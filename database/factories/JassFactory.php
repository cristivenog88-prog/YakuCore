<?php

namespace Database\Factories;

use App\Enums\Periodicidad;
use App\Models\Jass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jass>
 */
class JassFactory extends Factory
{
    protected $model = Jass::class;

    public function definition(): array
    {
        return [
            'nombre' => ucfirst(fake()->unique()->city()),
            'comunidad' => fake()->city(),
            'distrito' => fake()->city(),
            'tiene_medidor' => true,
            'tarifa_base_m3' => fake()->randomFloat(2, 1, 5),
            'periodicidad_mantenimiento' => fake()->randomElement(Periodicidad::cases()),
            'dia_vencimiento' => fake()->numberBetween(1, 28),
        ];
    }

    public function conMedidor(): static
    {
        return $this->state(fn () => [
            'tiene_medidor' => true,
            'tarifa_base_m3' => fake()->randomFloat(2, 1, 5),
        ]);
    }

    public function sinMedidor(): static
    {
        return $this->state(fn () => [
            'tiene_medidor' => false,
            'tarifa_base_m3' => null,
        ]);
    }
}
