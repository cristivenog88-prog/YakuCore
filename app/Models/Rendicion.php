<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAJass;
use App\Models\Concerns\SeEnlazaPorUuid;
use Database\Factories\RendicionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rendicion extends Model
{
    /** @use HasFactory<RendicionFactory> */
    use HasFactory, PerteneceAJass, SeEnlazaPorUuid;

    protected $table = 'rendiciones';

    protected $fillable = [
        'jass_id',
        'poa_id',
        'trimestre',
        'anio',
        'fecha_asamblea',
        'saldo_inicial',
        'ingresos_pagos',
        'otros_ingresos',
        'ejecucion_por_categoria',
        'egresos_total',
        'saldo_final',
        'checklist',
        'estado',
        'aprobada_at',
        'numero_acta',
        'aprobada_por_user_id',
    ];

    /**
     * @return BelongsTo<Poa, $this>
     */
    public function poa(): BelongsTo
    {
        return $this->belongsTo(Poa::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function aprobadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobada_por_user_id');
    }

    public function esEditable(): bool
    {
        return $this->estado === 'borrador';
    }

    protected function casts(): array
    {
        return [
            'trimestre' => 'integer',
            'anio' => 'integer',
            'fecha_asamblea' => 'date',
            'saldo_inicial' => 'decimal:2',
            'ingresos_pagos' => 'decimal:2',
            'otros_ingresos' => 'decimal:2',
            'ejecucion_por_categoria' => 'array',
            'egresos_total' => 'decimal:2',
            'saldo_final' => 'decimal:2',
            'checklist' => 'array',
            'aprobada_at' => 'datetime',
        ];
    }
}
