<?php

namespace App\Models\Concerns;

use App\Models\Jass;
use App\Models\Scopes\TenantScope;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aplica el scoping multi-tenant (sección 3 del plan). Todo modelo de
 * negocio que use este trait: (a) solo ve/consulta registros de la JASS
 * activa en ContextoJass, y (b) asigna jass_id automáticamente al crear,
 * ignorando siempre cualquier jass_id recibido por mass assignment —
 * jamás se acepta jass_id desde el request (regla dura del proyecto).
 */
trait PerteneceAJass
{
    protected static function bootPerteneceAJass(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model): void {
            $model->jass_id = app(ContextoJass::class)->id();
        });
    }

    public function jass(): BelongsTo
    {
        return $this->belongsTo(Jass::class);
    }
}
