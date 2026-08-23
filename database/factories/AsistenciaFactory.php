<?php

namespace Database\Factories;

use App\Enums\EstadoAsistencia;
use App\Models\Asistencia;
use App\Models\Reunion;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asistencia>
 */
class AsistenciaFactory extends Factory
{
    protected $model = Asistencia::class;

    public function definition(): array
    {
        return [
            'reunion_id' => Reunion::factory(),
            'socio_id' => Socio::factory(),
            'usuario_id' => User::factory()->presidente(),
            'estado' => fake()->randomElement(EstadoAsistencia::cases()),
        ];
    }
}
