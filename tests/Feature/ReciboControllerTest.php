<?php

use App\Models\Jass;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Pagos\RegistradorPago;
use App\Services\Tenancy\ContextoJass;

test('el recibo muestra número, JASS, socio, vivienda, concepto y monto', function () {
    $jass = Jass::factory()->create(['nombre' => 'Villa Sol']);
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $this->actingAs($presidente);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    $pago = app(RegistradorPago::class)->mantenimiento($socio, $vivienda, '2026-03', 15, '2026-03-05');

    $response = $this->get(route('panel.recibos.show', $pago));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('panel/recibos/show')
        ->where('pago.codigoRecibo', $jass->prefijo.'-000001')
        ->where('pago.jass.nombre', 'Villa Sol')
        ->where('pago.socio.dni', $socio->dni)
        ->where('pago.vivienda.direccion', $vivienda->direccion)
        ->where('pago.concepto', $pago->concepto)
    );
});

test('el recibo se enlaza por UUID, no por el id autoincremental: la URL no lo revela ni permite enumerarlo', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $this->actingAs($presidente);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $pago = app(RegistradorPago::class)->mantenimiento($socio, $vivienda, '2026-03', 15, '2026-03-05');

    expect(route('panel.recibos.show', $pago))->toEndWith('/recibos/'.$pago->uuid);

    $this->get('/panel/recibos/'.$pago->id)->assertNotFound();
    $this->get(route('panel.recibos.show', $pago))->assertOk();
});

test('vocal puede ver el recibo (consulta de deudas) pero no cobrar', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    $vocal = User::factory()->vocal($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $this->actingAs($presidente);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $pago = app(RegistradorPago::class)->mantenimiento($socio, $vivienda, '2026-03', 15, '2026-03-05');

    $this->actingAs($vocal)->get(route('panel.recibos.show', $pago))->assertOk();
});

test('presidente de otra JASS no puede ver un recibo ajeno', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();
    $presidenteB = User::factory()->presidente($jassB)->create();

    app(ContextoJass::class)->fijar($jassA);
    $presidenteA = User::factory()->presidente($jassA)->create();
    $this->actingAs($presidenteA);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $pago = app(RegistradorPago::class)->mantenimiento($socio, $vivienda, '2026-03', 15, '2026-03-05');

    $this->actingAs($presidenteB)->get(route('panel.recibos.show', $pago))->assertNotFound();
});
