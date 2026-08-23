<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vivienda;

/**
 * Misma matriz que SocioPolicy (spec §3): presidente y tesorero CRUD en su
 * JASS; vocal y superadmin solo lectura.
 */
class ViviendaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Vivienda $vivienda): bool
    {
        if ($user->esSuperadmin()) {
            return true;
        }

        return $user->jass_id === $vivienda->jass_id;
    }

    public function create(User $user): bool
    {
        return $user->esPresidente() || $user->esTesorero();
    }

    public function update(User $user, Vivienda $vivienda): bool
    {
        if (! ($user->esPresidente() || $user->esTesorero())) {
            return false;
        }

        return $user->jass_id === $vivienda->jass_id;
    }

    public function delete(User $user, Vivienda $vivienda): bool
    {
        return $this->update($user, $vivienda);
    }
}
