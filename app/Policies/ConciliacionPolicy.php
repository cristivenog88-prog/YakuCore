<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Conciliacion;
use App\Models\User;

/**
 * Doble firma (R.M. N.° 205-2010-VIVIENDA §): la conciliación se prepara y
 * firma por el Tesorero, y se contrafirma por el Fiscal. Nadie puede
 * modificar una conciliación cerrada (ambas firmas puestas).
 * Superadmin es solo-lectura en el flujo operativo (spec CLAUDE.md §8).
 */
class ConciliacionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [Rol::Superadmin, Rol::Presidente, Rol::Tesorero, Rol::Fiscal, Rol::Vocal], true);
    }

    public function view(User $user, Conciliacion $conciliacion): bool
    {
        return $user->esSuperadmin() || $user->jass_id === $conciliacion->jass_id;
    }

    public function preparar(User $user): bool
    {
        return in_array($user->rol, [Rol::Presidente, Rol::Tesorero], true);
    }

    public function firmarTesorero(User $user, Conciliacion $conciliacion): bool
    {
        return $user->jass_id === $conciliacion->jass_id
            && in_array($user->rol, [Rol::Presidente, Rol::Tesorero], true)
            && ! $conciliacion->estaCerrada();
    }

    public function firmarFiscal(User $user, Conciliacion $conciliacion): bool
    {
        return $user->jass_id === $conciliacion->jass_id
            && $user->rol === Rol::Fiscal
            && ! $conciliacion->estaCerrada();
    }
}
