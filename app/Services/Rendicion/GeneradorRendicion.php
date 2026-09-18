<?php

namespace App\Services\Rendicion;

use App\Enums\CategoriaActividadPoa;
use App\Models\Gasto;
use App\Models\Jass;
use App\Models\Pago;
use App\Models\Poa;
use App\Models\Rendicion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Genera (o actualiza) la Rendición trimestral consolidando desde:
 *   - Pagos del trimestre (ingresos)
 *   - Gastos del trimestre por categoría (ejecución vs. POA)
 *   - Saldo acumulado hasta el inicio del trimestre (saldo_inicial)
 *
 * Idempotente: mismo jass+año+trimestre siempre actualiza la misma fila.
 * NO aprueba: el paso de aprobación exige checklist completo + fecha de
 * asamblea + número de acta, y se hace desde el controller.
 */
class GeneradorRendicion
{
    public function handle(Jass $jass, int $anio, int $trimestre): Rendicion
    {
        if ($trimestre < 1 || $trimestre > 4) {
            throw new InvalidArgumentException('El trimestre debe estar entre 1 y 4.');
        }

        $inicio = Carbon::createFromDate($anio, ($trimestre - 1) * 3 + 1, 1)->startOfDay();
        $fin = (clone $inicio)->addMonths(3)->subDay()->endOfDay();

        return DB::transaction(function () use ($jass, $anio, $trimestre, $inicio, $fin) {
            $ingresos = (float) Pago::query()
                ->where('jass_id', $jass->id)
                ->whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()])
                ->sum('monto');

            // Saldo inicial = ingresos - egresos anteriores al trimestre
            $ingresosAnteriores = (float) Pago::query()
                ->where('jass_id', $jass->id)
                ->where('fecha_pago', '<', $inicio->toDateString())
                ->sum('monto');
            $egresosAnteriores = (float) Gasto::query()
                ->where('jass_id', $jass->id)
                ->where(function ($q) use ($inicio) {
                    $q->where('fecha_compra', '<', $inicio->toDateString())
                        ->orWhere(function ($q2) use ($inicio) {
                            $q2->whereNull('fecha_compra')->where('created_at', '<', $inicio);
                        });
                })
                ->sum('monto');
            $saldoInicial = round($ingresosAnteriores - $egresosAnteriores, 2);

            // Ejecución por categoría (incluye "sin_categoria" para gastos históricos NULL)
            $ejecucion = [];
            $egresosTotal = 0.0;
            foreach (CategoriaActividadPoa::cases() as $cat) {
                $monto = (float) $this->egresosDelTrimestre($jass, $inicio, $fin)
                    ->where('categoria', $cat->value)
                    ->sum('monto');
                $ejecucion[$cat->value] = round($monto, 2);
                $egresosTotal += $monto;
            }
            $sinCategoria = (float) $this->egresosDelTrimestre($jass, $inicio, $fin)
                ->whereNull('categoria')
                ->sum('monto');
            if ($sinCategoria > 0) {
                $ejecucion['sin_categoria'] = round($sinCategoria, 2);
                $egresosTotal += $sinCategoria;
            }

            $saldoFinal = round($saldoInicial + $ingresos - $egresosTotal, 2);

            $poaVigente = Poa::query()
                ->where('jass_id', $jass->id)
                ->where('anio', $anio)
                ->first();

            return Rendicion::updateOrCreate(
                ['jass_id' => $jass->id, 'anio' => $anio, 'trimestre' => $trimestre],
                [
                    'poa_id' => $poaVigente?->id,
                    'saldo_inicial' => $saldoInicial,
                    'ingresos_pagos' => round($ingresos, 2),
                    // otros_ingresos queda como está (editable manualmente por el tesorero)
                    'ejecucion_por_categoria' => $ejecucion,
                    'egresos_total' => round($egresosTotal, 2),
                    'saldo_final' => $saldoFinal,
                ]
            );
        });
    }

    /**
     * Consulta base de gastos que caen dentro del trimestre (usa fecha_compra
     * si está, cae a created_at si no).
     */
    private function egresosDelTrimestre(Jass $jass, Carbon $inicio, Carbon $fin): Builder
    {
        return Gasto::query()
            ->where('jass_id', $jass->id)
            ->where(function ($q) use ($inicio, $fin) {
                $q->whereBetween('fecha_compra', [$inicio->toDateString(), $fin->toDateString()])
                    ->orWhere(function ($q2) use ($inicio, $fin) {
                        $q2->whereNull('fecha_compra')->whereBetween('created_at', [$inicio, $fin]);
                    });
            });
    }
}
