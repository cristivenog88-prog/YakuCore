<?php

namespace App\Policies;

use App\Models\Asistencia;
use App\Models\User;

/**
 * Asistencia (spec §3, CLAUDE.md #4): inmutable — ni el presidente ni el
 * superadmin pueden editarla o eliminarla una vez tomada. Se crea
 * exclusivamente vía Services/Reuniones/RegistradorAsistencia (la habilidad
 * de "tomar asistencia" la gobierna ReunionPolicy::tomarAsistencia).
 */
class AsistenciaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Asistencia $asistencia): bool
    {
        if ($user->esSuperadmin()) {
            return true;
        }

        return $user->jass_id === $asistencia->jass_id;
    }

    public function update(User $user, Asistencia $asistencia): bool
    {
        return false;
    }

    public function delete(User $user, Asistencia $asistencia): bool
    {
        return false;
    }
}
