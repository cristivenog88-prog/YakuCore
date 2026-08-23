<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AsegurarRol
{
    /**
     * Permite el paso solo si el usuario autenticado tiene uno de los
     * roles indicados. Uso: ->middleware('rol:presidente,tesorero,vocal')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $rolesPermitidos = array_map(fn (string $rol) => Rol::from($rol), $roles);

        abort_unless(
            in_array($request->user()?->rol, $rolesPermitidos, strict: true),
            403
        );

        return $next($request);
    }
}
