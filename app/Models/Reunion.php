<?php

namespace App\Models;

use App\Enums\EstadoReunion;
use App\Models\Concerns\PerteneceAJass;
use Database\Factories\ReunionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reunion extends Model
{
    /** @use HasFactory<ReunionFactory> */
    use HasFactory, PerteneceAJass;

    protected $table = 'reuniones';

    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha',
        'hora',
        'lugar',
        'puntos_tratar',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoReunion::class,
        ];
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }
}
