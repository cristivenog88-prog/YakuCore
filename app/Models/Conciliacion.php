<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAJass;
use App\Models\Concerns\SeEnlazaPorUuid;
use Database\Factories\ConciliacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conciliacion extends Model
{
    /** @use HasFactory<ConciliacionFactory> */
    use HasFactory, PerteneceAJass, SeEnlazaPorUuid;

    protected $table = 'conciliaciones';

    protected $fillable = [
        'jass_id',
        'periodo',
        'pagos_total',
        'gastos_total',
        'saldo_calculado',
        'efectivo_verificado',
        'banco_verificado',
        'fondos_verificados',
        'diferencia',
        'estado',
        'tesorero_user_id',
        'fiscal_user_id',
        'firmado_tesorero_at',
        'firmado_fiscal_at',
        'observaciones',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function tesorero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tesorero_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function fiscal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fiscal_user_id');
    }

    public function estaCerrada(): bool
    {
        return $this->firmado_tesorero_at !== null && $this->firmado_fiscal_at !== null;
    }

    protected function casts(): array
    {
        return [
            'pagos_total' => 'decimal:2',
            'gastos_total' => 'decimal:2',
            'saldo_calculado' => 'decimal:2',
            'efectivo_verificado' => 'decimal:2',
            'banco_verificado' => 'decimal:2',
            'fondos_verificados' => 'decimal:2',
            'diferencia' => 'decimal:2',
            'firmado_tesorero_at' => 'datetime',
            'firmado_fiscal_at' => 'datetime',
        ];
    }
}
