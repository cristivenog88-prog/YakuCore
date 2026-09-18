<?php

use App\Models\Jass;
use App\Models\Poa;
use App\Models\PoaActividad;
use App\Services\Poa\CalculadoraCuota;
use App\Services\Tenancy\ContextoJass;

/**
 * Verifica la fórmula de la guía POA (pasos 2 y 3, caso 1 — cuota única):
 * presupuesto base = Σ(frecuencia × costo), reserva = 10% del base,
 * presupuesto neto = base + reserva − otros ingresos,
 * cuota mensual = presupuesto neto ÷ (12 × N° de socios).
 */
test('el presupuesto base suma frecuencia por costo unitario de cada actividad', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    $poa = Poa::factory()->create(['otros_ingresos' => 0]);
    PoaActividad::factory()->create(['poa_id' => $poa->id, 'frecuencia_anual' => 57.5, 'costo_unitario' => 18]);
    PoaActividad::factory()->create(['poa_id' => $poa->id, 'frecuencia_anual' => 12, 'costo_unitario' => 150]);

    $calculadora = app(CalculadoraCuota::class);

    expect($calculadora->presupuestoBase($poa))->toBe(1035.0 + 1800.0);
});

test('la reserva es el 10% del presupuesto base', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    $poa = Poa::factory()->create(['otros_ingresos' => 0]);
    PoaActividad::factory()->create(['poa_id' => $poa->id, 'frecuencia_anual' => 1, 'costo_unitario' => 1000]);

    $calculadora = app(CalculadoraCuota::class);

    expect($calculadora->reserva($poa))->toBe(100.0)
        ->and($calculadora->presupuestoNeto($poa))->toBe(1100.0);
});

test('otros ingresos se restan del presupuesto neto', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    $poa = Poa::factory()->create(['otros_ingresos' => 200]);
    PoaActividad::factory()->create(['poa_id' => $poa->id, 'frecuencia_anual' => 1, 'costo_unitario' => 1000]);

    $calculadora = app(CalculadoraCuota::class);

    // base 1000 + reserva 100 - otros 200 = 900
    expect($calculadora->presupuestoNeto($poa))->toBe(900.0);
});

test('el presupuesto neto nunca es negativo aunque otros ingresos superen el presupuesto', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    $poa = Poa::factory()->create(['otros_ingresos' => 5000]);
    PoaActividad::factory()->create(['poa_id' => $poa->id, 'frecuencia_anual' => 1, 'costo_unitario' => 1000]);

    $calculadora = app(CalculadoraCuota::class);

    expect($calculadora->presupuestoNeto($poa))->toBe(0.0);
});

test('la cuota mensual reparte el presupuesto neto entre 12 meses y el número de socios', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    $poa = Poa::factory()->create(['otros_ingresos' => 0]);
    PoaActividad::factory()->create(['poa_id' => $poa->id, 'frecuencia_anual' => 1, 'costo_unitario' => 4362.5]);

    $calculadora = app(CalculadoraCuota::class);

    // base 4362.5 + reserva 436.25 = 4798.75 neto; 120 socios → 4798.75 / 1440 = 3.33
    expect($calculadora->cuotaMensual($poa, 120))->toBe(3.33);
});

test('sin socios la cuota mensual es null en vez de dividir entre cero', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    $poa = Poa::factory()->create();
    PoaActividad::factory()->create(['poa_id' => $poa->id, 'frecuencia_anual' => 1, 'costo_unitario' => 100]);

    expect(app(CalculadoraCuota::class)->cuotaMensual($poa, 0))->toBeNull();
});

/**
 * Fórmula SUNASS (RCD N.° 028-2018-SUNASS-CD):
 *   CF = PA / [12 × (1 − FIP) × (NA − NE)]
 *   Cuota propuesta = ROUNDUP(CF, 1)
 * Ejemplo canónico del documento fuente: PA=17820, NA=100, NE=5, FIP=0.10
 *   → cuotas cobrables = 12 × 0.9 × 95 = 1026
 *   → CF = 17820 / 1026 ≈ 17.37
 *   → propuesta = ROUNDUP(17.37, 1) = 17.4
 */
test('SUNASS: caso canónico (PA=17820, NA=100, NE=5, FIP=0.10) → cuota 17.4', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    $poa = Poa::factory()->create(['otros_ingresos' => 0]);
    // Ajustamos las actividades para que presupuestoNeto = 17820.
    // base + 10% = 17820 → base = 16200. Una sola actividad 16200 × 1.
    PoaActividad::factory()->create(['poa_id' => $poa->id, 'frecuencia_anual' => 1, 'costo_unitario' => 16200]);

    $r = app(CalculadoraCuota::class)->cuotaMensualSunass($poa, 100, 5, 0.10);

    expect($r['presupuesto_anual'])->toBe(17820.0);
    expect($r['cuotas_cobrables_anio'])->toBe(1026.0);
    expect($r['cuota_calculada'])->toBe(17.37);
    expect($r['cuota_propuesta'])->toBe(17.4);
    expect($r['ingreso_anual_esperado'])->toBe(17852.4);
});

test('SUNASS: divisor cero (todos exonerados) → cuota propuesta 0', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $poa = Poa::factory()->create();
    PoaActividad::factory()->create(['poa_id' => $poa->id, 'frecuencia_anual' => 1, 'costo_unitario' => 10000]);

    $r = app(CalculadoraCuota::class)->cuotaMensualSunass($poa, 100, 100, 0.10);
    expect($r['cuotas_cobrables_anio'])->toBe(0.0);
    expect($r['cuota_propuesta'])->toBe(0.0);
});

test('SUNASS: ROUNDUP siempre redondea hacia arriba a 1 decimal, nunca a la baja', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $poa = Poa::factory()->create(['otros_ingresos' => 0]);
    // base + reserva = 1000 → base = 909.09... para test simplifiquemos:
    // ajustamos hasta que presupuestoNeto sea 1000
    PoaActividad::factory()->create(['poa_id' => $poa->id, 'frecuencia_anual' => 1, 'costo_unitario' => 909.09]);
    // presupuestoNeto ≈ 909.09 + 90.909 = 999.999 → redondeado a 2 = 1000.00
    // CF = 1000 / (12 × 1 × 100) = 0.833... → ROUNDUP(0.83, 1) = 0.9
    $r = app(CalculadoraCuota::class)->cuotaMensualSunass($poa, 100, 0, 0.0);
    expect($r['cuota_propuesta'])->toBe(0.9);
});

test('SUNASS: NE > NA lanza excepción', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $poa = Poa::factory()->create();
    PoaActividad::factory()->create(['poa_id' => $poa->id, 'frecuencia_anual' => 1, 'costo_unitario' => 100]);

    expect(fn () => app(CalculadoraCuota::class)->cuotaMensualSunass($poa, 10, 20, 0.10))
        ->toThrow(InvalidArgumentException::class);
});

test('SUNASS: FIP fuera de [0, 1) lanza excepción', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $poa = Poa::factory()->create();
    PoaActividad::factory()->create(['poa_id' => $poa->id, 'frecuencia_anual' => 1, 'costo_unitario' => 100]);

    expect(fn () => app(CalculadoraCuota::class)->cuotaMensualSunass($poa, 100, 0, 1.0))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => app(CalculadoraCuota::class)->cuotaMensualSunass($poa, 100, 0, -0.1))
        ->toThrow(InvalidArgumentException::class);
});
