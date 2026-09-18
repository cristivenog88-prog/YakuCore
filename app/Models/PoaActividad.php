<?php

namespace App\Models;

use App\Enums\CategoriaActividadPoa;
use App\Models\Concerns\PerteneceAJass;
use Database\Factories\PoaActividadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una actividad del POA (guía POA, paso 1): qué se hará, con qué frecuencia
 * anual, a qué costo unitario, en qué mes y quién es el responsable. Su
 * subtotal (frecuencia_anual × costo_unitario) nunca se guarda — lo calcula
 * siempre Services/Poa/CalculadoraCuota (CLAUDE.md #2).
 */
class PoaActividad extends Model
{
    /** @use HasFactory<PoaActividadFactory> */
    use HasFactory, PerteneceAJass;

    protected $table = 'poa_actividades';

    protected $fillable = [
        'poa_id',
        'categoria',
        'descripcion',
        'frecuencia_anual',
        'costo_unitario',
        'mes',
        'responsable',
    ];

    protected function casts(): array
    {
        return [
            'categoria' => CategoriaActividadPoa::class,
            'frecuencia_anual' => 'decimal:2',
            'costo_unitario' => 'decimal:2',
        ];
    }

    public function poa(): BelongsTo
    {
        return $this->belongsTo(Poa::class);
    }

    public function subtotal(): float
    {
        return (float) $this->frecuencia_anual * (float) $this->costo_unitario;
    }
}
