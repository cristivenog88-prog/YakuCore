<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Rendicion;
use App\Models\User;

/**
 * Rendición trimestral: la genera y edita el Consejo Directivo (Presidente
 * o Tesorero); solo el Presidente puede aprobarla en asamblea. Fiscal y
 * Vocal son solo-lectura. Superadmin ve todo cross-JASS.
 */
class RendicionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::Superadmin, Rol::Presidente, Rol::Tesorero, Rol::Fiscal, Rol::Vocal], true);
    }

    public function view(User $user, Rendicion $r): bool
    {
        return $user->esSuperadmin() || $user->jass_id === $r->jass_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, [Rol::Presidente, Rol::Tesorero], true);
    }

    public function update(User $user, Rendicion $r): bool
    {
        return $user->jass_id === $r->jass_id
            && in_array($user->rol, [Rol::Presidente, Rol::Tesorero], true)
            && $r->esEditable();
    }

    public function aprobar(User $user, Rendicion $r): bool
    {
        return $user->jass_id === $r->jass_id
            && $user->rol === Rol::Presidente
            && $r->esEditable();
    }
}
