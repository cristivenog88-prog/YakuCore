<?php

use App\Enums\TipoPago;
use App\Models\Gasto;
use App\Models\Jass;
use App\Models\Pago;
use App\Models\Rendicion;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Rendicion\GeneradorRendicion;
use App\Services\Tenancy\ContextoJass;

function sembrarPagoR(Jass $jass, string $fechaPago, float $monto): Pago
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
        'periodo' => substr($fechaPago, 0, 7),
        'concepto' => 'Test',
        'monto' => $monto,
        'fecha_pago' => $fechaPago,
    ]);
}

test('generar rendición Q1 consolida solo pagos y gastos del trimestre', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $tesorero = User::factory()->tesorero($jass)->create();

    // Q1 (ene-mar): 2 pagos (10 + 20 = 30)
    sembrarPagoR($jass, '2026-01-10', 10);
    sembrarPagoR($jass, '2026-03-15', 20);
    // Q2: 1 pago que NO debe contarse
    sembrarPagoR($jass, '2026-04-05', 50);

    // Gastos Q1: uno operacion (5), uno mantenimiento (3)
    Gasto::create(['jass_id' => $jass->id, 'usuario_id' => $tesorero->id, 'descripcion' => 'x', 'monto' => 5, 'tipo_comprobante' => 'boleta', 'fecha_compra' => '2026-02-01', 'categoria' => 'operacion']);
    Gasto::create(['jass_id' => $jass->id, 'usuario_id' => $tesorero->id, 'descripcion' => 'x', 'monto' => 3, 'tipo_comprobante' => 'boleta', 'fecha_compra' => '2026-03-01', 'categoria' => 'mantenimiento']);

    $r = app(GeneradorRendicion::class)->handle($jass, 2026, 1);

    expect((float) $r->ingresos_pagos)->toBe(30.0);
    expect((float) $r->egresos_total)->toBe(8.0);
    expect((float) $r->ejecucion_por_categoria['operacion'])->toBe(5.0);
    expect((float) $r->ejecucion_por_categoria['mantenimiento'])->toBe(3.0);
    expect((float) $r->saldo_final)->toBe(22.0); // 0 + 30 - 8
});

test('generar rendición es idempotente por jass+anio+trimestre', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    app(GeneradorRendicion::class)->handle($jass, 2026, 1);
    app(GeneradorRendicion::class)->handle($jass, 2026, 1);

    expect(Rendicion::where('jass_id', $jass->id)->count())->toBe(1);
});

test('trimestre fuera de 1..4 lanza excepción', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    expect(fn () => app(GeneradorRendicion::class)->handle($jass, 2026, 5))
        ->toThrow(InvalidArgumentException::class);
});

test('aprobar requiere checklist completo y fecha de asamblea', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $r = Rendicion::factory()->create(['jass_id' => $jass->id]);

    // sin checklist
    $this->actingAs($presidente)->post(route('panel.rendiciones.aprobar', $r->uuid), ['numero_acta' => 'X'])
        ->assertSessionHasErrors('checklist');

    // checklist completo pero sin fecha
    $r->update(['checklist' => [
        'libros_al_dia' => true,
        'recibos_archivados' => true,
        'saldo_bancario_conciliado' => true,
        'inventario_actualizado' => true,
        'informe_fiscal' => true,
    ]]);
    $this->actingAs($presidente)->post(route('panel.rendiciones.aprobar', $r->uuid), ['numero_acta' => 'X'])
        ->assertSessionHasErrors('fecha_asamblea');

    // ambas condiciones cumplidas → OK
    $r->update(['fecha_asamblea' => '2026-04-05']);
    $this->actingAs($presidente)->post(route('panel.rendiciones.aprobar', $r->uuid), ['numero_acta' => 'ACTA-Q1'])
        ->assertRedirect();

    expect($r->refresh()->estado)->toBe('aprobada');
});

test('tesorero no puede aprobar (solo presidente)', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $tesorero = User::factory()->tesorero($jass)->create();
    $r = Rendicion::factory()->create([
        'jass_id' => $jass->id,
        'fecha_asamblea' => '2026-04-05',
        'checklist' => [
            'libros_al_dia' => true, 'recibos_archivados' => true, 'saldo_bancario_conciliado' => true,
            'inventario_actualizado' => true, 'informe_fiscal' => true,
        ],
    ]);

    $this->actingAs($tesorero)->post(route('panel.rendiciones.aprobar', $r->uuid), ['numero_acta' => 'X'])
        ->assertStatus(403);
});

test('vocal no puede generar ni ver rendiciones de otra JASS', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    app(ContextoJass::class)->fijar($jassB);
    $rB = Rendicion::factory()->create(['jass_id' => $jassB->id]);

    app(ContextoJass::class)->fijar($jassA);
    $vocalA = User::factory()->vocal($jassA)->create();

    $this->actingAs($vocalA)->post(route('panel.rendiciones.store'), ['anio' => 2026, 'trimestre' => 1])
        ->assertStatus(403);
    $this->actingAs($vocalA)->get(route('panel.rendiciones.show', $rB->uuid))
        ->assertStatus(404); // scope global oculta la rendición de otra JASS
});

test('rendición aprobada no acepta cambios en el borrador', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $tesorero = User::factory()->tesorero($jass)->create();
    $r = Rendicion::factory()->create([
        'jass_id' => $jass->id,
        'estado' => 'aprobada',
        'aprobada_at' => now(),
    ]);

    $this->actingAs($tesorero)->put(route('panel.rendiciones.update-borrador', $r->uuid), ['fecha_asamblea' => '2026-04-05'])
        ->assertStatus(403);
});
