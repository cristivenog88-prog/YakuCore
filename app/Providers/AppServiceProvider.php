<?php

namespace App\Providers;

use App\Services\Tenancy\ContextoJass;
use Illuminate\Database\Eloquent\Model;
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
    }
}
