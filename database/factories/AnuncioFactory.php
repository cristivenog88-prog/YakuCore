<?php

namespace Database\Factories;

use App\Enums\EstadoAnuncio;
use App\Models\Anuncio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Anuncio>
 */
class AnuncioFactory extends Factory
{
    protected $model = Anuncio::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::factory()->presidente(),
            'titulo' => fake()->sentence(4),
            'contenido' => fake()->paragraph(),
            'fecha_publicacion' => now()->toDateString(),
            'estado' => EstadoAnuncio::Visible,
        ];
    }
}
