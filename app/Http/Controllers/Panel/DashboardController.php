<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Jass;
use App\Services\Dashboard\EstadisticasJass;
use App\Services\Tenancy\ContextoJass;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly EstadisticasJass $estadisticas) {}

    /**
     * Reutilizado por /panel (presidente/tesorero/vocal) y por
     * /supervision/{jass} (superadmin, modo lectura — sección 3 del plan).
     */
    public function index(?Jass $jass = null): Response
    {
        $jassActiva = app(ContextoJass::class)->jass();

        return Inertia::render('panel/dashboard', [
            'supervisando' => $jass?->only(['id', 'uuid', 'nombre']),
            'estadisticas' => $jassActiva ? $this->estadisticas->calcular($jassActiva) : null,
        ]);
    }
}
