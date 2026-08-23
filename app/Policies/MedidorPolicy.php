<?php

namespace App\Policies;

use App\Models\Medidor;
use App\Models\User;

/**
 * Misma matriz que SocioPolicy/ViviendaPolicy (spec §3). El acceso a
 * medidores en una JASS sin medición ya se corta antes, con 404, vía
 * AsegurarJassTieneMedidor.
 */
class MedidorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Medidor $medidor): bool
    {
        if ($user->esSuperadmin()) {
            return true;
        }

        return $user->jass_id === $medidor->jass_id;
    }

    public function create(User $user): bool
    {
        return $user->esPresidente() || $user->esTesorero();
    }

    public function update(User $user, Medidor $medidor): bool
    {
        if (! ($user->esPresidente() || $user->esTesorero())) {
            return false;
        }

        return $user->jass_id === $medidor->jass_id;
    }
}
