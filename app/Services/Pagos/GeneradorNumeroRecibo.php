<?php

namespace App\Services\Pagos;

use App\Models\Jass;
use Illuminate\Support\Facades\DB;

/**
 * Correlativo de recibo por JASS, compartido por los tres tipos de pago
 * (regla 5.8). El incremento ocurre dentro de una transacción con
 * lockForUpdate sobre la fila de la JASS para evitar números duplicados
 * o huecos ante altas concurrentes (garantía real bajo MySQL en
 * producción; RegistradorPago reutiliza esta misma transacción).
 */
class GeneradorNumeroRecibo
{
    public function generar(int $jassId): int
    {
        return DB::transaction(function () use ($jassId) {
            $jass = Jass::whereKey($jassId)->lockForUpdate()->firstOrFail();

            $jass->ultimo_numero_recibo = $jass->ultimo_numero_recibo + 1;
            $jass->save();

            return $jass->ultimo_numero_recibo;
        });
    }
}
