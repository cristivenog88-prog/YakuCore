<?php

namespace Database\Factories;

use App\Models\Socio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Socio>
 */
class SocioFactory extends Factory
{
    protected $model = Socio::class;

    public function definition(): array
    {
        return [
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName().' '.fake()->lastName(),
            'dni' => fake()->unique()->numerify('########'),
            'telefono' => fake()->optional()->numerify('9########'),
            'direccion' => fake()->streetAddress(),
        ];
    }
}
