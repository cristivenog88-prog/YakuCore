<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Auditoria;
use App\Models\User;

/**
 * Auditoria es append-only (spec §6.12, decisión 8.4): nadie puede crear,
 * editar ni eliminar vía policy — solo se escribe con RegistradorAuditoria.
 * Lectura: superadmin ve todo; presidente/tesorero solo su JASS; vocal sin acceso.
 */
class AuditoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return match ($user->rol) {
            Rol::Superadmin, Rol::Presidente, Rol::Tesorero, Rol::Fiscal => true,
            Rol::Vocal => false,
        };
    }

    public function view(User $user, Auditoria $auditoria): bool
    {
        if ($user->esVocal()) {
            return false;
        }

        return $user->esSuperadmin() || $user->jass_id === $auditoria->jass_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Auditoria $auditoria): bool
    {
        return false;
    }

    public function delete(User $user, Auditoria $auditoria): bool
    {
        return false;
    }

    public function restore(User $user, Auditoria $auditoria): bool
    {
        return false;
    }

    public function forceDelete(User $user, Auditoria $auditoria): bool
    {
        return false;
    }
}
