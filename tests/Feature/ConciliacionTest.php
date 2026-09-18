<?php

use App\Enums\TipoPago;
use App\Models\Conciliacion;
use App\Models\Gasto;
use App\Models\Jass;
use App\Models\Pago;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Conciliacion\CalculadoraConciliacion;
use App\Services\Tenancy\ContextoJass;

function sembrarPagoParaConciliar(Jass $jass, string $periodo, float $monto): Pago
{
    app(ContextoJass::class)->fijar($jass);
    $tesorero = User::factory()->tesorero($jass)->create();
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    return Pago::create([
        'jass_id' => $jass->id,
        'socio_id' => $socio->id,
        'vivienda_id' => $vivienda->id,
        'usuario_id' => $tesorero->id,
        'tipo' => TipoPago::Mantenimiento,
        'numero_recibo' => Pago::where('jass_id', $jass->id)->max('numero_recibo') + 1,
        'periodo' => $periodo,
        'concepto' => 'Mantenimiento test',
        'monto' => $monto,
        'fecha_pago' => $periodo.'-15',
    ]);
}

test('conciliación queda CONCILIADO cuando fondos verificados = saldo calculado', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    sembrarPagoParaConciliar($jass, '2026-03', 100.00);
    sembrarPagoParaConciliar($jass, '2026-03', 50.00);

    $usuario = User::factory()->tesorero($jass)->create();
    Gasto::create([
        'jass_id' => $jass->id, 'usuario_id' => $usuario->id, 'descripcion' => 'x',
        'monto' => 30, 'tipo_comprobante' => 'boleta', 'fecha_compra' => '2026-03-05',
    ]);

    // pagos=150, gastos=30 → saldo calculado=120. Fondos: 120 (100 ef + 20 bk).
    $c = app(CalculadoraConciliacion::class)->handle($jass, '2026-03', 100.00, 20.00);

    expect($c->estado)->toBe('conciliado');
    expect((float) $c->diferencia)->toBe(0.0);
    expect((float) $c->pagos_total)->toBe(150.0);
    expect((float) $c->gastos_total)->toBe(30.0);
});

test('conciliación queda REVISAR cuando fondos no coinciden con saldo (dif > 0.01)', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    sembrarPagoParaConciliar($jass, '2026-03', 100.00);
    $c = app(CalculadoraConciliacion::class)->handle($jass, '2026-03', 90.00, 0);

    expect($c->estado)->toBe('revisar');
    expect((float) $c->diferencia)->toBe(-10.0);
});

test('conciliación es idempotente por periodo (updateOrCreate)', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    app(CalculadoraConciliacion::class)->handle($jass, '2026-03', 0, 0);
    app(CalculadoraConciliacion::class)->handle($jass, '2026-03', 100, 0);

    expect(Conciliacion::where('jass_id', $jass->id)->where('periodo', '2026-03')->count())->toBe(1);
});

test('tesorero puede firmar; sin firma del tesorero, fiscal recibe error', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $tesorero = User::factory()->tesorero($jass)->create();
    $fiscal = User::factory()->fiscal($jass)->create();
    $c = Conciliacion::factory()->create(['jass_id' => $jass->id, 'periodo' => '2026-03']);

    $this->actingAs($fiscal)->post(route('panel.conciliaciones.firma-fiscal', $c->uuid))->assertSessionHasErrors('firma');

    $this->actingAs($tesorero)->post(route('panel.conciliaciones.firma-tesorero', $c->uuid))->assertRedirect();
    expect($c->refresh()->firmado_tesorero_at)->not->toBeNull();

    $this->actingAs($fiscal)->post(route('panel.conciliaciones.firma-fiscal', $c->uuid))->assertRedirect();
    expect($c->refresh()->firmado_fiscal_at)->not->toBeNull();
});

test('conciliación cerrada (ambas firmas) rechaza nueva firma con 403', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $tesorero = User::factory()->tesorero($jass)->create();
    $c = Conciliacion::factory()->create([
        'jass_id' => $jass->id,
        'periodo' => '2026-03',
        'firmado_tesorero_at' => now(),
        'firmado_fiscal_at' => now(),
    ]);

    $this->actingAs($tesorero)->post(route('panel.conciliaciones.firma-tesorero', $c->uuid))->assertStatus(403);
});

test('vocal no puede firmar como fiscal', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $vocal = User::factory()->vocal($jass)->create();
    $c = Conciliacion::factory()->create(['jass_id' => $jass->id, 'periodo' => '2026-03']);

    $this->actingAs($vocal)->post(route('panel.conciliaciones.firma-fiscal', $c->uuid))->assertStatus(403);
});

test('scoping cruzado: una JASS no puede firmar la conciliación de otra', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    app(ContextoJass::class)->fijar($jassB);
    $conciliacionB = Conciliacion::factory()->create(['jass_id' => $jassB->id, 'periodo' => '2026-03']);

    app(ContextoJass::class)->fijar($jassA);
    $tesoreroA = User::factory()->tesorero($jassA)->create();

    $this->actingAs($tesoreroA)->post(route('panel.conciliaciones.firma-tesorero', $conciliacionB->uuid))
        ->assertStatus(404); // el scope global no expone la conciliación de otra JASS
});
