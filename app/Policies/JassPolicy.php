<?php

namespace App\Policies;

use App\Models\Jass;
use App\Models\User;

/**
 * Solo el superadmin gestiona JASS (spec §3): CRUD total. Ningún otro rol
 * tiene acceso, ni siquiera de lectura (presidente/tesorero/vocal solo
 * conocen su propia JASS a través del contexto de tenancy).
 */
class JassPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esSuperadmin();
    }

    public function view(User $user, Jass $jass): bool
    {
        return $user->esSuperadmin();
    }

    public function create(User $user): bool
    {
        return $user->esSuperadmin();
    }

    public function update(User $user, Jass $jass): bool
    {
        return $user->esSuperadmin();
    }

    public function delete(User $user, Jass $jass): bool
    {
        return $user->esSuperadmin();
    }

    public function restore(User $user, Jass $jass): bool
    {
        return $user->esSuperadmin();
    }

    public function forceDelete(User $user, Jass $jass): bool
    {
        return false;
    }
}
