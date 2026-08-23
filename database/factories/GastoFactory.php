<?php

namespace Database\Factories;

use App\Enums\TipoComprobante;
use App\Models\Gasto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gasto>
 */
class GastoFactory extends Factory
{
    protected $model = Gasto::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::factory()->presidente(),
            'descripcion' => fake()->sentence(4),
            'monto' => fake()->randomFloat(2, 10, 500),
            'tipo_comprobante' => fake()->randomElement(TipoComprobante::cases()),
            'foto_path' => null,
        ];
    }
}
