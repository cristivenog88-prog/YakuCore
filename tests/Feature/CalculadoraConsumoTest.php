<?php

use App\Models\Jass;
use App\Models\Medidor;
use App\Models\Pago;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Pagos\CalculadoraConsumo;
use App\Services\Tenancy\ContextoJass;

test('sin pagos previos, la lectura anterior es la lectura_inicial (8.2)', function () {
    $jass = Jass::factory()->conMedidor()->create();
    app(ContextoJass::class)->fijar($jass);
    $medidor = Medidor::factory()->create(['lectura_inicial' => 15.5]);

    expect(app(CalculadoraConsumo::class)->lecturaAnterior($medidor))->toBe(15.5);
});

test('con pagos previos, la lectura anterior es la última lectura_actual registrada', function () {
    $jass = Jass::factory()->conMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $this->actingAs($presidente);

    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $medidor = Medidor::factory()->create(['vivienda_id' => $vivienda->id, 'lectura_inicial' => 0]);

    Pago::factory()->create([
        'socio_id' => $socio->id, 'vivienda_id' => $vivienda->id, 'medidor_id' => $medidor->id,
        'tipo' => 'consumo_medidor', 'lectura_anterior' => 0, 'lectura_actual' => 10,
        'fecha_pago' => '2026-01-05', 'concepto' => 'Consumo enero', 'monto' => 20,
    ]);
    Pago::factory()->create([
        'socio_id' => $socio->id, 'vivienda_id' => $vivienda->id, 'medidor_id' => $medidor->id,
        'tipo' => 'consumo_medidor', 'lectura_anterior' => 10, 'lectura_actual' => 18,
        'fecha_pago' => '2026-02-05', 'concepto' => 'Consumo febrero', 'monto' => 16,
    ]);

    expect(app(CalculadoraConsumo::class)->lecturaAnterior($medidor))->toBe(18.0);
});

test('el autocompletado ignora pagos anteriores a reiniciado_en (8.3)', function () {
    $jass = Jass::factory()->conMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $this->actingAs($presidente);

    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $medidor = Medidor::factory()->create([
        'vivienda_id' => $vivienda->id,
        'lectura_inicial' => 0,
        'reiniciado_en' => '2026-03-01 00:00:00',
    ]);

    // Pago anterior al reinicio: debe ignorarse.
    Pago::factory()->create([
        'socio_id' => $socio->id, 'vivienda_id' => $vivienda->id, 'medidor_id' => $medidor->id,
        'tipo' => 'consumo_medidor', 'lectura_anterior' => 0, 'lectura_actual' => 999,
        'fecha_pago' => '2026-02-01', 'concepto' => 'Antes del reemplazo', 'monto' => 1,
    ]);

    expect(app(CalculadoraConsumo::class)->lecturaAnterior($medidor))->toBe(0.0);
});

test('calcula el monto de consumo con casos de dataset', function (float $anterior, float $actual, float $tarifa, float $esperado) {
    expect(app(CalculadoraConsumo::class)->calcularMonto($anterior, $actual, $tarifa))->toBe($esperado);
})->with([
    'primera lectura con inicial 0' => [0.0, 12.0, 2.5, 30.0],
    'primera lectura con inicial > 0' => [50.0, 62.0, 2.5, 30.0],
    'lectura igual → monto 0' => [40.0, 40.0, 2.5, 0.0],
    'con decimales' => [10.25, 15.75, 1.8, 9.9],
]);
