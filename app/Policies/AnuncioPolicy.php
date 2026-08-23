<?php

namespace App\Policies;

use App\Models\Anuncio;
use App\Models\User;

/**
 * Anuncio (spec §3): presidente y tesorero CRUD en su JASS; vocal y
 * superadmin solo lectura (CLAUDE.md #8).
 */
class AnuncioPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Anuncio $anuncio): bool
    {
        if ($user->esSuperadmin()) {
            return true;
        }

        return $user->jass_id === $anuncio->jass_id;
    }

    public function create(User $user): bool
    {
        return $user->esPresidente() || $user->esTesorero();
    }

    public function update(User $user, Anuncio $anuncio): bool
    {
        if (! ($user->esPresidente() || $user->esTesorero())) {
            return false;
        }

        return $user->jass_id === $anuncio->jass_id;
    }

    public function delete(User $user, Anuncio $anuncio): bool
    {
        return $this->update($user, $anuncio);
    }
}
