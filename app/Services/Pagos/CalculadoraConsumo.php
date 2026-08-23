<?php

namespace App\Services\Pagos;

use App\Models\Medidor;
use App\Models\Pago;

/**
 * Cálculo de consumo con medidor (regla 5.7). La lectura anterior se
 * autocompleta con la última lectura_actual registrada para ese medidor
 * (8.2); si el medidor fue reemplazado (8.3), solo se consideran pagos
 * posteriores al reinicio. Sin pagos previos, la base es lectura_inicial.
 */
class CalculadoraConsumo
{
    public function lecturaAnterior(Medidor $medidor): float
    {
        $ultimoPago = Pago::where('medidor_id', $medidor->id)
            ->when(
                $medidor->reiniciado_en,
                fn ($query, $reiniciadoEn) => $query->where('fecha_pago', '>=', $reiniciadoEn->toDateString())
            )
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id')
            ->first();

        return $ultimoPago !== null
            ? (float) $ultimoPago->lectura_actual
            : (float) $medidor->lectura_inicial;
    }

    public function calcularMonto(float $lecturaAnterior, float $lecturaActual, float $tarifaBaseM3): float
    {
        return round(($lecturaActual - $lecturaAnterior) * $tarifaBaseM3, 2);
    }
}
