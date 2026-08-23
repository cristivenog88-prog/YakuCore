<?php

namespace App\Models;

use App\Enums\Periodicidad;
use Database\Factories\JassFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Jass extends Model
{
    /** @use HasFactory<JassFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'jass';

    protected $fillable = [
        'nombre',
        'comunidad',
        'distrito',
        'tiene_medidor',
        'tarifa_base_m3',
        'periodicidad_mantenimiento',
        'dia_vencimiento',
    ];

    protected function casts(): array
    {
        return [
            'tiene_medidor' => 'boolean',
            'tarifa_base_m3' => 'decimal:2',
            'periodicidad_mantenimiento' => Periodicidad::class,
            'dia_vencimiento' => 'integer',
            'ultimo_numero_recibo' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $jass): void {
            if (blank($jass->prefijo)) {
                $jass->prefijo = static::generarPrefijoUnico($jass->nombre);
            }
        });
    }

    /**
     * Genera el prefijo autogenerado de la JASS a partir de su nombre
     * (sin espacios, ej. "Huaraz" → "JASSHuaraz"), garantizando unicidad
     * si dos nombres distintos normalizan al mismo prefijo.
     */
    protected static function generarPrefijoUnico(string $nombre): string
    {
        $base = 'JASS'.Str::studly($nombre);

        $prefijo = $base;
        $sufijo = 2;

        while (static::withTrashed()->where('prefijo', $prefijo)->exists()) {
            $prefijo = $base.$sufijo;
            $sufijo++;
        }

        return $prefijo;
    }
}
