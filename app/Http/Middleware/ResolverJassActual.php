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

        // El binding implícito de {jass} a un modelo Jass solo ocurre si el
        // método del controller declara un parámetro `Jass $jass` — varios
        // controllers reutilizados en supervisión (p. ej. index()) no lo
        // necesitan, así que resolvemos también el caso donde la ruta trae
        // el valor crudo (string/int) sin bindear.
        $jassId = match (true) {
            $jassDeRuta instanceof Jass => $jassDeRuta->id,
            $jassDeRuta !== null => Jass::where('uuid', $jassDeRuta)->firstOrFail()->id,
            default => $request->user()?->jass_id,
        };

        abort_if($jassId === null, 403);

        $this->contexto->fijar($jassId);

        return $next($request);
    }
}
