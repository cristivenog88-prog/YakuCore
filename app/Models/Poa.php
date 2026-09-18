<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAJass;
use Database\Factories\PoaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan Operativo Anual (guía POA): uno por JASS y año. Sus actividades
 * (paso 1) determinan el presupuesto (paso 2), del que
 * Services/Poa/CalculadoraCuota deriva la cuota mensual de mantenimiento
 * (paso 3, caso 1 — cuota única) cuando la JASS usa POA (jass.usa_poa).
 */
class Poa extends Model
{
    /** @use HasFactory<PoaFactory> */
    use HasFactory, PerteneceAJass;

    protected $table = 'poa';

    protected $fillable = [
        'anio',
        'otros_ingresos',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'otros_ingresos' => 'decimal:2',
        ];
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(PoaActividad::class);
    }
}
