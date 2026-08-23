<?php

use App\Models\Auditoria;
use App\Models\Jass;
use App\Models\Medidor;
use App\Models\Scopes\TenantScope;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Medidores\ReemplazoMedidor;
use App\Services\Tenancy\ContextoJass;

test('el servicio resetea la lectura inicial a 0 y fija reiniciado_en', function () {
    $jass = Jass::factory()->conMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $this->actingAs($presidente);

    $medidor = Medidor::factory()->create(['lectura_inicial' => 87.5, 'reiniciado_en' => null]);

    app(ReemplazoMedidor::class)->ejecutar($medidor, 'Medidor dañado por humedad');

    $medidor->refresh();
    expect((float) $medidor->lectura_inicial)->toBe(0.0);
    expect($medidor->reiniciado_en)->not->toBeNull();
});

test('el reemplazo audita el motivo y las lecturas previas', function () {
    $jass = Jass::factory()->conMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);

    $medidor = Medidor::factory()->create(['lectura_inicial' => 42.0]);

    $this->actingAs($presidente);
    app(ReemplazoMedidor::class)->ejecutar($medidor, 'Cambio por vencimiento');

    $registro = Auditoria::withoutGlobalScope(TenantScope::class)
        ->where('accion', 'reemplazar_medidor')
        ->where('entidad_id', $medidor->id)
        ->firstOrFail();

    expect($registro->detalles['motivo'])->toBe('Cambio por vencimiento');
    expect($registro->detalles['lectura_inicial_previa'])->toBe('42.00');
});

test('reemplazar un medidor por ruta exige confirmar la contraseña', function () {
    $jass = Jass::factory()->conMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $medidor = Medidor::factory()->create(['vivienda_id' => $vivienda->id, 'lectura_inicial' => 30]);

    $response = $this->actingAs($presidente)->post(
        route('panel.viviendas.medidor.reemplazar', [$vivienda, $medidor]),
        ['motivo' => 'Medidor roto']
    );

    $response->assertRedirect(route('password.confirm'));
    expect($medidor->fresh()->lectura_inicial)->toBe('30.00');
});

test('con la contraseña confirmada, el reemplazo no bloquea futuros pagos: solo resetea la base', function () {
    $jass = Jass::factory()->conMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $medidor = Medidor::factory()->create(['vivienda_id' => $vivienda->id, 'lectura_inicial' => 30]);

    $this->actingAs($presidente);
    session(['auth.password_confirmed_at' => time()]);

    $response = $this->post(route('panel.viviendas.medidor.reemplazar', [$vivienda, $medidor]), [
        'motivo' => 'Medidor roto por congelamiento',
    ]);

    $response->assertRedirect(route('panel.socios.show', $socio));

    $medidor->refresh();
    expect((float) $medidor->lectura_inicial)->toBe(0.0);
    expect($medidor->activo)->toBeTrue();
});

test('vocal no puede reemplazar medidores', function () {
    $jass = Jass::factory()->conMedidor()->create();
    $vocal = User::factory()->vocal($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $medidor = Medidor::factory()->create(['vivienda_id' => $vivienda->id]);

    session(['auth.password_confirmed_at' => time()]);
    $this->actingAs($vocal)
        ->post(route('panel.viviendas.medidor.reemplazar', [$vivienda, $medidor]), ['motivo' => 'Cualquiera'])
        ->assertForbidden();
});
