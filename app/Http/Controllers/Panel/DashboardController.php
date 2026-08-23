<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Jass;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Reutilizado por /panel (presidente/tesorero/vocal) y por
     * /supervision/{jass} (superadmin, modo lectura — sección 3 del plan).
     */
    public function index(?Jass $jass = null): Response
    {
        return Inertia::render('panel/dashboard', [
            'supervisando' => $jass?->only(['id', 'nombre']),
        ]);
    }
}
