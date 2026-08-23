<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAJass;
use Database\Factories\SocioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Socio extends Model
{
    /** @use HasFactory<SocioFactory> */
    use HasFactory, PerteneceAJass, SoftDeletes;

    protected $fillable = [
        'nombres',
        'apellidos',
        'dni',
        'telefono',
        'direccion',
    ];

    public function viviendas(): HasMany
    {
        return $this->hasMany(Vivienda::class);
    }
}
