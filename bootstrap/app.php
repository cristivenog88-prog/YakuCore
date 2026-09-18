<?php

use App\Http\Middleware\AsegurarJassSinMedidor;
use App\Http\Middleware\AsegurarJassTieneMedidor;
use App\Http\Middleware\AsegurarRol;
use App\Http\Middleware\CabecerasSeguridad;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolverJassActual;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CabecerasSeguridad::class);

        $middleware->alias([
            'rol' => AsegurarRol::class,
            'jass' => ResolverJassActual::class,
            'jass.tiene-medidor' => AsegurarJassTieneMedidor::class,
            'jass.sin-medidor' => AsegurarJassSinMedidor::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // ResolverJassActual fija la JASS activa (ContextoJass), de la que
        // depende el TenantScope global usado por el route model binding
        // implícito ({socio}, {reunion}, {gasto}, {anuncio}...). Sin esto,
        // SubstituteBindings corre antes de que exista contexto y toda
        // ruta con binding implícito devuelve 404 (el scope falla cerrado).
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: ResolverJassActual::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
