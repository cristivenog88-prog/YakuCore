<?php

use App\Models\Jass;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Morosidad\EstadoCuenta;
use App\Services\Pagos\RegistradorPago;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

test('deriva periodos de mantenimiento mensuales vencidos e impagos', function () {
    $jass = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);
    app(ContextoJass::class)->fijar($jass);

    Carbon::setTestNow('2026-01-01');
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    Carbon::setTestNow('2026-03-20');

    $pendientes = app(EstadoCuenta::class)->periodosPendientes($vivienda->fresh());

    expect($pendientes['mantenimiento'])->toBe(['2026-01', '2026-02', '2026-03']);
});

test('un pago dentro del periodo lo marca como pagado y ya no aparece pendiente', function () {
    $jass = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);
    app(ContextoJass::class)->fijar($jass);

    Carbon::setTestNow('2026-01-01');
    $presidente = User::factory()->presidente($jass)->create();
    $this->actingAs($presidente);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    app(RegistradorPago::class)->mantenimiento($socio, $vivienda, '2026-02', 10, '2026-02-10');

    Carbon::setTestNow('2026-03-20');

    $pendientes = app(EstadoCuenta::class)->periodosPendientes($vivienda->fresh());

    expect($pendientes['mantenimiento'])->toBe(['2026-01', '2026-03']);
});

test('en periodicidad trimestral un pago con cualquier mes del trimestre cubre todo el periodo', function () {
    $jass = Jass::factory()->create(['periodicidad_mantenimiento' => 'trimestral', 'dia_vencimiento' => 10]);
    app(ContextoJass::class)->fijar($jass);

    Carbon::setTestNow('2026-01-05');
    $presidente = User::factory()->presidente($jass)->create();
    $this->actingAs($presidente);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    app(RegistradorPago::class)->mantenimiento($socio, $vivienda, '2026-02', 30, '2026-02-10');

    Carbon::setTestNow('2026-04-15');

    $pendientes = app(EstadoCuenta::class)->periodosPendientes($vivienda->fresh());

    expect($pendientes['mantenimiento'])->toBe([]);
});

test('no genera periodos anteriores a la fecha de registro de la vivienda', function () {
    $jass = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);
    app(ContextoJass::class)->fijar($jass);

    Carbon::setTestNow('2026-06-01');
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    Carbon::setTestNow('2026-06-20');

    $pendientes = app(EstadoCuenta::class)->periodosPendientes($vivienda->fresh());

    expect($pendientes['mantenimiento'])->toBe(['2026-06']);
});

test('deriva periodos de consumo directo cuando la jass no tiene medidor', function () {
    $jass = Jass::factory()->sinMedidor()->create(['periodicidad_mantenimiento' => 'anual', 'dia_vencimiento' => 15]);
    app(ContextoJass::class)->fijar($jass);

    Carbon::setTestNow('2026-01-01');
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    Carbon::setTestNow('2026-03-20');

    $pendientes = app(EstadoCuenta::class)->periodosPendientes($vivienda->fresh());

    expect($pendientes['consumo'])->toBe(['2026-01', '2026-02', '2026-03']);
});

test('fechaMorosaDesde devuelve el vencimiento impago mas antiguo entre mantenimiento y consumo', function () {
    $jass = Jass::factory()->sinMedidor()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);
    app(ContextoJass::class)->fijar($jass);

    Carbon::setTestNow('2026-01-01');
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    Carbon::setTestNow('2026-03-20');

    $fecha = app(EstadoCuenta::class)->fechaMorosaDesde($vivienda->fresh());

    expect($fecha)->not->toBeNull()
        ->and($fecha->toDateString())->toBe('2026-01-15');
});

test('fechaMorosaDesde es null cuando la vivienda esta al dia', function () {
    $jass = Jass::factory()->sinMedidor()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);
    app(ContextoJass::class)->fijar($jass);

    Carbon::setTestNow('2026-03-01');
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    Carbon::setTestNow('2026-03-05');

    $fecha = app(EstadoCuenta::class)->fechaMorosaDesde($vivienda->fresh());

    expect($fecha)->toBeNull();
});
