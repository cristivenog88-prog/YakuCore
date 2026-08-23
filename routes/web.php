<?php

use App\Http\Controllers\Panel\DashboardController as PanelDashboardController;
use App\Http\Controllers\Panel\SocioController;
use App\Http\Controllers\Panel\UsuarioController;
use App\Http\Controllers\Panel\ViviendaController;
use App\Http\Controllers\Superadmin\JassController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'rol:presidente,tesorero,vocal', 'jass'])
    ->prefix('panel')
    ->name('panel.')
    ->group(function () {
        Route::get('/', [PanelDashboardController::class, 'index'])->name('dashboard');

        Route::get('socios', [SocioController::class, 'index'])->name('socios.index');
        Route::get('socios/crear', [SocioController::class, 'create'])->name('socios.create');
        Route::post('socios', [SocioController::class, 'store'])->name('socios.store');
        Route::get('socios/{socio}', [SocioController::class, 'show'])->name('socios.show');
        Route::get('socios/{socio}/editar', [SocioController::class, 'edit'])->name('socios.edit');
        Route::put('socios/{socio}', [SocioController::class, 'update'])->name('socios.update');

        Route::delete('socios/{socio}', [SocioController::class, 'destroy'])
            ->middleware('password.confirm')
            ->name('socios.destroy');

        Route::post('socios/{socio}/viviendas', [ViviendaController::class, 'store'])->name('socios.viviendas.store');
        Route::put('socios/{socio}/viviendas/{vivienda}', [ViviendaController::class, 'update'])->name('socios.viviendas.update');

        Route::delete('socios/{socio}/viviendas/{vivienda}', [ViviendaController::class, 'destroy'])
            ->middleware('password.confirm')
            ->name('socios.viviendas.destroy');

        Route::middleware('rol:presidente')->group(function () {
            Route::get('usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
            Route::get('usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
            Route::post('usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
            Route::get('usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
            Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');

            Route::delete('usuarios/{usuario}', [UsuarioController::class, 'destroy'])
                ->middleware('password.confirm')
                ->name('usuarios.destroy');
        });
    });

Route::middleware(['auth', 'rol:superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        // El panel superadmin es, por ahora, el listado de JASS con botón
        // "Supervisar" (KPIs reales en Fase 7).
        Route::get('/', [JassController::class, 'index'])->name('dashboard');

        Route::get('jass', [JassController::class, 'index'])->name('jass.index');
        Route::get('jass/crear', [JassController::class, 'create'])->name('jass.create');
        Route::post('jass', [JassController::class, 'store'])->name('jass.store');
        Route::get('jass/{jass}/editar', [JassController::class, 'edit'])->name('jass.edit');
        Route::put('jass/{jass}', [JassController::class, 'update'])->name('jass.update');

        Route::delete('jass/{jass}', [JassController::class, 'destroy'])
            ->middleware('password.confirm')
            ->name('jass.destroy');

        Route::get('jass/{jass}/usuarios', [UsuarioController::class, 'index'])->name('jass.usuarios.index');
        Route::get('jass/{jass}/usuarios/crear', [UsuarioController::class, 'create'])->name('jass.usuarios.create');
        Route::post('jass/{jass}/usuarios', [UsuarioController::class, 'store'])->name('jass.usuarios.store');
        Route::get('jass/{jass}/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('jass.usuarios.edit');
        Route::put('jass/{jass}/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('jass.usuarios.update');

        Route::delete('jass/{jass}/usuarios/{usuario}', [UsuarioController::class, 'destroy'])
            ->middleware('password.confirm')
            ->name('jass.usuarios.destroy');
    });

// Modo supervisión (superadmin): reutiliza los controllers de /panel sobre
// una JASS elegida por ruta; estructuralmente de solo lectura (sección 3).
Route::middleware(['auth', 'rol:superadmin', 'jass'])
    ->prefix('supervision/{jass}')
    ->name('supervision.')
    ->group(function () {
        Route::get('/', [PanelDashboardController::class, 'index'])->name('dashboard');
        Route::get('socios', [SocioController::class, 'index'])->name('socios.index');
        Route::get('socios/{socio}', [SocioController::class, 'show'])->name('socios.show');
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
