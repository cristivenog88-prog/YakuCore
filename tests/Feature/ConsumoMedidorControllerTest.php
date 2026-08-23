<?php

use App\Models\Jass;
use App\Models\Medidor;
use App\Models\Pago;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Tenancy\ContextoJass;

test('en una JASS sin medidor, las rutas de consumo con medidor devuelven 404', function () {
    $jass = Jass::factory()->sinMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $this->actingAs($presidente)->get(route('panel.pagos.consumo-medidor.create'))->assertNotFound();
});

test('el formulario incluye la previsualización de lectura anterior y monto calculados en servidor', function () {
    $jass = Jass::factory()->conMedidor()->create(['tarifa_base_m3' => 2.5]);
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $medidor = Medidor::factory()->create(['vivienda_id' => $vivienda->id, 'lectura_inicial' => 10]);

    $response = $this->actingAs($presidente)->get(route('panel.pagos.consumo-medidor.create', [
        'medidor_id' => $medidor->id,
        'lectura_actual' => 20,
    ]));

    $response->assertOk();
    $response->assertInertia(function ($page) {
        expect((float) $page->toArray()['props']['previsualizacion']['lectura_anterior'])->toBe(10.0);
        expect((float) $page->toArray()['props']['previsualizacion']['monto'])->toBe(25.0);
        $page->where('previsualizacion.valido', true);
    });
});

test('la previsualización marca inválida una lectura menor a la anterior, sin monto', function () {
    $jass = Jass::factory()->conMedidor()->create(['tarifa_base_m3' => 2.5]);
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $medidor = Medidor::factory()->create(['vivienda_id' => $vivienda->id, 'lectura_inicial' => 50]);

    $response = $this->actingAs($presidente)->get(route('panel.pagos.consumo-medidor.create', [
        'medidor_id' => $medidor->id,
        'lectura_actual' => 40,
    ]));

    $response->assertInertia(fn ($page) => $page
        ->where('previsualizacion.valido', false)
        ->where('previsualizacion.monto', null)
    );
});

test('presidente puede cobrar consumo con medidor y es redirigido al recibo', function () {
    $jass = Jass::factory()->conMedidor()->create(['tarifa_base_m3' => 3]);
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $medidor = Medidor::factory()->create(['vivienda_id' => $vivienda->id, 'lectura_inicial' => 5]);

    $response = $this->actingAs($presidente)->post(route('panel.pagos.consumo-medidor.store'), [
        'socio_id' => $socio->id,
        'medidor_id' => $medidor->id,
        'periodo' => '2026-04',
        'lectura_actual' => 15,
        'fecha_pago' => '2026-04-05',
    ]);

    $pago = Pago::where('medidor_id', $medidor->id)->firstOrFail();
    $response->assertRedirect(route('panel.recibos.show', $pago));
    expect((float) $pago->monto)->toBe(30.0);
});

test('el medidor debe pertenecer al socio elegido', function () {
    $jass = Jass::factory()->conMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socioA = Socio::factory()->create();
    $socioB = Socio::factory()->create();
    $viviendaB = Vivienda::factory()->create(['socio_id' => $socioB->id]);
    $medidorDeB = Medidor::factory()->create(['vivienda_id' => $viviendaB->id]);

    $response = $this->actingAs($presidente)->post(route('panel.pagos.consumo-medidor.store'), [
        'socio_id' => $socioA->id,
        'medidor_id' => $medidorDeB->id,
        'periodo' => '2026-04',
        'lectura_actual' => 15,
        'fecha_pago' => '2026-04-05',
    ]);

    $response->assertSessionHasErrors('medidor_id');
});

test('vocal no puede cobrar consumo con medidor', function () {
    $jass = Jass::factory()->conMedidor()->create();
    $vocal = User::factory()->vocal($jass)->create();

    $this->actingAs($vocal)->get(route('panel.pagos.consumo-medidor.create'))->assertForbidden();
});
