<?php

namespace App\Policies;

use App\Models\Socio;
use App\Models\User;

/**
 * Socio/Vivienda/Medidor (spec §3): presidente y tesorero CRUD en su JASS;
 * vocal y superadmin solo lectura (superadmin: solo-lectura en módulos
 * operativos, CLAUDE.md #8).
 */
class SocioPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Socio $socio): bool
    {
        if ($user->esSuperadmin()) {
            return true;
        }

        return $user->jass_id === $socio->jass_id;
    }

    public function create(User $user): bool
    {
        return $user->esPresidente() || $user->esTesorero();
    }

    public function update(User $user, Socio $socio): bool
    {
        if (! ($user->esPresidente() || $user->esTesorero())) {
            return false;
        }

        return $user->jass_id === $socio->jass_id;
    }

    public function delete(User $user, Socio $socio): bool
    {
        return $this->update($user, $socio);
    }
}
