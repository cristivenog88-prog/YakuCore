<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Todo modelo cuyas rutas usan un {parametro} en la URL (spec 7.1, buenas
 * prácticas de seguridad): el id autoincremental nunca se expone en una
 * URL — revela el correlativo real de registros y permite enumerarlos
 * cambiando el número. El route model binding usa `uuid` en su lugar
 * (getRouteKeyName), generado automáticamente al crear el registro.
 */
trait SeEnlazaPorUuid
{
    protected static function bootSeEnlazaPorUuid(): void
    {
        static::creating(function ($model): void {
            $model->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
