<?php

namespace App\Services\Pagos;

use App\Enums\Periodicidad;
use App\Enums\TipoPago;
use App\Models\Jass;
use App\Models\Medidor;
use App\Models\Pago;
use App\Models\Socio;
use App\Models\Vivienda;
use App\Services\Auditoria\RegistradorAuditoria;
use App\Services\Morosidad\EstadoCuenta;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Único punto de creación de pagos. Cada método valida tipo↔JASS (8.6/regla
 * 5.2-5.3), normaliza el periodo a CHAR(7) YYYY-MM, y crea el pago junto al
 * correlativo de recibo dentro de la misma transacción — el monto y el
 * número de recibo se calculan siempre en servidor (CLAUDE.md #2).
 */
class RegistradorPago
{
    private const MESES = [
        '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
        '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
        '09' => 'setiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre',
    ];

    public function __construct(
        private readonly GeneradorNumeroRecibo $generadorNumeroRecibo,
        private readonly CalculadoraConsumo $calculadoraConsumo,
        private readonly RegistradorAuditoria $auditoria,
        private readonly EstadoCuenta $estadoCuenta,
    ) {}

    public function mantenimiento(Socio $socio, Vivienda $vivienda, string $periodo, float $monto, string $fechaPago): Pago
    {
        $jass = $this->jassActual();
        $this->asegurarMismaJass($jass, $socio, $vivienda);
        $periodo = $this->normalizarPeriodo($periodo);

        return DB::transaction(function () use ($jass, $socio, $vivienda, $periodo, $monto, $fechaPago) {
            $pago = Pago::create([
                'socio_id' => $socio->id,
                'vivienda_id' => $vivienda->id,
                'medidor_id' => null,
                'usuario_id' => Auth::id(),
                'tipo' => TipoPago::Mantenimiento,
                'numero_recibo' => $this->generadorNumeroRecibo->generar($jass->id),
                'periodo' => $periodo,
                'concepto' => $this->conceptoMantenimiento($jass, $periodo),
                'monto' => $monto,
                'fecha_pago' => $fechaPago,
            ]);

            $this->auditoria->registrar('cobrar', $pago, ['tipo' => 'mantenimiento', 'monto' => $monto, 'periodo' => $periodo]);
            $this->recalcularMorosidad($vivienda);

            return $pago;
        });
    }

    public function consumoMedidor(Socio $socio, Medidor $medidor, string $periodo, float $lecturaActual, string $fechaPago): Pago
    {
        $jass = $this->jassActual();

        if (! $jass->tiene_medidor) {
            throw new InvalidArgumentException('Esta JASS no trabaja con medidores.');
        }

        $vivienda = $medidor->vivienda;
        $this->asegurarMismaJass($jass, $socio, $vivienda, $medidor);
        $periodo = $this->normalizarPeriodo($periodo);
        $lecturaAnterior = $this->calculadoraConsumo->lecturaAnterior($medidor);

        if ($lecturaActual < $lecturaAnterior) {
            throw new InvalidArgumentException('La lectura actual no puede ser menor a la lectura anterior.');
        }

        $monto = $this->calculadoraConsumo->calcularMonto($lecturaAnterior, $lecturaActual, (float) $jass->tarifa_base_m3);

        return DB::transaction(function () use ($jass, $socio, $vivienda, $medidor, $periodo, $lecturaAnterior, $lecturaActual, $monto, $fechaPago) {
            $pago = Pago::create([
                'socio_id' => $socio->id,
                'vivienda_id' => $vivienda->id,
                'medidor_id' => $medidor->id,
                'usuario_id' => Auth::id(),
                'tipo' => TipoPago::ConsumoMedidor,
                'numero_recibo' => $this->generadorNumeroRecibo->generar($jass->id),
                'periodo' => $periodo,
                'concepto' => 'Consumo de agua '.$this->etiquetaMes($periodo),
                'lectura_anterior' => $lecturaAnterior,
                'lectura_actual' => $lecturaActual,
                'monto' => $monto,
                'fecha_pago' => $fechaPago,
            ]);

            $this->auditoria->registrar('cobrar', $pago, ['tipo' => 'consumo_medidor', 'monto' => $monto, 'periodo' => $periodo]);
            $this->recalcularMorosidad($vivienda);

            return $pago;
        });
    }

    public function consumoDirecto(Socio $socio, Vivienda $vivienda, string $periodo, float $monto, string $fechaPago): Pago
    {
        $jass = $this->jassActual();

        if ($jass->tiene_medidor) {
            throw new InvalidArgumentException('Esta JASS trabaja con medidores; el consumo se cobra por medidor.');
        }

        $this->asegurarMismaJass($jass, $socio, $vivienda);
        $periodo = $this->normalizarPeriodo($periodo);

        return DB::transaction(function () use ($jass, $socio, $vivienda, $periodo, $monto, $fechaPago) {
            $pago = Pago::create([
                'socio_id' => $socio->id,
                'vivienda_id' => $vivienda->id,
                'medidor_id' => null,
                'usuario_id' => Auth::id(),
                'tipo' => TipoPago::ConsumoDirecto,
                'numero_recibo' => $this->generadorNumeroRecibo->generar($jass->id),
                'periodo' => $periodo,
                'concepto' => 'Consumo de agua '.$this->etiquetaMes($periodo),
                'monto' => $monto,
                'fecha_pago' => $fechaPago,
            ]);

            $this->auditoria->registrar('cobrar', $pago, ['tipo' => 'consumo_directo', 'monto' => $monto, 'periodo' => $periodo]);
            $this->recalcularMorosidad($vivienda);

            return $pago;
        });
    }

    /**
     * Recalcula morosa_desde de la vivienda inline tras el cobro (sección 4
     * de la Fase 4): no espera al job nocturno `yaku:marcar-morosos`.
     */
    private function recalcularMorosidad(Vivienda $vivienda): void
    {
        $vivienda->update(['morosa_desde' => $this->estadoCuenta->fechaMorosaDesde($vivienda)]);
    }

    private function jassActual(): Jass
    {
        return app(ContextoJass::class)->jass() ?? throw new InvalidArgumentException('No hay una JASS activa en el contexto.');
    }

    /**
     * Defensa en profundidad (CLAUDE.md #1): aunque el caller ya debería
     * haber resuelto socio/vivienda/medidor bajo el contexto correcto (vía
     * TenantScope), este servicio nunca confía ciegamente en eso al
     * mover dinero — vuelve a verificar la jass_id de cada modelo.
     */
    private function asegurarMismaJass(Jass $jass, Socio $socio, Vivienda $vivienda, ?Medidor $medidor = null): void
    {
        if ($socio->jass_id !== $jass->id || $vivienda->jass_id !== $jass->id) {
            throw new InvalidArgumentException('El socio o la vivienda no pertenecen a la JASS activa.');
        }

        if ($medidor !== null && $medidor->jass_id !== $jass->id) {
            throw new InvalidArgumentException('El medidor no pertenece a la JASS activa.');
        }
    }

    /**
     * Normaliza cualquier entrada de fecha/periodo a CHAR(7) YYYY-MM (el mes
     * inicial del periodo cubierto, sección 2 del plan).
     */
    private function normalizarPeriodo(string $periodoBase): string
    {
        return Carbon::parse($periodoBase.'-01')->format('Y-m');
    }

    private function etiquetaMes(string $periodo): string
    {
        [$anio, $mes] = explode('-', $periodo);

        return self::MESES[$mes].' '.$anio;
    }

    private function conceptoMantenimiento(Jass $jass, string $periodo): string
    {
        [$anio, $mes] = explode('-', $periodo);
        $numeroMes = (int) $mes;

        return match ($jass->periodicidad_mantenimiento) {
            Periodicidad::Mensual => 'Mantenimiento '.$this->etiquetaMes($periodo),
            Periodicidad::Trimestral => 'Mantenimiento trimestre '.(int) ceil($numeroMes / 3)." {$anio}",
            Periodicidad::Semestral => 'Mantenimiento semestre '.(int) ceil($numeroMes / 6)." {$anio}",
            Periodicidad::Anual => "Mantenimiento anual {$anio}",
        };
    }
}
