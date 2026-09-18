<?php

namespace Database\Factories;

use App\Models\Conciliacion;
use App\Models\Jass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conciliacion>
 */
class ConciliacionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'jass_id' => Jass::factory(),
            'periodo' => now()->format('Y-m'),
            'pagos_total' => 0,
            'gastos_total' => 0,
            'saldo_calculado' => 0,
            'efectivo_verificado' => 0,
            'banco_verificado' => 0,
            'fondos_verificados' => 0,
            'diferencia' => 0,
            'estado' => 'revisar',
        ];
    }
}
