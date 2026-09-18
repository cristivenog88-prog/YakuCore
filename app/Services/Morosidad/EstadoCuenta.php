<?php

namespace App\Services\Morosidad;

use App\Enums\Periodicidad;
use App\Enums\TipoPago;
use App\Models\Pago;
use App\Models\Vivienda;
use Illuminate\Support\Carbon;

/**
 * Deriva los periodos vencidos e impagos de una vivienda (8.8): mantenimiento
 * según periodicidad_mantenimiento de la JASS, consumo siempre mensual. Los
 * periodos se alinean a límites de calendario (mismo criterio que los
 * conceptos de RegistradorPago: trimestre = ene-mar/abr-jun/..., etc.) y se
 * consideran pagados si existe cualquier pago cuyo periodo caiga dentro del
 * rango cubierto — el formulario de cobro deja elegir cualquier mes dentro
 * del periodo, no solo el mes inicial.
 */
class EstadoCuenta
{
    private const DURACION_MESES = [
        Periodicidad::Mensual->value => 1,
        Periodicidad::Trimestral->value => 3,
        Periodicidad::Semestral->value => 6,
        Periodicidad::Anual->value => 12,
    ];

    /**
     * @return array{mantenimiento: string[], consumo: string[]}
     */
    public function periodosPendientes(Vivienda $vivienda): array
    {
        $jass = $vivienda->loadMissing('jass')->jass;
        $hoy = Carbon::today();
        $desde = $vivienda->created_at->copy()->startOfDay();

        $rangosMantenimiento = $this->generarRangosVencidos($desde, $hoy, $jass->periodicidad_mantenimiento, $jass->dia_vencimiento);
        $rangosConsumo = $this->generarRangosVencidos($desde, $hoy, Periodicidad::Mensual, $jass->dia_vencimiento);

        $tipoConsumo = $jass->tiene_medidor ? TipoPago::ConsumoMedidor : TipoPago::ConsumoDirecto;

        $periodosPagadosMantenimiento = $this->periodosPagados($vivienda, TipoPago::Mantenimiento);
        $periodosPagadosConsumo = $this->periodosPagados($vivienda, $tipoConsumo);

        return [
            'mantenimiento' => $this->pendientes($rangosMantenimiento, $periodosPagadosMantenimiento),
            'consumo' => $this->pendientes($rangosConsumo, $periodosPagadosConsumo),
        ];
    }

    /**
     * Fecha del vencimiento impago más antiguo entre mantenimiento y
     * consumo, o null si la vivienda está al día (8.8, campo morosa_desde).
     */
    public function fechaMorosaDesde(Vivienda $vivienda): ?Carbon
    {
        $jass = $vivienda->loadMissing('jass')->jass;
        $hoy = Carbon::today();
        $desde = $vivienda->created_at->copy()->startOfDay();

        $rangosMantenimiento = $this->generarRangosVencidos($desde, $hoy, $jass->periodicidad_mantenimiento, $jass->dia_vencimiento);
        $rangosConsumo = $this->generarRangosVencidos($desde, $hoy, Periodicidad::Mensual, $jass->dia_vencimiento);

        $tipoConsumo = $jass->tiene_medidor ? TipoPago::ConsumoMedidor : TipoPago::ConsumoDirecto;

        $pendientesMantenimiento = $this->rangosPendientes($rangosMantenimiento, $this->periodosPagados($vivienda, TipoPago::Mantenimiento));
        $pendientesConsumo = $this->rangosPendientes($rangosConsumo, $this->periodosPagados($vivienda, $tipoConsumo));

        $vencimientos = array_merge(
            array_map(fn (array $r) => $r['vencimiento'], $pendientesMantenimiento),
            array_map(fn (array $r) => $r['vencimiento'], $pendientesConsumo),
        );

        if ($vencimientos === []) {
            return null;
        }

        usort($vencimientos, fn (Carbon $a, Carbon $b) => $a->lessThan($b) ? -1 : ($a->greaterThan($b) ? 1 : 0));

        return $vencimientos[0];
    }

    /**
     * @return string[] periodos (YYYY-MM) registrados para la vivienda y tipo dados.
     */
    private function periodosPagados(Vivienda $vivienda, TipoPago $tipo): array
    {
        return Pago::where('vivienda_id', $vivienda->id)
            ->where('tipo', $tipo)
            ->pluck('periodo')
            ->all();
    }

    /**
     * @param  array<int, array{inicio: string, fin: string, vencimiento: Carbon}>  $rangos
     * @param  string[]  $periodosPagados
     * @return string[]
     */
    private function pendientes(array $rangos, array $periodosPagados): array
    {
        return array_values(array_map(
            fn (array $r) => $r['inicio'],
            $this->rangosPendientes($rangos, $periodosPagados),
        ));
    }

    /**
     * @param  array<int, array{inicio: string, fin: string, vencimiento: Carbon}>  $rangos
     * @param  string[]  $periodosPagados
     * @return array<int, array{inicio: string, fin: string, vencimiento: Carbon}>
     */
    private function rangosPendientes(array $rangos, array $periodosPagados): array
    {
        return array_values(array_filter(
            $rangos,
            fn (array $r) => ! $this->tienePagoEnRango($r, $periodosPagados),
        ));
    }

    /**
     * @param  array{inicio: string, fin: string, vencimiento: Carbon}  $rango
     * @param  string[]  $periodosPagados
     */
    private function tienePagoEnRango(array $rango, array $periodosPagados): bool
    {
        foreach ($periodosPagados as $periodo) {
            if ($periodo >= $rango['inicio'] && $periodo <= $rango['fin']) {
                return true;
            }
        }

        return false;
    }

    private function duracionMeses(Periodicidad $periodicidad): int
    {
        return self::DURACION_MESES[$periodicidad->value];
    }

    /**
     * Alinea una fecha al inicio del periodo de calendario que la contiene
     * (mensual: el mismo mes; trimestral: ene/abr/jul/oct; semestral:
     * ene/jul; anual: enero) — mismo criterio que RegistradorPago usa para
     * etiquetar el concepto.
     */
    private function alinearInicio(Carbon $fecha, int $duracionMeses): Carbon
    {
        $mesIndice = $fecha->month - 1;
        $inicioAlineado = intdiv($mesIndice, $duracionMeses) * $duracionMeses;

        return Carbon::create($fecha->year, $inicioAlineado + 1, 1);
    }

    /**
     * @return array<int, array{inicio: string, fin: string, vencimiento: Carbon}>
     */
    private function generarRangosVencidos(Carbon $desde, Carbon $hoy, Periodicidad $periodicidad, int $diaVencimiento): array
    {
        $duracion = $this->duracionMeses($periodicidad);
        $cursor = $this->alinearInicio($desde, $duracion);
        $rangos = [];

        while (true) {
            $finPeriodo = $cursor->copy()->addMonths($duracion - 1);
            $diaValido = min($diaVencimiento, $finPeriodo->daysInMonth);
            $vencimiento = $finPeriodo->copy()->day($diaValido);

            if ($vencimiento->greaterThan($hoy)) {
                break;
            }

            $rangos[] = [
                'inicio' => $cursor->format('Y-m'),
                'fin' => $finPeriodo->format('Y-m'),
                'vencimiento' => $vencimiento,
            ];

            $cursor = $cursor->copy()->addMonths($duracion);
        }

        return $rangos;
    }
}
