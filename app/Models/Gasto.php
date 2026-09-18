<?php

namespace App\Models;

use App\Enums\CategoriaActividadPoa;
use App\Enums\TipoComprobante;
use App\Models\Concerns\PerteneceAJass;
use App\Models\Concerns\SeEnlazaPorUuid;
use Database\Factories\GastoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gasto extends Model
{
    /** @use HasFactory<GastoFactory> */
    use HasFactory, PerteneceAJass, SeEnlazaPorUuid, SoftDeletes;

    protected $fillable = [
        'usuario_id',
        'descripcion',
        'proveedor',
        'fecha_compra',
        'monto',
        'categoria',
        'tipo_comprobante',
        'foto_path',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_compra' => 'date',
            'tipo_comprobante' => TipoComprobante::class,
            'categoria' => CategoriaActividadPoa::class,
        ];
    }

    /**
     * Ventana de edición de 24 horas desde el registro (evita manipular un
     * comprobante después de que ya se consideró parte de la contabilidad
     * cerrada del día).
     */
    public function puedeEditarse(): bool
    {
        return $this->created_at->copy()->addDay()->isFuture();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
