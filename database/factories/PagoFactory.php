<?php

namespace Database\Factories;

use App\Enums\TipoPago;
use App\Models\Pago;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    protected $model = Pago::class;

    public function definition(): array
    {
        return [
            'socio_id' => Socio::factory(),
            'vivienda_id' => Vivienda::factory(),
            'usuario_id' => User::factory()->presidente(),
            'tipo' => TipoPago::Mantenimiento,
            'numero_recibo' => fake()->unique()->numberBetween(1, 999999),
            'periodo' => now()->format('Y-m'),
            'concepto' => 'Mantenimiento',
            'monto' => fake()->randomFloat(2, 5, 50),
            'fecha_pago' => now()->toDateString(),
        ];
    }
}
