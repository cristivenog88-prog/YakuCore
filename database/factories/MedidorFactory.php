<?php

namespace Database\Factories;

use App\Models\Medidor;
use App\Models\Vivienda;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medidor>
 */
class MedidorFactory extends Factory
{
    protected $model = Medidor::class;

    public function definition(): array
    {
        return [
            'vivienda_id' => Vivienda::factory(),
            'correlativo' => fake()->unique()->numberBetween(1, 99999),
            'lectura_inicial' => 0,
            'reiniciado_en' => null,
            'activo' => true,
            'fecha_instalacion' => fake()->date(),
        ];
    }
}
