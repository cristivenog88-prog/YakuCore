<?php

namespace App\Http\Middleware;

use App\Models\Jass;
use App\Services\Tenancy\ContextoJass;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija la JASS activa en ContextoJass (sección 3 del plan). En /panel/* usa
 * la JASS del propio usuario autenticado; en /supervision/{jass}/* (superadmin)
 * usa la JASS del segmento de ruta.
 */
class ResolverJassActual
{
    public function __construct(private readonly ContextoJass $contexto) {}

    public function handle(Request $request, Closure $next): Response
    {
        $jassDeRuta = $request->route('jass');

        $jassId = $jassDeRuta instanceof Jass
            ? $jassDeRuta->id
            : $request->user()?->jass_id;

        abort_if($jassId === null, 403);

        $this->contexto->fijar($jassId);

        return $next($request);
    }
}
