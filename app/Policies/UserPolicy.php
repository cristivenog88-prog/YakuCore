<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\User;

/**
 * Gestión de usuarios (spec §3): superadmin CRUD en cualquier JASS y es el
 * único que puede asignar el rol presidente; presidente solo gestiona
 * tesorero/vocal de su propia JASS. Tesorero y vocal no gestionan usuarios.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esSuperadmin() || $user->esPresidente();
    }

    public function view(User $user, User $model): bool
    {
        if ($user->esSuperadmin()) {
            return true;
        }

        if ($user->esPresidente()) {
            return $user->jass_id === $model->jass_id
                && in_array($model->rol, [Rol::Tesorero, Rol::Fiscal, Rol::Vocal], true);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->esSuperadmin() || $user->esPresidente();
    }

    public function update(User $user, User $model): bool
    {
        return $this->view($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->view($user, $model);
    }

    /**
     * Autorización fina para crear/reasignar un usuario a un rol y JASS
     * concretos: superadmin puede cualquier combinación (es el único que
     * asigna el rol presidente); presidente solo tesorero/vocal de su
     * propia JASS.
     */
    public function asignarRol(User $user, Rol $rolObjetivo, ?int $jassIdObjetivo): bool
    {
        if ($user->esSuperadmin()) {
            return true;
        }

        if ($user->esPresidente()) {
            return $user->jass_id === $jassIdObjetivo
                && in_array($rolObjetivo, [Rol::Tesorero, Rol::Fiscal, Rol::Vocal], true);
        }

        return false;
    }
}
