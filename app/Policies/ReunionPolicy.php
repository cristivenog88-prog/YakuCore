<?php

namespace App\Policies;

use App\Enums\EstadoReunion;
use App\Models\Reunion;
use App\Models\User;

/**
 * Reunión (spec §3): presidente y tesorero crean/editan/cancelan y toman
 * asistencia; vocal y superadmin solo lectura (CLAUDE.md #8). Editable,
 * cancelable y apta para tomar asistencia únicamente mientras está
 * `programada` (plan Fase 6 tarea 2, P10): una cancelada o finalizada queda
 * cerrada para siempre.
 */
class ReunionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Reunion $reunion): bool
    {
        if ($user->esSuperadmin()) {
            return true;
        }

        return $user->jass_id === $reunion->jass_id;
    }

    public function create(User $user): bool
    {
        return $user->esPresidente() || $user->esTesorero();
    }

    public function update(User $user, Reunion $reunion): bool
    {
        if (! ($user->esPresidente() || $user->esTesorero())) {
            return false;
        }

        if ($user->jass_id !== $reunion->jass_id) {
            return false;
        }

        return $reunion->estado === EstadoReunion::Programada;
    }

    public function cancelar(User $user, Reunion $reunion): bool
    {
        return $this->update($user, $reunion);
    }

    public function tomarAsistencia(User $user, Reunion $reunion): bool
    {
        return $this->update($user, $reunion);
    }
}
