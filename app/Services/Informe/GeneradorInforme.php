<?php

namespace App\Services\Informe;

use App\Models\Informe;
use App\Models\Rendicion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Genera el Informe de Gestión Financiera (PDF) a partir de una Rendición
 * aprobada. El correlativo NNNN-YYYY es único por JASS y año (reinicia cada
 * enero). La operación es idempotente por rendición: si ya existe un
 * informe para esa rendición, devuelve el existente sin duplicar.
 */
class GeneradorInforme
{
    public function handle(Rendicion $rendicion, User $actor): Informe
    {
        if ($rendicion->estado !== 'aprobada') {
            throw new InvalidArgumentException('La rendición debe estar aprobada antes de emitir el informe.');
        }

        return DB::transaction(function () use ($rendicion, $actor) {
            $existente = Informe::query()->where('rendicion_id', $rendicion->id)->first();
            if ($existente) {
                return $existente;
            }

            $anio = $rendicion->anio;

            $ultimo = Informe::query()
                ->where('jass_id', $rendicion->jass_id)
                ->where('anio', $anio)
                ->orderByDesc('numero')
                ->lockForUpdate()
                ->first();

            $numero = ($ultimo?->numero ?? 0) + 1;

            $informe = Informe::create([
                'jass_id' => $rendicion->jass_id,
                'rendicion_id' => $rendicion->id,
                'numero' => $numero,
                'anio' => $anio,
                'generado_at' => now(),
                'generado_por_user_id' => $actor->id,
            ]);

            $pdf = Pdf::loadView('informes.gestion-financiera', [
                'informe' => $informe,
                'rendicion' => $rendicion->fresh(),
                'jass' => $rendicion->loadMissing('jass')->jass,
            ]);

            $filename = sprintf(
                'informes/%d/informe-%04d-%d.pdf',
                $rendicion->jass_id,
                $numero,
                $anio
            );

            Storage::disk('local')->put($filename, $pdf->output());
            $informe->update(['pdf_path' => $filename]);

            return $informe;
        });
    }
}
