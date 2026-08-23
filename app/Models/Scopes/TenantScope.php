<?php

namespace App\Models\Scopes;

use App\Services\Tenancy\ContextoJass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filtra siempre por la JASS activa en ContextoJass. Si no hay contexto
 * fijado, no expone ningún registro (falla cerrado, nunca abierto).
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $jassId = app(ContextoJass::class)->id();

        if ($jassId === null) {
            // Sin contexto de JASS: no exponer ningún registro. Se evita
            // DB::raw/whereRaw (regla de seguridad) usando una condición
            // de clave primaria imposible en un BIGINT UNSIGNED.
            $builder->where($model->getQualifiedKeyName(), -1);

            return;
        }

        $builder->where($model->qualifyColumn('jass_id'), $jassId);
    }
}
