<?php

namespace Database\Factories;

use App\Models\Poa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Poa>
 */
class PoaFactory extends Factory
{
    protected $model = Poa::class;

    public function definition(): array
    {
        return [
            'anio' => now()->year,
            'otros_ingresos' => 0,
        ];
    }
}
