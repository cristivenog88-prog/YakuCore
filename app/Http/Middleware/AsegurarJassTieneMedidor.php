<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\ContextoJass;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En una JASS sin medidor las rutas de medidores no existen para el
 * usuario (404, no 403) — regla explícita de la Fase 2.
 */
class AsegurarJassTieneMedidor
{
    public function handle(Request $request, Closure $next): Response
    {
        $jass = app(ContextoJass::class)->jass();

        abort_if(! $jass?->tiene_medidor, 404);

        return $next($request);
    }
}
