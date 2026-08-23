<?php

use App\Models\Jass;
use App\Models\Socio;
use App\Models\Vivienda;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

afterEach(function () {
    Carbon::setTestNow();
});

function crearViviendaEn(Jass $jass): Vivienda
{
    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();

    return Vivienda::factory()->create(['socio_id' => $socio->id]);
}

test('marca morosa_desde en una vivienda con periodos vencidos e impagos', function () {
    $jass = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);

    Carbon::setTestNow('2026-01-01');
    $vivienda = crearViviendaEn($jass);

    Carbon::setTestNow('2026-03-20');

    Artisan::call('yaku:marcar-morosos');

    expect($vivienda->fresh()->morosa_desde->toDateString())->toBe('2026-01-15');
});

test('limpia morosa_desde cuando la vivienda vuelve a estar al dia', function () {
    $jass = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);

    Carbon::setTestNow('2026-01-01');
    $vivienda = crearViviendaEn($jass);
    $vivienda->forceFill(['morosa_desde' => '2025-12-15'])->save();

    Carbon::setTestNow('2026-01-05');

    Artisan::call('yaku:marcar-morosos');

    expect($vivienda->fresh()->morosa_desde)->toBeNull();
});

test('es idempotente: no vuelve a escribir una vivienda ya al dia', function () {
    $jass = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);

    Carbon::setTestNow('2026-01-01');
    crearViviendaEn($jass);

    Artisan::call('yaku:marcar-morosos');
    $salida = Artisan::output();

    expect($salida)->toContain('Viviendas actualizadas: 0');
});

test('recalcula viviendas de varias jass sin mezclar los datos', function () {
    $jassA = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);
    $jassB = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);

    Carbon::setTestNow('2026-01-01');
    $viviendaA = crearViviendaEn($jassA);
    $viviendaB = crearViviendaEn($jassB);

    Carbon::setTestNow('2026-03-20');

    Artisan::call('yaku:marcar-morosos');

    expect($viviendaA->fresh()->morosa_desde)->not->toBeNull()
        ->and($viviendaA->fresh()->jass_id)->toBe($jassA->id)
        ->and($viviendaB->fresh()->morosa_desde)->not->toBeNull()
        ->and($viviendaB->fresh()->jass_id)->toBe($jassB->id);
});

test('no marca viviendas inactivas', function () {
    $jass = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);

    Carbon::setTestNow('2026-01-01');
    $vivienda = crearViviendaEn($jass);
    $vivienda->update(['activa' => false]);

    Carbon::setTestNow('2026-03-20');

    Artisan::call('yaku:marcar-morosos');

    expect($vivienda->fresh()->morosa_desde)->toBeNull();
});
