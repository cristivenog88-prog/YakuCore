<?php

namespace App\Models;

use App\Enums\EstadoAsistencia;
use App\Models\Concerns\PerteneceAJass;
use Database\Factories\AsistenciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inmutable por rutas (CLAUDE.md #4): una vez tomada la asistencia no se
 * edita ni se elimina. Se crea exclusivamente vía
 * Services/Reuniones/RegistradorAsistencia, dentro de una transacción junto
 * a la transición de la reunión a `finalizada`.
 */
class Asistencia extends Model
{
    /** @use HasFactory<AsistenciaFactory> */
    use HasFactory, PerteneceAJass;

    protected $fillable = [
        'reunion_id',
        'socio_id',
        'usuario_id',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoAsistencia::class,
        ];
    }

    public function reunion(): BelongsTo
    {
        return $this->belongsTo(Reunion::class);
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
