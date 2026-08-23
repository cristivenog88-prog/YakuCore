<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAJass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de auditoría append-only (spec §6.12): sin rutas de update/delete,
 * AuditoriaPolicy niega toda mutación, y solo se escribe vía RegistradorAuditoria.
 */
class Auditoria extends Model
{
    use PerteneceAJass;

    protected $table = 'auditoria';

    const UPDATED_AT = null;

    protected $fillable = [
        'jass_id',
        'usuario_id',
        'accion',
        'entidad_tipo',
        'entidad_id',
        'detalles',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'detalles' => 'array',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
