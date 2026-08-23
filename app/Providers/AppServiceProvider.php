<?php

namespace App\Providers;

use App\Services\Tenancy\ContextoJass;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ContextoJass::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fase 7 tarea 4: cualquier acceso a una relación no cargada explota
        // en local en vez de disparar una consulta N+1 silenciosa.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Fase 8 tarea 4 / decisión 8.5: política documentada del buscador
        // público de deuda — 3 intentos fallidos por ip|jass_id|dni, 15 min
        // de bloqueo. El conteo real solo se incrementa en los intentos
        // fallidos (Services/Publico/ConsultaDeuda), no en cada petición,
        // así que este limiter nombrado no se adjunta como middleware.
        RateLimiter::for('consulta-deuda', function (Request $request) {
            return Limit::perMinutes(15, 3)->by($request->ip().'|'.$request->route('jass')?->id.'|'.$request->input('dni'));
        });
    }
}
