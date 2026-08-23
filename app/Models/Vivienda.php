<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAJass;
use Database\Factories\ViviendaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vivienda extends Model
{
    /** @use HasFactory<ViviendaFactory> */
    use HasFactory, PerteneceAJass, SoftDeletes;

    protected $fillable = [
        'socio_id',
        'direccion',
        'activa',
        'morosa_desde',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'morosa_desde' => 'date',
        ];
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }

    /**
     * 0..1 medidor por vivienda (regla 5.2); la invariante la garantiza el
     * servicio de creación de medidores, no una constraint de base de datos.
     */
    public function medidor(): HasOne
    {
        return $this->hasOne(Medidor::class);
    }
}
