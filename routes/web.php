<?php

use App\Http\Controllers\Panel\DashboardController as PanelDashboardController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'rol:presidente,tesorero,vocal'])
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
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
