<?php

use App\Models\Auditoria;
use App\Models\Jass;
use App\Models\Scopes\TenantScope;
use App\Models\User;

function datosUsuarioValidos(array $sobrescribir = []): array
{
    return array_merge([
        'name' => 'Nuevo Usuario',
        'email' => 'nuevo.usuario@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rol' => 'tesorero',
    ], $sobrescribir);
}

// --- Presidente vía /panel/usuarios ---

test('presidente puede ver el listado y crear tesorero/vocal de su propia JASS', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $this->actingAs($presidente)->get(route('panel.usuarios.index'))->assertOk();

    $response = $this->actingAs($presidente)->post(route('panel.usuarios.store'), datosUsuarioValidos());

    $response->assertRedirect(route('panel.usuarios.index'));
    $creado = User::where('email', 'nuevo.usuario@example.com')->firstOrFail();
    expect($creado->jass_id)->toBe($jass->id);
    expect($creado->rol->value)->toBe('tesorero');
});

test('presidente no puede crear un usuario con rol presidente', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $response = $this->actingAs($presidente)->post(route('panel.usuarios.store'), datosUsuarioValidos(['rol' => 'presidente']));

    $response->assertForbidden();
});

test('tesorero y vocal no pueden acceder a la gestión de usuarios', function () {
    $tesorero = User::factory()->tesorero()->create();
    $vocal = User::factory()->vocal()->create();

    $this->actingAs($tesorero)->get(route('panel.dashboard'))->assertOk();
    $this->actingAs($tesorero)->get('/panel/usuarios')->assertForbidden();
    $this->actingAs($vocal)->get('/panel/usuarios')->assertForbidden();
});

test('presidente no puede editar ni eliminar un usuario de otra JASS', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();
    $presidenteA = User::factory()->presidente($jassA)->create();
    $tesoreroB = User::factory()->tesorero($jassB)->create();

    $this->actingAs($presidenteA)->get(route('panel.usuarios.edit', $tesoreroB))->assertForbidden();
    $this->actingAs($presidenteA)->put(route('panel.usuarios.update', $tesoreroB), datosUsuarioValidos())->assertForbidden();

    session(['auth.password_confirmed_at' => time()]);
    $this->actingAs($presidenteA)->delete(route('panel.usuarios.destroy', $tesoreroB))->assertForbidden();

    expect($tesoreroB->fresh())->not->toBeNull();
});

test('eliminar un usuario exige confirmar la contraseña', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    $tesorero = User::factory()->tesorero($jass)->create();

    $response = $this->actingAs($presidente)->delete(route('panel.usuarios.destroy', $tesorero));

    $response->assertRedirect(route('password.confirm'));
    expect($tesorero->fresh())->not->toBeNull();
});

test('con la contraseña confirmada, presidente elimina (soft delete) al usuario y queda auditado', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    $tesorero = User::factory()->tesorero($jass)->create();

    $this->actingAs($presidente);
    session(['auth.password_confirmed_at' => time()]);

    $response = $this->delete(route('panel.usuarios.destroy', $tesorero));

    $response->assertRedirect(route('panel.usuarios.index'));
    $this->assertSoftDeleted($tesorero);

    $existeAuditoria = Auditoria::withoutGlobalScope(TenantScope::class)
        ->where('accion', 'eliminar')
        ->where('entidad_tipo', User::class)
        ->where('entidad_id', $tesorero->id)
        ->exists();

    expect($existeAuditoria)->toBeTrue();
});

// --- Superadmin vía /superadmin/jass/{jass}/usuarios ---

test('superadmin puede crear un presidente en cualquier JASS', function () {
    $jass = Jass::factory()->create();
    $superadmin = User::factory()->superadmin()->create();

    $response = $this->actingAs($superadmin)->post(
        route('superadmin.jass.usuarios.store', $jass),
        datosUsuarioValidos(['rol' => 'presidente', 'email' => 'presidente.nuevo@example.com'])
    );

    $response->assertRedirect(route('superadmin.jass.usuarios.index', $jass));
    $creado = User::where('email', 'presidente.nuevo@example.com')->firstOrFail();
    expect($creado->rol->value)->toBe('presidente');
    expect($creado->jass_id)->toBe($jass->id);
});

test('presidente no puede acceder a la gestión de usuarios de otra JASS vía rutas de superadmin', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente()->create();

    $this->actingAs($presidente)->get(route('superadmin.jass.usuarios.index', $jass))->assertForbidden();
});
