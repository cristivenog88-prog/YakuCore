<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\ContextoJass;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El cobro de consumo directo (sin medidor, spec §6.7) solo existe en JASS
 * que no trabajan con medición — en una JASS con medidor estas rutas no
 * existen para el usuario (404).
 */
class AsegurarJassSinMedidor
{
    public function handle(Request $request, Closure $next): Response
    {
        $jass = app(ContextoJass::class)->jass();

        abort_if(! $jass || $jass->tiene_medidor, 404);

        return $next($request);
    }
}
