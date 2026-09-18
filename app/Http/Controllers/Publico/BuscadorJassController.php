<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Jass;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Buscador público de JASS por nombre (spec §6.13). Consulta de solo
 * lectura con campos explícitos — nunca se serializa el modelo completo
 * (Fase 8 tarea 5).
 */
class BuscadorJassController extends Controller
{
    public function index(Request $request): Response
    {
        $busqueda = trim((string) $request->query('buscar', ''));

        $resultados = $busqueda !== ''
            ? Jass::where('nombre', 'like', "%{$busqueda}%")
                ->orderBy('nombre')
                ->get(['id', 'uuid', 'nombre', 'comunidad', 'distrito'])
            : collect();

        return Inertia::render('publico/buscar', [
            'busqueda' => $busqueda,
            'resultados' => $resultados,
        ]);
    }
}
