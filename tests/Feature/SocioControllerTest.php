<?php

use App\Models\Auditoria;
use App\Models\Jass;
use App\Models\Scopes\TenantScope;
use App\Models\Socio;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;

function datosSocioValidos(array $sobrescribir = []): array
{
    return array_merge([
        'nombres' => 'Juan',
        'apellidos' => 'Pérez Quispe',
        'dni' => '12345678',
        'telefono' => '987654321',
        'direccion' => 'Jr. Los Andes 123',
    ], $sobrescribir);
}

test('presidente puede crear un socio y queda auditado', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $response = $this->actingAs($presidente)->post(route('panel.socios.store'), datosSocioValidos());

    $response->assertRedirect(route('panel.socios.index'));
    $socio = Socio::where('dni', '12345678')->firstOrFail();
    expect($socio->jass_id)->toBe($jass->id);

    $existeAuditoria = Auditoria::withoutGlobalScope(TenantScope::class)
        ->where('accion', 'crear')->where('entidad_tipo', Socio::class)->where('entidad_id', $socio->id)->exists();
    expect($existeAuditoria)->toBeTrue();
});

test('el DNI debe tener 8 dígitos', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $response = $this->actingAs($presidente)->post(route('panel.socios.store'), datosSocioValidos(['dni' => '123']));

    $response->assertSessionHasErrors('dni');
});

test('el mismo DNI puede existir en dos JASS distintas, pero no repetirse en la misma', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();
    $presidenteA = User::factory()->presidente($jassA)->create();
    $presidenteB = User::factory()->presidente($jassB)->create();

    $this->actingAs($presidenteA)->post(route('panel.socios.store'), datosSocioValidos(['dni' => '11223344']))
        ->assertRedirect(route('panel.socios.index'));

    // mismo DNI, otra JASS: pasa
    $this->actingAs($presidenteB)->post(route('panel.socios.store'), datosSocioValidos(['dni' => '11223344']))
        ->assertRedirect(route('panel.socios.index'));

    // mismo DNI, misma JASS: falla
    $this->actingAs($presidenteA)->post(route('panel.socios.store'), datosSocioValidos(['dni' => '11223344', 'nombres' => 'Otro']))
        ->assertSessionHasErrors('dni');

    expect(Socio::withoutGlobalScope(TenantScope::class)->where('dni', '11223344')->count())->toBe(2);
});

test('vocal puede ver el listado pero no gestionar (crear/editar/eliminar)', function () {
    $jass = Jass::factory()->create();
    $vocal = User::factory()->vocal($jass)->create();

    $this->actingAs($vocal)->get(route('panel.socios.index'))->assertOk();
    $this->actingAs($vocal)->get(route('panel.socios.create'))->assertForbidden();
    $this->actingAs($vocal)->post(route('panel.socios.store'), datosSocioValidos())->assertForbidden();
});

test('el buscador filtra por DNI y por apellidos', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    Socio::factory()->create(['nombres' => 'Ana', 'apellidos' => 'Torres Lima', 'dni' => '99999999']);
    Socio::factory()->create(['nombres' => 'Luis', 'apellidos' => 'Ramos Vega', 'dni' => '88888888']);

    $porDni = $this->actingAs($presidente)->get(route('panel.socios.index', ['buscar' => '99999999']));
    $porDni->assertInertia(fn ($page) => $page->has('socios', 1)->where('socios.0.dni', '99999999'));

    $porApellido = $this->actingAs($presidente)->get(route('panel.socios.index', ['buscar' => 'Torres']));
    $porApellido->assertInertia(fn ($page) => $page->has('socios', 1)->where('socios.0.apellidos', 'Torres Lima'));
});

test('eliminar un socio exige confirmar la contraseña y queda soft-deleted', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();

    $this->actingAs($presidente)->delete(route('panel.socios.destroy', $socio))
        ->assertRedirect(route('password.confirm'));

    session(['auth.password_confirmed_at' => time()]);
    $this->actingAs($presidente)->delete(route('panel.socios.destroy', $socio))
        ->assertRedirect(route('panel.socios.index'));

    $this->assertSoftDeleted($socio);
});

test('presidente no puede editar ni eliminar socios de otra JASS', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();
    $presidenteA = User::factory()->presidente($jassA)->create();

    app(ContextoJass::class)->fijar($jassB);
    $socioB = Socio::factory()->create();

    $this->actingAs($presidenteA)->get(route('panel.socios.edit', $socioB))->assertNotFound();
});

test('superadmin puede supervisar y ver socios de cualquier JASS de solo lectura', function () {
    $jass = Jass::factory()->create();
    $superadmin = User::factory()->superadmin()->create();

    app(ContextoJass::class)->fijar($jass);
    Socio::factory()->create();

    $response = $this->actingAs($superadmin)->get(route('supervision.socios.index', $jass));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('socios', 1));
});
