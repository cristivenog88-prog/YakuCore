<?php

namespace App\Models;

use App\Enums\TipoPago;
use App\Models\Concerns\PerteneceAJass;
use Database\Factories\PagoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inmutable por rutas (CLAUDE.md #4): un pago emitido no se edita ni se
 * elimina. Se crea exclusivamente vía Services/Pagos/RegistradorPago,
 * dentro de una transacción junto al correlativo de recibo.
 */
class Pago extends Model
{
    /** @use HasFactory<PagoFactory> */
    use HasFactory, PerteneceAJass;

    protected $fillable = [
        'socio_id',
        'vivienda_id',
        'medidor_id',
        'usuario_id',
        'tipo',
        'numero_recibo',
        'periodo',
        'concepto',
        'lectura_anterior',
        'lectura_actual',
        'monto',
        'fecha_pago',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoPago::class,
            'numero_recibo' => 'integer',
            'lectura_anterior' => 'decimal:2',
            'lectura_actual' => 'decimal:2',
            'monto' => 'decimal:2',
            'fecha_pago' => 'date',
        ];
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }

    public function vivienda(): BelongsTo
    {
        return $this->belongsTo(Vivienda::class);
    }

    public function medidor(): BelongsTo
    {
        return $this->belongsTo(Medidor::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
