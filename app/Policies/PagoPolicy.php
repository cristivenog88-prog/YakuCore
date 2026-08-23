<?php

namespace App\Policies;

use App\Models\Pago;
use App\Models\User;

/**
 * Pago (spec §3): presidente y tesorero cobran; vocal y superadmin solo
 * lectura (consulta de deudas). Inmutable por rutas (CLAUDE.md #4): no hay
 * update/delete — la policy ni los define.
 */
class PagoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Pago $pago): bool
    {
        if ($user->esSuperadmin()) {
            return true;
        }

        return $user->jass_id === $pago->jass_id;
    }

    public function create(User $user): bool
    {
        return $user->esPresidente() || $user->esTesorero();
    }
}
