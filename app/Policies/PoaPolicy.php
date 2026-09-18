<?php

namespace App\Policies;

use App\Models\Poa;
use App\Models\User;

/**
 * POA (guía POA): presidente y tesorero lo elaboran y actualizan; vocal y
 * superadmin solo lectura (superadmin: solo-lectura en módulos operativos,
 * CLAUDE.md #8).
 */
class PoaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Poa $poa): bool
    {
        if ($user->esSuperadmin()) {
            return true;
        }

        return $user->jass_id === $poa->jass_id;
    }

    public function create(User $user): bool
    {
        return $user->esPresidente() || $user->esTesorero();
    }

    public function update(User $user, Poa $poa): bool
    {
        if (! ($user->esPresidente() || $user->esTesorero())) {
            return false;
        }

        return $user->jass_id === $poa->jass_id;
    }
}
