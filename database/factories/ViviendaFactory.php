<?php

namespace Database\Factories;

use App\Models\Socio;
use App\Models\Vivienda;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vivienda>
 */
class ViviendaFactory extends Factory
{
    protected $model = Vivienda::class;

    public function definition(): array
    {
        return [
            'socio_id' => Socio::factory(),
            'direccion' => fake()->streetAddress(),
            'activa' => true,
            'morosa_desde' => null,
        ];
    }
}
