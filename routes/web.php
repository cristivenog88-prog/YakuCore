<?php

use App\Http\Controllers\Panel\AnuncioController;
use App\Http\Controllers\Panel\AsistenciaController;
use App\Http\Controllers\Panel\AuditoriaController;
use App\Http\Controllers\Panel\ConciliacionController;
use App\Http\Controllers\Panel\DashboardController as PanelDashboardController;
use App\Http\Controllers\Panel\GastoController;
use App\Http\Controllers\Panel\InformeController;
use App\Http\Controllers\Panel\MedidorController;
use App\Http\Controllers\Panel\MorosidadController;
use App\Http\Controllers\Panel\Pagos\ConsumoDirectoController;
use App\Http\Controllers\Panel\Pagos\ConsumoMedidorController;
use App\Http\Controllers\Panel\Pagos\MantenimientoController;
use App\Http\Controllers\Panel\Pagos\ReciboController;
use App\Http\Controllers\Panel\PoaController;
use App\Http\Controllers\Panel\RendicionController;
use App\Http\Controllers\Panel\ReunionController;
use App\Http\Controllers\Panel\SocioController;
use App\Http\Controllers\Panel\UsuarioController;
use App\Http\Controllers\Panel\ViviendaController;
use App\Http\Controllers\Publico\BuscadorJassController;
use App\Http\Controllers\Publico\ConsultaDeudaController;
use App\Http\Controllers\Publico\PortalJassController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\JassController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('buscar-jass', [BuscadorJassController::class, 'index'])->name('buscar-jass');
Route::get('jass/{jass}', [PortalJassController::class, 'show'])->name('portal.jass.show');
Route::get('jass/{jass}/deuda', [ConsultaDeudaController::class, 'create'])->name('portal.deuda.create');
Route::post('jass/{jass}/deuda', [ConsultaDeudaController::class, 'store'])->name('portal.deuda.store');

Route::middleware(['auth', 'rol:presidente,tesorero,fiscal,vocal', 'jass'])
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

        Route::middleware('jass.tiene-medidor')->group(function () {
            Route::post('viviendas/{vivienda}/medidor', [MedidorController::class, 'store'])->name('viviendas.medidor.store');
            Route::put('viviendas/{vivienda}/medidor/{medidor}', [MedidorController::class, 'update'])->name('viviendas.medidor.update');

            Route::post('viviendas/{vivienda}/medidor/{medidor}/reemplazar', [MedidorController::class, 'reemplazar'])
                ->middleware('password.confirm')
                ->name('viviendas.medidor.reemplazar');

            Route::get('pagos/consumo-medidor/crear', [ConsumoMedidorController::class, 'create'])->name('pagos.consumo-medidor.create');
            Route::post('pagos/consumo-medidor', [ConsumoMedidorController::class, 'store'])->name('pagos.consumo-medidor.store');
        });

        Route::get('recibos/{pago}', [ReciboController::class, 'show'])->name('recibos.show');

        Route::get('morosidad', [MorosidadController::class, 'index'])->name('morosidad.index');

        Route::get('poa', [PoaController::class, 'index'])->name('poa.index');
        Route::get('poa/crear', [PoaController::class, 'create'])->name('poa.create');
        Route::post('poa', [PoaController::class, 'store'])->name('poa.store');
        Route::get('poa/editar', [PoaController::class, 'edit'])->name('poa.edit');
        Route::put('poa', [PoaController::class, 'update'])->name('poa.update');

        Route::get('pagos/mantenimiento/crear', [MantenimientoController::class, 'create'])->name('pagos.mantenimiento.create');
        Route::post('pagos/mantenimiento', [MantenimientoController::class, 'store'])->name('pagos.mantenimiento.store');

        Route::middleware('jass.sin-medidor')->group(function () {
            Route::get('pagos/consumo-directo/crear', [ConsumoDirectoController::class, 'create'])->name('pagos.consumo-directo.create');
            Route::post('pagos/consumo-directo', [ConsumoDirectoController::class, 'store'])->name('pagos.consumo-directo.store');
        });

        Route::get('gastos', [GastoController::class, 'index'])->name('gastos.index');
        Route::get('gastos/crear', [GastoController::class, 'create'])->name('gastos.create');
        Route::post('gastos', [GastoController::class, 'store'])->name('gastos.store');
        Route::get('gastos/{gasto}/editar', [GastoController::class, 'edit'])->name('gastos.edit');
        Route::put('gastos/{gasto}', [GastoController::class, 'update'])->name('gastos.update');
        Route::get('gastos/{gasto}/comprobante', [GastoController::class, 'comprobante'])->name('gastos.comprobante');

        Route::delete('gastos/{gasto}', [GastoController::class, 'destroy'])
            ->middleware('password.confirm')
            ->name('gastos.destroy');

        Route::get('reuniones', [ReunionController::class, 'index'])->name('reuniones.index');
        Route::get('reuniones/crear', [ReunionController::class, 'create'])->name('reuniones.create');
        Route::post('reuniones', [ReunionController::class, 'store'])->name('reuniones.store');
        Route::get('reuniones/{reunion}', [ReunionController::class, 'show'])->name('reuniones.show');
        Route::get('reuniones/{reunion}/editar', [ReunionController::class, 'edit'])->name('reuniones.edit');
        Route::put('reuniones/{reunion}', [ReunionController::class, 'update'])->name('reuniones.update');
        Route::post('reuniones/{reunion}/cancelar', [ReunionController::class, 'cancelar'])->name('reuniones.cancelar');

        Route::get('reuniones/{reunion}/asistencia', [AsistenciaController::class, 'create'])->name('reuniones.asistencia.create');

        Route::post('reuniones/{reunion}/asistencia', [AsistenciaController::class, 'store'])
            ->name('reuniones.asistencia.store');

        Route::get('anuncios', [AnuncioController::class, 'index'])->name('anuncios.index');
        Route::get('anuncios/crear', [AnuncioController::class, 'create'])->name('anuncios.create');
        Route::post('anuncios', [AnuncioController::class, 'store'])->name('anuncios.store');
        Route::get('anuncios/{anuncio}/editar', [AnuncioController::class, 'edit'])->name('anuncios.edit');
        Route::put('anuncios/{anuncio}', [AnuncioController::class, 'update'])->name('anuncios.update');

        Route::delete('anuncios/{anuncio}', [AnuncioController::class, 'destroy'])
            ->middleware('password.confirm')
            ->name('anuncios.destroy');

        Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');

        Route::get('conciliaciones', [ConciliacionController::class, 'index'])->name('conciliaciones.index');
        Route::get('conciliaciones/{conciliacion}', [ConciliacionController::class, 'show'])->name('conciliaciones.show');
        Route::post('conciliaciones', [ConciliacionController::class, 'store'])->name('conciliaciones.store');
        Route::post('conciliaciones/{conciliacion}/firma-tesorero', [ConciliacionController::class, 'firmarTesorero'])
            ->name('conciliaciones.firma-tesorero');
        Route::post('conciliaciones/{conciliacion}/firma-fiscal', [ConciliacionController::class, 'firmarFiscal'])
            ->name('conciliaciones.firma-fiscal');

        Route::get('rendiciones', [RendicionController::class, 'index'])->name('rendiciones.index');
        Route::get('rendiciones/{rendicion}', [RendicionController::class, 'show'])->name('rendiciones.show');
        Route::post('rendiciones', [RendicionController::class, 'store'])->name('rendiciones.store');
        Route::put('rendiciones/{rendicion}/borrador', [RendicionController::class, 'updateBorrador'])
            ->name('rendiciones.update-borrador');
        Route::post('rendiciones/{rendicion}/aprobar', [RendicionController::class, 'aprobar'])
            ->name('rendiciones.aprobar');

        Route::get('informes', [InformeController::class, 'index'])->name('informes.index');
        Route::post('informes/{rendicion}', [InformeController::class, 'store'])->name('informes.store');
        Route::get('informes/{informe}/descargar', [InformeController::class, 'descargar'])->name('informes.descargar');

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
        Route::get('/', [SuperadminDashboardController::class, 'index'])->name('dashboard');

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
        Route::get('morosidad', [MorosidadController::class, 'index'])->name('morosidad.index');
        Route::get('poa', [PoaController::class, 'index'])->name('poa.index');
        Route::get('gastos', [GastoController::class, 'index'])->name('gastos.index');
        Route::get('gastos/{gasto}/comprobante', [GastoController::class, 'comprobante'])->name('gastos.comprobante');
        Route::get('reuniones', [ReunionController::class, 'index'])->name('reuniones.index');
        Route::get('reuniones/{reunion}', [ReunionController::class, 'show'])->name('reuniones.show');
        Route::get('anuncios', [AnuncioController::class, 'index'])->name('anuncios.index');
        Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
