<?php

namespace App\Models;

use App\Enums\TipoComprobante;
use App\Models\Concerns\PerteneceAJass;
use Database\Factories\GastoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gasto extends Model
{
    /** @use HasFactory<GastoFactory> */
    use HasFactory, PerteneceAJass, SoftDeletes;

    protected $fillable = [
        'usuario_id',
        'descripcion',
        'monto',
        'tipo_comprobante',
        'foto_path',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'tipo_comprobante' => TipoComprobante::class,
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
