<?php

namespace Database\Factories;

use App\Models\Jass;
use App\Models\Rendicion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Rendicion>
 */
class RendicionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'jass_id' => Jass::factory(),
            'trimestre' => 1,
            'anio' => (int) now()->format('Y'),
            'saldo_inicial' => 0,
            'ingresos_pagos' => 0,
            'otros_ingresos' => 0,
            'egresos_total' => 0,
            'saldo_final' => 0,
            'estado' => 'borrador',
        ];
    }
}
