<?php

use App\Models\Jass;
use App\Models\Pago;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Tenancy\ContextoJass;

/**
 * Cuando la JASS usa POA, el monto de "Cobrar mantenimiento" lo calcula el
 * servidor desde la cuota vigente del POA (CLAUDE.md #2) — nunca se acepta
 * el monto que venga en el request.
 */
test('con POA activo, el monto se calcula desde la cuota del POA e ignora el monto enviado', function () {
    $jass = Jass::factory()->create(['tiene_medidor' => false, 'usa_poa' => true]);
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    Socio::factory()->count(1)->create(); // 2 socios en total

    $this->actingAs($presidente)->post(route('panel.poa.store'), [
        'otros_ingresos' => '0',
        'actividades' => [[
            'categoria' => 'operacion',
            'descripcion' => 'Clorar el agua',
            'frecuencia_anual' => '12',
            'costo_unitario' => '100',
            'mes' => null,
            'responsable' => null,
        ]],
    ]);

    // base 1200 + reserva 120 = 1320 neto; 2 socios → 1320 / 24 = 55.00
    $response = $this->actingAs($presidente)->post(route('panel.pagos.mantenimiento.store'), [
        'socio_id' => $socio->id,
        'vivienda_id' => $vivienda->id,
        'periodo' => '2026-03',
        'monto' => '999999', // debe ignorarse por completo
        'fecha_pago' => '2026-03-05',
    ]);

    $pago = Pago::where('vivienda_id', $vivienda->id)->firstOrFail();
    $response->assertRedirect(route('panel.recibos.show', $pago));
    expect((float) $pago->monto)->toBe(55.0);
});

test('con POA activo pero sin POA elaborado para el año, el cobro se rechaza', function () {
    $jass = Jass::factory()->create(['tiene_medidor' => false, 'usa_poa' => true]);
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    $response = $this->actingAs($presidente)->post(route('panel.pagos.mantenimiento.store'), [
        'socio_id' => $socio->id,
        'vivienda_id' => $vivienda->id,
        'periodo' => '2026-03',
        'fecha_pago' => '2026-03-05',
    ]);

    $response->assertSessionHasErrors('monto');
    expect(Pago::count())->toBe(0);
});

test('sin POA activo, el monto se sigue ingresando a mano como antes', function () {
    $jass = Jass::factory()->create(['tiene_medidor' => false, 'usa_poa' => false]);
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
    expect((float) $pago->monto)->toBe(15.0);
});
