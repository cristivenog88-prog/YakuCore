<?php

namespace App\Policies;

use App\Models\Gasto;
use App\Models\User;

/**
 * Gasto (spec §3): presidente y tesorero registran/editan/eliminan; vocal y
 * superadmin solo lectura (superadmin: solo-lectura en módulos operativos,
 * CLAUDE.md #8). `view` también gobierna la descarga del comprobante — el
 * controller de descarga la reutiliza (jamás una URL pública, spec §7.1).
 * `update` además exige que no hayan pasado más de 24h desde el registro
 * (Gasto::puedeEditarse) — pasado ese plazo el comprobante queda cerrado
 * para evitar manipulación posterior.
 */
class GastoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Gasto $gasto): bool
    {
        if ($user->esSuperadmin()) {
            return true;
        }

        return $user->jass_id === $gasto->jass_id;
    }

    public function create(User $user): bool
    {
        return $user->esPresidente() || $user->esTesorero();
    }

    public function update(User $user, Gasto $gasto): bool
    {
        if (! ($user->esPresidente() || $user->esTesorero())) {
            return false;
        }

        if ($user->jass_id !== $gasto->jass_id) {
            return false;
        }

        return $gasto->puedeEditarse();
    }

    public function delete(User $user, Gasto $gasto): bool
    {
        return $this->update($user, $gasto);
    }
}
