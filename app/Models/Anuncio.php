<?php

namespace App\Models;

use App\Enums\EstadoAnuncio;
use App\Models\Concerns\PerteneceAJass;
use Database\Factories\AnuncioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Anuncio extends Model
{
    /** @use HasFactory<AnuncioFactory> */
    use HasFactory, PerteneceAJass, SoftDeletes;

    protected $fillable = [
        'usuario_id',
        'titulo',
        'contenido',
        'fecha_publicacion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_publicacion' => 'date',
            'estado' => EstadoAnuncio::class,
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
