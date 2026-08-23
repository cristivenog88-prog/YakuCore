<?php

use App\Enums\Periodicidad;
use App\Enums\TipoPago;
use App\Models\Auditoria;
use App\Models\Jass;
use App\Models\Medidor;
use App\Models\Pago;
use App\Models\Scopes\TenantScope;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Pagos\RegistradorPago;
use App\Services\Tenancy\ContextoJass;

function prepararContexto(array $jassAtributos = []): array
{
    $jass = Jass::factory()->create($jassAtributos);
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    test()->actingAs($presidente);

    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    return compact('jass', 'presidente', 'socio', 'vivienda');
}

test('mantenimiento persiste vivienda_id (8.6) y permite 2 pagos del mismo socio en 2 viviendas para el mismo periodo', function () {
    ['socio' => $socio] = prepararContexto();
    $viviendaA = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $viviendaB = Vivienda::factory()->create(['socio_id' => $socio->id]);

    $pagoA = app(RegistradorPago::class)->mantenimiento($socio, $viviendaA, '2026-03', 10, '2026-03-05');
    $pagoB = app(RegistradorPago::class)->mantenimiento($socio, $viviendaB, '2026-03', 10, '2026-03-05');

    expect($pagoA->vivienda_id)->toBe($viviendaA->id);
    expect($pagoB->vivienda_id)->toBe($viviendaB->id);
    expect($pagoA->numero_recibo)->not->toBe($pagoB->numero_recibo);
});

test('el concepto de mantenimiento refleja la periodicidad de la JASS', function () {
    ['socio' => $socio, 'vivienda' => $vivienda] = prepararContexto(['periodicidad_mantenimiento' => Periodicidad::Trimestral]);

    $pago = app(RegistradorPago::class)->mantenimiento($socio, $vivienda, '2026-07', 15, '2026-07-01');

    expect($pago->concepto)->toBe('Mantenimiento trimestre 3 2026');
});

test('cada pago queda auditado', function () {
    ['socio' => $socio, 'vivienda' => $vivienda] = prepararContexto();

    $pago = app(RegistradorPago::class)->mantenimiento($socio, $vivienda, '2026-03', 10, '2026-03-05');

    $auditado = Auditoria::withoutGlobalScope(TenantScope::class)
        ->where('accion', 'cobrar')->where('entidad_tipo', Pago::class)->where('entidad_id', $pago->id)->exists();

    expect($auditado)->toBeTrue();
});

test('consumo_medidor solo se permite si la JASS tiene medidor', function () {
    ['socio' => $socio, 'vivienda' => $vivienda] = prepararContexto(['tiene_medidor' => false, 'tarifa_base_m3' => null]);
    $medidor = Medidor::factory()->create(['vivienda_id' => $vivienda->id]);

    app(RegistradorPago::class)->consumoMedidor($socio, $medidor, '2026-03', 10, '2026-03-05');
})->throws(InvalidArgumentException::class, 'Esta JASS no trabaja con medidores.');

test('consumo_directo solo se permite si la JASS no tiene medidor', function () {
    ['socio' => $socio, 'vivienda' => $vivienda] = prepararContexto(['tiene_medidor' => true, 'tarifa_base_m3' => 2]);

    app(RegistradorPago::class)->consumoDirecto($socio, $vivienda, '2026-03', 20, '2026-03-05');
})->throws(InvalidArgumentException::class, 'Esta JASS trabaja con medidores; el consumo se cobra por medidor.');

test('el consumo con medidor calcula el monto en servidor y persiste lecturas', function () {
    ['socio' => $socio, 'vivienda' => $vivienda] = prepararContexto(['tiene_medidor' => true, 'tarifa_base_m3' => 2.5]);
    $medidor = Medidor::factory()->create(['vivienda_id' => $vivienda->id, 'lectura_inicial' => 10]);

    $pago = app(RegistradorPago::class)->consumoMedidor($socio, $medidor, '2026-03', 20, '2026-03-05');

    expect((float) $pago->lectura_anterior)->toBe(10.0);
    expect((float) $pago->lectura_actual)->toBe(20.0);
    expect((float) $pago->monto)->toBe(25.0);
    expect($pago->tipo)->toBe(TipoPago::ConsumoMedidor);
});

test('la lectura actual no puede ser menor a la anterior (regla 5.6)', function () {
    ['socio' => $socio, 'vivienda' => $vivienda] = prepararContexto(['tiene_medidor' => true, 'tarifa_base_m3' => 2]);
    $medidor = Medidor::factory()->create(['vivienda_id' => $vivienda->id, 'lectura_inicial' => 50]);

    app(RegistradorPago::class)->consumoMedidor($socio, $medidor, '2026-03', 40, '2026-03-05');
})->throws(InvalidArgumentException::class, 'La lectura actual no puede ser menor a la lectura anterior.');

test('el consumo directo usa monto manual', function () {
    ['socio' => $socio, 'vivienda' => $vivienda] = prepararContexto(['tiene_medidor' => false, 'tarifa_base_m3' => null]);

    $pago = app(RegistradorPago::class)->consumoDirecto($socio, $vivienda, '2026-03', 18.5, '2026-03-05');

    expect((float) $pago->monto)->toBe(18.5);
    expect($pago->tipo)->toBe(TipoPago::ConsumoDirecto);
});

test('el periodo se normaliza a YYYY-MM', function () {
    ['socio' => $socio, 'vivienda' => $vivienda] = prepararContexto();

    $pago = app(RegistradorPago::class)->mantenimiento($socio, $vivienda, '2026-03', 10, '2026-03-05');

    expect($pago->periodo)->toBe('2026-03');
});

test('el servicio rechaza cobrar sobre un socio/vivienda de otra JASS aunque el caller los pase directamente (scoping cruzado)', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    app(ContextoJass::class)->fijar($jassB);
    $socioB = Socio::factory()->create();
    $viviendaB = Vivienda::factory()->create(['socio_id' => $socioB->id]);

    $tesoreroA = User::factory()->tesorero($jassA)->create();
    app(ContextoJass::class)->fijar($jassA);
    $this->actingAs($tesoreroA);

    app(RegistradorPago::class)->mantenimiento($socioB, $viviendaB, '2026-03', 10, '2026-03-05');
})->throws(InvalidArgumentException::class, 'El socio o la vivienda no pertenecen a la JASS activa.');
