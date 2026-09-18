<?php

namespace Database\Factories;

use App\Models\Informe;
use App\Models\Rendicion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Informe>
 */
class InformeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'rendicion_id' => Rendicion::factory(),
            'jass_id' => fn (array $attrs) => Rendicion::find($attrs['rendicion_id'])->jass_id,
            'numero' => 1,
            'anio' => (int) now()->format('Y'),
        ];
    }
}
