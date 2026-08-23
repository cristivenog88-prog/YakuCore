<?php

use App\Enums\Periodicidad;
use App\Models\Auditoria;
use App\Models\Jass;
use App\Models\Scopes\TenantScope;
use App\Models\User;

/**
 * Las acciones de superadmin auditan con jass_id null (acción global) y no
 * corren bajo el middleware "jass", así que ContextoJass no tiene JASS
 * activa: para verificar el registro hay que sortear el TenantScope
 * (que por diseño oculta todo sin contexto), igual que hará la vista
 * global de auditoría de Fase 7.
 */
function existeAuditoria(string $accion, string $entidadTipo, int $entidadId): bool
{
    return Auditoria::withoutGlobalScope(TenantScope::class)
        ->where('accion', $accion)
        ->where('entidad_tipo', $entidadTipo)
        ->where('entidad_id', $entidadId)
        ->exists();
}

function datosJassValidos(array $sobrescribir = []): array
{
    return array_merge([
        'nombre' => 'Nueva JASS',
        'comunidad' => 'Comunidad X',
        'distrito' => 'Distrito X',
        'tiene_medidor' => true,
        'tarifa_base_m3' => '2.50',
        'periodicidad_mantenimiento' => Periodicidad::Mensual->value,
        'dia_vencimiento' => 15,
    ], $sobrescribir);
}

test('superadmin puede ver el listado de JASS', function () {
    $superadmin = User::factory()->superadmin()->create();
    Jass::factory()->count(2)->create();

    $this->actingAs($superadmin)->get(route('superadmin.jass.index'))->assertOk();
});

test('presidente, tesorero y vocal no pueden acceder a ninguna ruta de JASS', function () {
    $presidente = User::factory()->presidente()->create();

    $this->actingAs($presidente)->get(route('superadmin.jass.index'))->assertForbidden();
    $this->actingAs($presidente)->get(route('superadmin.jass.create'))->assertForbidden();
    $this->actingAs($presidente)->post(route('superadmin.jass.store'), datosJassValidos())->assertForbidden();
});

test('superadmin puede crear una JASS y queda auditado', function () {
    $superadmin = User::factory()->superadmin()->create();

    $response = $this->actingAs($superadmin)->post(route('superadmin.jass.store'), datosJassValidos(['nombre' => 'Villa Sol']));

    $response->assertRedirect(route('superadmin.jass.index'));
    $jass = Jass::where('nombre', 'Villa Sol')->firstOrFail();
    expect($jass->prefijo)->toBe('JASSVillaSol');

    expect(existeAuditoria('crear', Jass::class, $jass->id))->toBeTrue();
});

test('tarifa_base_m3 es obligatoria si tiene_medidor es true', function () {
    $superadmin = User::factory()->superadmin()->create();

    $response = $this->actingAs($superadmin)->post(route('superadmin.jass.store'), datosJassValidos(['tarifa_base_m3' => null]));

    $response->assertSessionHasErrors('tarifa_base_m3');
});

test('tarifa_base_m3 se guarda como null si tiene_medidor es false', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)->post(route('superadmin.jass.store'), datosJassValidos([
        'nombre' => 'Sin Medidor',
        'tiene_medidor' => false,
        'tarifa_base_m3' => '9.99',
    ]));

    $jass = Jass::where('nombre', 'Sin Medidor')->firstOrFail();
    expect($jass->tarifa_base_m3)->toBeNull();
});

test('el nombre de la JASS debe ser único', function () {
    Jass::factory()->create(['nombre' => 'Repetida']);
    $superadmin = User::factory()->superadmin()->create();

    $response = $this->actingAs($superadmin)->post(route('superadmin.jass.store'), datosJassValidos(['nombre' => 'Repetida']));

    $response->assertSessionHasErrors('nombre');
});

test('superadmin puede actualizar una JASS y queda auditado', function () {
    $superadmin = User::factory()->superadmin()->create();
    $jass = Jass::factory()->create(['nombre' => 'Original']);

    $response = $this->actingAs($superadmin)->put(route('superadmin.jass.update', $jass), datosJassValidos(['nombre' => 'Actualizada']));

    $response->assertRedirect(route('superadmin.jass.index'));
    expect($jass->fresh()->nombre)->toBe('Actualizada');
    expect(existeAuditoria('actualizar', Jass::class, $jass->id))->toBeTrue();
});

test('eliminar una JASS exige confirmar la contraseña', function () {
    $superadmin = User::factory()->superadmin()->create();
    $jass = Jass::factory()->create();

    $response = $this->actingAs($superadmin)->delete(route('superadmin.jass.destroy', $jass));

    $response->assertRedirect(route('password.confirm'));
    expect($jass->fresh())->not->toBeNull();
});

test('con la contraseña confirmada, superadmin elimina (soft delete) la JASS y queda auditado', function () {
    $superadmin = User::factory()->superadmin()->create();
    $jass = Jass::factory()->create();

    $this->actingAs($superadmin);
    session(['auth.password_confirmed_at' => time()]);

    $response = $this->delete(route('superadmin.jass.destroy', $jass));

    $response->assertRedirect(route('superadmin.jass.index'));
    $this->assertSoftDeleted($jass);
    expect(existeAuditoria('eliminar', Jass::class, $jass->id))->toBeTrue();
});
