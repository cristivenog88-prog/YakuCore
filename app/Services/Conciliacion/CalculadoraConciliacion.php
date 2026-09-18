<?php

namespace App\Services\Conciliacion;

use App\Models\Conciliacion;
use App\Models\Gasto;
use App\Models\Jass;
use App\Models\Pago;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Calcula/actualiza los totales de una conciliación mensual comparando:
 *   - total de pagos (ingresos) registrados en el periodo
 *   - total de gastos (egresos) registrados en el periodo
 *   - saldo calculado = pagos − gastos
 *   - fondos verificados (efectivo + banco/medios digitales) declarados por el tesorero
 *   - diferencia = fondos_verificados − saldo_calculado
 * Estado = CONCILIADO si |diferencia| < 0.01, sino REVISAR.
 *
 * Los importes se calculan siempre en servidor (CLAUDE.md §2 — jamás confiar
 * en montos enviados desde el cliente para el total del periodo).
 */
class CalculadoraConciliacion
{
    private const TOLERANCIA = 0.01;

    public function handle(Jass $jass, string $periodo, float $efectivo, float $banco): Conciliacion
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodo)) {
            throw new InvalidArgumentException('El periodo debe tener formato YYYY-MM.');
        }

        return DB::transaction(function () use ($jass, $periodo, $efectivo, $banco) {
            // Los modelos con PerteneceAJass ya filtran por la JASS del contexto
            // activo; aquí forzamos jass_id explícito para no depender del scope
            // (útil cuando el service se llama fuera de un request panel).
            $pagosTotal = (float) Pago::query()
                ->where('jass_id', $jass->id)
                ->where('periodo', $periodo)
                ->sum('monto');

            // Gastos por rango de fechas (compatible cross-DB, sin DB::raw):
            // desde el primer día del mes hasta el último inclusive.
            $inicio = Carbon::parse($periodo.'-01')->startOfDay();
            $fin = (clone $inicio)->endOfMonth();

            $gastosTotal = (float) Gasto::query()
                ->where('jass_id', $jass->id)
                ->where(function ($q) use ($inicio, $fin) {
                    $q->whereBetween('fecha_compra', [$inicio->toDateString(), $fin->toDateString()])
                        ->orWhere(function ($q2) use ($inicio, $fin) {
                            $q2->whereNull('fecha_compra')
                                ->whereBetween('created_at', [$inicio, $fin]);
                        });
                })
                ->sum('monto');

            $saldoCalculado = round($pagosTotal - $gastosTotal, 2);
            $fondos = round($efectivo + $banco, 2);
            $diferencia = round($fondos - $saldoCalculado, 2);

            $estado = abs($diferencia) < self::TOLERANCIA ? 'conciliado' : 'revisar';

            return Conciliacion::updateOrCreate(
                ['jass_id' => $jass->id, 'periodo' => $periodo],
                [
                    'pagos_total' => round($pagosTotal, 2),
                    'gastos_total' => round($gastosTotal, 2),
                    'saldo_calculado' => $saldoCalculado,
                    'efectivo_verificado' => round($efectivo, 2),
                    'banco_verificado' => round($banco, 2),
                    'fondos_verificados' => $fondos,
                    'diferencia' => $diferencia,
                    'estado' => $estado,
                ]
            );
        });
    }
}
