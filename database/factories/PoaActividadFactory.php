<?php

namespace Database\Factories;

use App\Enums\CategoriaActividadPoa;
use App\Models\Poa;
use App\Models\PoaActividad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PoaActividad>
 */
class PoaActividadFactory extends Factory
{
    protected $model = PoaActividad::class;

    public function definition(): array
    {
        return [
            'poa_id' => Poa::factory(),
            'categoria' => fake()->randomElement(CategoriaActividadPoa::cases()),
            'descripcion' => fake()->sentence(4),
            'frecuencia_anual' => fake()->numberBetween(1, 12),
            'costo_unitario' => fake()->randomFloat(2, 5, 200),
            'mes' => null,
            'responsable' => null,
        ];
    }
}
