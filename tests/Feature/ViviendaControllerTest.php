<?php

use App\Models\Auditoria;
use App\Models\Jass;
use App\Models\Scopes\TenantScope;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Tenancy\ContextoJass;

test('presidente puede ver la ficha del socio con sus viviendas', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    Vivienda::factory()->create(['socio_id' => $socio->id]);
    Vivienda::factory()->create(['socio_id' => $socio->id]);

    $response = $this->actingAs($presidente)->get(route('panel.socios.show', $socio));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('panel/socios/show')->has('viviendas', 2));
});

test('un socio puede tener varias viviendas (regla 5.1) y cada alta queda auditada', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();

    $this->actingAs($presidente)->post(route('panel.socios.viviendas.store', $socio), [
        'direccion' => 'Jr. Primera 100',
        'activa' => true,
    ])->assertRedirect(route('panel.socios.show', $socio));

    $this->actingAs($presidente)->post(route('panel.socios.viviendas.store', $socio), [
        'direccion' => 'Jr. Segunda 200',
        'activa' => true,
    ])->assertRedirect(route('panel.socios.show', $socio));

    expect($socio->viviendas()->count())->toBe(2);

    $viviendas = Vivienda::where('socio_id', $socio->id)->get();
    foreach ($viviendas as $vivienda) {
        expect($vivienda->jass_id)->toBe($jass->id);

        $auditada = Auditoria::withoutGlobalScope(TenantScope::class)
            ->where('accion', 'crear')->where('entidad_tipo', Vivienda::class)->where('entidad_id', $vivienda->id)->exists();
        expect($auditada)->toBeTrue();
    }
});

test('vocal ve la ficha pero no puede agregar ni eliminar viviendas', function () {
    $jass = Jass::factory()->create();
    $vocal = User::factory()->vocal($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();

    $this->actingAs($vocal)->get(route('panel.socios.show', $socio))->assertOk();
    $this->actingAs($vocal)->post(route('panel.socios.viviendas.store', $socio), ['direccion' => 'X', 'activa' => true])
        ->assertForbidden();
});

test('eliminar una vivienda exige confirmar la contraseña', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);

    $this->actingAs($presidente)->delete(route('panel.socios.viviendas.destroy', [$socio, $vivienda]))
        ->assertRedirect(route('password.confirm'));

    session(['auth.password_confirmed_at' => time()]);
    $this->actingAs($presidente)->delete(route('panel.socios.viviendas.destroy', [$socio, $vivienda]))
        ->assertRedirect(route('panel.socios.show', $socio));

    $this->assertSoftDeleted($vivienda);
});

test('presidente no puede agregar viviendas a un socio de otra JASS', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();
    $presidenteA = User::factory()->presidente($jassA)->create();

    app(ContextoJass::class)->fijar($jassB);
    $socioB = Socio::factory()->create();

    $this->actingAs($presidenteA)->get(route('panel.socios.show', $socioB))->assertNotFound();
});
