<?php

namespace App\Services\Auditoria;

use App\Models\Auditoria;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Único punto de escritura de la tabla auditoria (regla dura del proyecto,
 * CLAUDE.md #9): nunca crear el modelo Auditoria directamente.
 */
class RegistradorAuditoria
{
    public function registrar(string $accion, Model $entidad, array $detalles = []): Auditoria
    {
        return Auditoria::create([
            'jass_id' => app(ContextoJass::class)->id(),
            'usuario_id' => Auth::id(),
            'accion' => $accion,
            'entidad_tipo' => $entidad::class,
            'entidad_id' => $entidad->getKey(),
            'detalles' => $detalles,
            'ip' => Request::ip(),
        ]);
    }
}
