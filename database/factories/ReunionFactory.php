<?php

namespace Database\Factories;

use App\Enums\EstadoReunion;
use App\Models\Reunion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reunion>
 */
class ReunionFactory extends Factory
{
    protected $model = Reunion::class;

    public function definition(): array
    {
        return [
            'titulo' => fake()->sentence(3),
            'descripcion' => fake()->paragraph(),
            'fecha' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'hora' => '18:00',
            'lugar' => fake()->streetAddress(),
            'puntos_tratar' => fake()->paragraph(),
            'estado' => EstadoReunion::Programada,
        ];
    }
}
