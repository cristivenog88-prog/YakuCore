<?php

use App\Models\Jass;
use App\Models\Pago;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Tenancy\ContextoJass;

test('en una JASS con medidor, las rutas de consumo sin medidor devuelven 404', function () {
    $jass = Jass::factory()->conMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $this->actingAs($presidente)->get(route('panel.pagos.consumo-directo.create'))->assertNotFound();
});

test('presidente puede cobrar consumo directo con monto manual y es redirigido al recibo', function () {
    $jass = Jass::factory()->sinMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    $response = $this->actingAs($presidente)->post(route('panel.pagos.consumo-directo.store'), [
        'socio_id' => $socio->id,
        'vivienda_id' => $vivienda->id,
        'periodo' => '2026-04',
        'monto' => '22.75',
        'fecha_pago' => '2026-04-05',
    ]);

    $pago = Pago::where('vivienda_id', $vivienda->id)->firstOrFail();
    $response->assertRedirect(route('panel.recibos.show', $pago));
    expect((float) $pago->monto)->toBe(22.75);
    expect($pago->medidor_id)->toBeNull();
});

test('la vivienda debe pertenecer al socio elegido', function () {
    $jass = Jass::factory()->sinMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socioA = Socio::factory()->create();
    $socioB = Socio::factory()->create();
    $viviendaDeB = Vivienda::factory()->create(['socio_id' => $socioB->id]);

    $response = $this->actingAs($presidente)->post(route('panel.pagos.consumo-directo.store'), [
        'socio_id' => $socioA->id,
        'vivienda_id' => $viviendaDeB->id,
        'periodo' => '2026-04',
        'monto' => '10',
        'fecha_pago' => '2026-04-05',
    ]);

    $response->assertSessionHasErrors('vivienda_id');
});

test('vocal no puede cobrar consumo directo', function () {
    $jass = Jass::factory()->sinMedidor()->create();
    $vocal = User::factory()->vocal($jass)->create();

    $this->actingAs($vocal)->get(route('panel.pagos.consumo-directo.create'))->assertForbidden();
});
