<?php

namespace App\Services\Medidores;

use App\Models\Medidor;
use App\Services\Auditoria\RegistradorAuditoria;

/**
 * Flujo de reemplazo de medidor (decisión 8.3): en vez de bloquear el pago
 * cuando la lectura actual sería menor a la anterior por un cambio físico
 * de medidor, resetea la base a 0 y deja traza de las lecturas previas en
 * auditoría. No crea un medidor nuevo: reinicia el mismo registro.
 */
class ReemplazoMedidor
{
    public function __construct(private readonly RegistradorAuditoria $auditoria) {}

    public function ejecutar(Medidor $medidor, string $motivo): Medidor
    {
        $lecturaInicialPrevia = $medidor->lectura_inicial;
        $reiniciadoEnPrevio = $medidor->reiniciado_en;

        $medidor->lectura_inicial = 0;
        $medidor->reiniciado_en = now();
        $medidor->save();

        $this->auditoria->registrar('reemplazar_medidor', $medidor, [
            'motivo' => $motivo,
            'lectura_inicial_previa' => (string) $lecturaInicialPrevia,
            'reiniciado_en_previo' => $reiniciadoEnPrevio?->toDateTimeString(),
        ]);

        return $medidor;
    }
}
