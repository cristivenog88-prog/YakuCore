<?php

namespace App\Services\Reuniones;

use App\Enums\EstadoReunion;
use App\Models\Reunion;
use App\Services\Auditoria\RegistradorAuditoria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Único punto de creación de Asistencia (spec §6.10, CLAUDE.md #4): en una
 * transacción, inserta la asistencia de cada socio, pasa la reunión a
 * `finalizada` y audita. Bloquea la fila de la reunión (lockForUpdate) para
 * que dos solicitudes concurrentes no puedan tomar asistencia dos veces
 * sobre la misma reunión — el unique (reunion_id, socio_id) es la red final.
 */
class RegistradorAsistencia
{
    public function __construct(private readonly RegistradorAuditoria $auditoria) {}

    /**
     * @param  array<int, string>  $asistenciasPorSocio  socio_id => 'presente'|'ausente'
     */
    public function registrar(Reunion $reunion, array $asistenciasPorSocio): void
    {
        DB::transaction(function () use ($reunion, $asistenciasPorSocio) {
            $reunionBloqueada = Reunion::whereKey($reunion->id)->lockForUpdate()->firstOrFail();

            if ($reunionBloqueada->estado !== EstadoReunion::Programada) {
                throw new InvalidArgumentException('Esta reunión ya no admite tomar asistencia.');
            }

            foreach ($asistenciasPorSocio as $socioId => $estado) {
                $reunionBloqueada->asistencias()->create([
                    'socio_id' => $socioId,
                    'usuario_id' => Auth::id(),
                    'estado' => $estado,
                ]);
            }

            $reunionBloqueada->estado = EstadoReunion::Finalizada;
            $reunionBloqueada->save();

            $this->auditoria->registrar('tomar_asistencia', $reunionBloqueada, ['total_socios' => count($asistenciasPorSocio)]);
        });
    }
}
