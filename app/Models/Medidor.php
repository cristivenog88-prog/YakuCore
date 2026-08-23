<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAJass;
use Database\Factories\MedidorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medidor extends Model
{
    /** @use HasFactory<MedidorFactory> */
    use HasFactory, PerteneceAJass;

    protected $table = 'medidores';

    protected $fillable = [
        'vivienda_id',
        'correlativo',
        'lectura_inicial',
        'reiniciado_en',
        'activo',
        'fecha_instalacion',
    ];

    protected function casts(): array
    {
        return [
            'correlativo' => 'integer',
            'lectura_inicial' => 'decimal:2',
            'reiniciado_en' => 'datetime',
            'activo' => 'boolean',
            'fecha_instalacion' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $medidor): void {
            if (blank($medidor->codigo)) {
                $jass = Jass::findOrFail($medidor->jass_id);
                $medidor->codigo = "{$jass->prefijo}-MED-{$medidor->correlativo}";
            }
        });
    }

    public function vivienda(): BelongsTo
    {
        return $this->belongsTo(Vivienda::class);
    }
}
