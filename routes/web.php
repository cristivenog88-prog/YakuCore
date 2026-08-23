<?php

use App\Http\Controllers\Panel\DashboardController as PanelDashboardController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
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
    });

Route::middleware(['auth', 'rol:superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/', [SuperadminDashboardController::class, 'index'])->name('dashboard');

        Route::get('jass', [JassController::class, 'index'])->name('jass.index');
        Route::get('jass/crear', [JassController::class, 'create'])->name('jass.create');
        Route::post('jass', [JassController::class, 'store'])->name('jass.store');
        Route::get('jass/{jass}/editar', [JassController::class, 'edit'])->name('jass.edit');
        Route::put('jass/{jass}', [JassController::class, 'update'])->name('jass.update');

        Route::delete('jass/{jass}', [JassController::class, 'destroy'])
            ->middleware('password.confirm')
            ->name('jass.destroy');
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
