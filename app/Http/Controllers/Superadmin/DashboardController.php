<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Jass;
use App\Services\Dashboard\EstadisticasGlobales;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly EstadisticasGlobales $estadisticas) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Jass::class);

        return Inertia::render('superadmin/dashboard', $this->estadisticas->calcular());
    }
}
