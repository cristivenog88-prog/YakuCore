<?php

use App\Http\Controllers\Panel\DashboardController as PanelDashboardController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('panel', [PanelDashboardController::class, 'index'])->name('panel.dashboard');
    Route::get('superadmin', [SuperadminDashboardController::class, 'index'])->name('superadmin.dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
