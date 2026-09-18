<?php

namespace App\Services\Poa;

use App\Models\Poa;
use InvalidArgumentException;

/**
 * Deriva la cuota mensual de mantenimiento desde el POA.
 *
 * Fórmula base (guía POA, pasos 2 y 3 — caso 1, cuota única):
 *   presupuesto base   = Σ (frecuencia_anual × costo_unitario) de las actividades
 *   presupuesto neto   = base + reserva obligatoria (10%) − otros ingresos
 *   cuota mensual      = presupuesto neto ÷ (12 × N° de socios)
 *
 * Fórmula SUNASS (RCD N.° 028-2018-SUNASS-CD — para JASS que reportan
 * formalmente al ente rector; usar cuotaMensualSunass):
 *   CF = PA / [12 × (1 − FIP) × (NA − NE)]
 * donde
 *   PA  = presupuesto neto (=presupuestoNeto del POA, incluye reserva)
 *   NA  = número total de socios
 *   NE  = socios exonerados
 *   FIP = factor de incumplimiento de pago (0 ≤ FIP < 1)
 *   La cuota propuesta se redondea hacia arriba a 1 decimal (ROUNDUP).
 *
 * Todo se calcula en servidor (CLAUDE.md #2) — nunca se acepta un monto de
 * mantenimiento desde el request cuando la JASS usa POA.
 */
class CalculadoraCuota
{
    private const RESERVA_PORCENTAJE = 0.10;

    public function presupuestoBase(Poa $poa): float
    {
        return (float) ($poa->actividades()
            ->selectRaw('COALESCE(SUM(frecuencia_anual * costo_unitario), 0) as total')
            ->value('total') ?? 0);
    }

    public function reserva(Poa $poa): float
    {
        return round($this->presupuestoBase($poa) * self::RESERVA_PORCENTAJE, 2);
    }

    public function presupuestoNeto(Poa $poa): float
    {
        $neto = $this->presupuestoBase($poa) + $this->reserva($poa) - (float) $poa->otros_ingresos;

        return max(0.0, round($neto, 2));
    }

    /**
     * Null si no hay socios sobre los cuales repartir la cuota (evita
     * dividir entre cero) — el caller decide cómo comunicarlo.
     */
    public function cuotaMensual(Poa $poa, int $numeroSocios): ?float
    {
        if ($numeroSocios <= 0) {
            return null;
        }

        return round($this->presupuestoNeto($poa) / (12 * $numeroSocios), 2);
    }

    /**
     * Fórmula SUNASS con exonerados y factor de incumplimiento de pago (FIP).
     * Devuelve el desglose completo para poder mostrarlo/persistirlo en el
     * módulo de rendición y en el informe PDF (INFORME N.° NNNN-YYYY-JASS).
     *
     * @return array{
     *     presupuesto_anual: float,
     *     numero_socios: int,
     *     socios_exonerados: int,
     *     factor_incumplimiento: float,
     *     socios_sujetos: int,
     *     cuotas_cobrables_anio: float,
     *     cuota_calculada: float,
     *     cuota_propuesta: float,
     *     ingreso_anual_esperado: float,
     *     cobertura: float,
     * }
     */
    public function cuotaMensualSunass(Poa $poa, int $numeroSocios, int $sociosExonerados, float $fip): array
    {
        if ($numeroSocios < 0 || $sociosExonerados < 0) {
            throw new InvalidArgumentException('Los conteos de socios no pueden ser negativos.');
        }
        if ($sociosExonerados > $numeroSocios) {
            throw new InvalidArgumentException('Los socios exonerados no pueden exceder al total de socios.');
        }
        if ($fip < 0 || $fip >= 1) {
            throw new InvalidArgumentException('El factor de incumplimiento debe estar en el rango [0, 1).');
        }

        $pa = $this->presupuestoNeto($poa);
        $sujetos = $numeroSocios - $sociosExonerados;
        $cobrables = 12 * (1 - $fip) * $sujetos;

        $cuotaCalc = $cobrables > 0 ? $pa / $cobrables : 0.0;
        $cuotaPropuesta = $cobrables > 0 ? $this->redondearHaciaArriba($cuotaCalc, 1) : 0.0;
        $ingreso = $cuotaPropuesta * $cobrables;
        $cobertura = $pa > 0 ? $ingreso / $pa : 0.0;

        return [
            'presupuesto_anual' => round($pa, 2),
            'numero_socios' => $numeroSocios,
            'socios_exonerados' => $sociosExonerados,
            'factor_incumplimiento' => round($fip, 4),
            'socios_sujetos' => $sujetos,
            'cuotas_cobrables_anio' => round($cobrables, 4),
            'cuota_calculada' => round($cuotaCalc, 2),
            'cuota_propuesta' => round($cuotaPropuesta, 2),
            'ingreso_anual_esperado' => round($ingreso, 2),
            'cobertura' => round($cobertura, 4),
        ];
    }

    private function redondearHaciaArriba(float $valor, int $decimales): float
    {
        $mult = 10 ** $decimales;

        return ceil($valor * $mult) / $mult;
    }
}
