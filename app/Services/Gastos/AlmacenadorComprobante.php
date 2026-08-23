<?php

namespace App\Services\Gastos;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Comprobantes de gasto en disco privado `storage/app/private/comprobantes/{jass_id}/...`
 * (spec §7.1): jamás en `public`, jamás URL directa — solo el controller
 * autorizado los sirve (GastoController::comprobante).
 */
class AlmacenadorComprobante
{
    public function guardar(UploadedFile $foto, int $jassId): string
    {
        return $foto->store("comprobantes/{$jassId}", 'local');
    }

    public function eliminar(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('local')->delete($path);
        }
    }
}
