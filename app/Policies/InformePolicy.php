<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Informe;
use App\Models\User;

/**
 * Informe de Gestión Financiera: lo genera el Consejo Directivo desde una
 * Rendición aprobada; todos los roles de la JASS pueden verlo/descargarlo;
 * superadmin cross-JASS. No hay edición ni eliminación (PDF snapshot).
 */
class InformePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::Superadmin, Rol::Presidente, Rol::Tesorero, Rol::Fiscal, Rol::Vocal], true);
    }

    public function view(User $user, Informe $informe): bool
    {
        return $user->esSuperadmin() || $user->jass_id === $informe->jass_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, [Rol::Presidente, Rol::Tesorero], true);
    }
}
