<?php

use App\Models\Jass;
use App\Models\Pago;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Tenancy\ContextoJass;

test('presidente puede cobrar mantenimiento y es redirigido al recibo', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    $response = $this->actingAs($presidente)->post(route('panel.pagos.mantenimiento.store'), [
        'socio_id' => $socio->id,
        'vivienda_id' => $vivienda->id,
        'periodo' => '2026-03',
        'monto' => '15.00',
        'fecha_pago' => '2026-03-05',
    ]);

    $pago = Pago::where('vivienda_id', $vivienda->id)->firstOrFail();
    $response->assertRedirect(route('panel.recibos.show', $pago));
    expect($pago->numero_recibo)->toBe(1);
});

test('la vivienda debe pertenecer al socio elegido', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socioA = Socio::factory()->create();
    $socioB = Socio::factory()->create();
    $viviendaDeB = Vivienda::factory()->create(['socio_id' => $socioB->id]);

    $response = $this->actingAs($presidente)->post(route('panel.pagos.mantenimiento.store'), [
        'socio_id' => $socioA->id,
        'vivienda_id' => $viviendaDeB->id,
        'periodo' => '2026-03',
        'monto' => '15.00',
        'fecha_pago' => '2026-03-05',
    ]);

    $response->assertSessionHasErrors('vivienda_id');
});

test('vocal no puede cobrar mantenimiento', function () {
    $jass = Jass::factory()->create();
    $vocal = User::factory()->vocal($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    $this->actingAs($vocal)->get(route('panel.pagos.mantenimiento.create'))->assertForbidden();
    $this->actingAs($vocal)->post(route('panel.pagos.mantenimiento.store'), [
        'socio_id' => $socio->id, 'vivienda_id' => $vivienda->id, 'periodo' => '2026-03', 'monto' => 10, 'fecha_pago' => '2026-03-05',
    ])->assertForbidden();
});

test('tesorero de JASS A no puede cobrar sobre socios/viviendas de JASS B', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    app(ContextoJass::class)->fijar($jassB);
    $socioB = Socio::factory()->create();
    $viviendaB = Vivienda::factory()->create(['socio_id' => $socioB->id]);

    $tesoreroA = User::factory()->tesorero($jassA)->create();

    $response = $this->actingAs($tesoreroA)->post(route('panel.pagos.mantenimiento.store'), [
        'socio_id' => $socioB->id,
        'vivienda_id' => $viviendaB->id,
        'periodo' => '2026-03',
        'monto' => '15.00',
        'fecha_pago' => '2026-03-05',
    ]);

    $response->assertSessionHasErrors('socio_id');
});
