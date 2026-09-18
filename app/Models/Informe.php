<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAJass;
use App\Models\Concerns\SeEnlazaPorUuid;
use Database\Factories\InformeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Informe extends Model
{
    /** @use HasFactory<InformeFactory> */
    use HasFactory, PerteneceAJass, SeEnlazaPorUuid;

    protected $table = 'informes';

    protected $fillable = [
        'jass_id',
        'rendicion_id',
        'numero',
        'anio',
        'pdf_path',
        'generado_at',
        'generado_por_user_id',
    ];

    /**
     * @return BelongsTo<Rendicion, $this>
     */
    public function rendicion(): BelongsTo
    {
        return $this->belongsTo(Rendicion::class);
    }

    /**
     * Código canónico "INFORME N.° NNNN-YYYY-JASS".
     *
     * @return Attribute<string, never>
     */
    protected function codigo(): Attribute
    {
        return Attribute::get(fn (): string => sprintf('INFORME N.° %04d-%d-JASS', $this->numero, $this->anio));
    }

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'anio' => 'integer',
            'generado_at' => 'datetime',
        ];
    }
}
