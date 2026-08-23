<?php

use App\Models\Auditoria;
use App\Models\Jass;
use App\Models\Medidor;
use App\Models\Scopes\TenantScope;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Tenancy\ContextoJass;

test('en una JASS sin medidor, las rutas de medidor devuelven 404', function () {
    $jass = Jass::factory()->sinMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    $this->actingAs($presidente)
        ->post(route('panel.viviendas.medidor.store', $vivienda), ['correlativo' => 1])
        ->assertNotFound();
});

test('en una JASS sin medidor, la ficha del socio no ofrece nada de medidores', function () {
    $jass = Jass::factory()->sinMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    Vivienda::factory()->create(['socio_id' => $socio->id]);

    $response = $this->actingAs($presidente)->get(route('panel.socios.show', $socio));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('tieneMedidor', false));
});

test('presidente puede crear un medidor con código autogenerado y lectura inicial, y queda auditado', function () {
    $jass = Jass::factory()->conMedidor()->create(['nombre' => 'Recuay']);
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    $response = $this->actingAs($presidente)->post(route('panel.viviendas.medidor.store', $vivienda), [
        'correlativo' => 5,
        'lectura_inicial' => '12.50',
    ]);

    $response->assertRedirect(route('panel.socios.show', $socio));

    $medidor = Medidor::where('vivienda_id', $vivienda->id)->firstOrFail();
    expect($medidor->codigo)->toBe('JASSRecuay-MED-5');
    expect((float) $medidor->lectura_inicial)->toBe(12.50);
    expect($medidor->jass_id)->toBe($jass->id);

    $auditado = Auditoria::withoutGlobalScope(TenantScope::class)
        ->where('accion', 'crear')->where('entidad_tipo', Medidor::class)->where('entidad_id', $medidor->id)->exists();
    expect($auditado)->toBeTrue();
});

test('el correlativo debe ser único por JASS, con mensaje en español', function () {
    $jass = Jass::factory()->conMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $viviendaA = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $viviendaB = Vivienda::factory()->create(['socio_id' => $socio->id]);
    Medidor::factory()->create(['vivienda_id' => $viviendaA->id, 'correlativo' => 9]);

    $response = $this->actingAs($presidente)->post(route('panel.viviendas.medidor.store', $viviendaB), ['correlativo' => 9]);

    $response->assertSessionHasErrors('correlativo');
    expect(session('errors')->first('correlativo'))->toContain('Ya existe un medidor');
});

test('una vivienda no puede tener dos medidores', function () {
    $jass = Jass::factory()->conMedidor()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    Medidor::factory()->create(['vivienda_id' => $vivienda->id, 'correlativo' => 1]);

    $this->actingAs($presidente)
        ->post(route('panel.viviendas.medidor.store', $vivienda), ['correlativo' => 2])
        ->assertStatus(422);
});

test('vocal no puede crear medidores', function () {
    $jass = Jass::factory()->conMedidor()->create();
    $vocal = User::factory()->vocal($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    $this->actingAs($vocal)
        ->post(route('panel.viviendas.medidor.store', $vivienda), ['correlativo' => 1])
        ->assertForbidden();
});
