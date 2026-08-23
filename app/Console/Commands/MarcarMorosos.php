<?php

namespace App\Console\Commands;

use App\Models\Jass;
use App\Models\Vivienda;
use App\Services\Morosidad\EstadoCuenta;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Console\Command;

/**
 * Recalcula viviendas.morosa_desde de todas las viviendas activas de todas
 * las JASS (8.8). Idempotente: solo escribe cuando el valor cambia, para no
 * generar ruido en auditoría ni tocar updated_at sin necesidad.
 */
class MarcarMorosos extends Command
{
    protected $signature = 'yaku:marcar-morosos';

    protected $description = 'Recalcula el estado de morosidad de las viviendas de todas las JASS';

    public function __construct(
        private readonly EstadoCuenta $estadoCuenta,
        private readonly ContextoJass $contexto,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $actualizadas = 0;

        Jass::query()->each(function (Jass $jass) use (&$actualizadas) {
            $this->contexto->fijar($jass);

            Vivienda::where('activa', true)->get()->each(function (Vivienda $vivienda) use (&$actualizadas) {
                $fecha = $this->estadoCuenta->fechaMorosaDesde($vivienda);

                if (($vivienda->morosa_desde?->toDateString()) !== $fecha?->toDateString()) {
                    $vivienda->update(['morosa_desde' => $fecha]);
                    $actualizadas++;
                }
            });

            $this->contexto->limpiar();
        });

        $this->info("Morosidad recalculada. Viviendas actualizadas: {$actualizadas}.");

        return self::SUCCESS;
    }
}
