<?php

use App\Models\Auditoria;
use App\Models\Jass;
use App\Models\Reunion;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;

function datosReunionValidos(array $sobrescribir = []): array
{
    return array_merge([
        'titulo' => 'Asamblea general',
        'descripcion' => 'Rendición de cuentas del trimestre',
        'fecha' => now()->addDays(5)->toDateString(),
        'hora' => '18:00',
        'lugar' => 'Local comunal',
        'puntos_tratar' => 'Presupuesto, mantenimiento de red',
    ], $sobrescribir);
}

test('presidente puede crear una reunión y queda auditada', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $response = $this->actingAs($presidente)->post(route('panel.reuniones.store'), datosReunionValidos());

    $reunion = Reunion::where('titulo', 'Asamblea general')->firstOrFail();
    $response->assertRedirect(route('panel.reuniones.show', $reunion));
    expect($reunion->jass_id)->toBe($jass->id)
        ->and($reunion->estado->value)->toBe('programada');

    $existeAuditoria = Auditoria::withoutGlobalScope(TenantScope::class)
        ->where('accion', 'crear')->where('entidad_tipo', Reunion::class)->where('entidad_id', $reunion->id)->exists();
    expect($existeAuditoria)->toBeTrue();
});

test('la fecha de una reunión nueva no puede ser anterior a hoy', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $response = $this->actingAs($presidente)->post(
        route('panel.reuniones.store'),
        datosReunionValidos(['fecha' => now()->subDay()->toDateString()])
    );

    $response->assertSessionHasErrors('fecha');
});

test('una reunión programada se puede editar', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $reunion = Reunion::factory()->create();

    $response = $this->actingAs($presidente)->put(
        route('panel.reuniones.update', $reunion),
        datosReunionValidos(['titulo' => 'Asamblea reprogramada', 'fecha' => $reunion->fecha->toDateString()])
    );

    $response->assertRedirect(route('panel.reuniones.show', $reunion));
    expect($reunion->fresh()->titulo)->toBe('Asamblea reprogramada');
});

test('una reunión cancelada ya no se puede editar ni volver a cancelar', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $reunion = Reunion::factory()->create();

    $this->actingAs($presidente)->post(route('panel.reuniones.cancelar', $reunion))
        ->assertRedirect(route('panel.reuniones.show', $reunion));
    expect($reunion->fresh()->estado->value)->toBe('cancelada');

    $this->actingAs($presidente)->get(route('panel.reuniones.edit', $reunion))->assertForbidden();
    $this->actingAs($presidente)->post(route('panel.reuniones.cancelar', $reunion))->assertForbidden();
});

test('vocal puede ver reuniones pero no crear, editar ni cancelar', function () {
    $jass = Jass::factory()->create();
    $vocal = User::factory()->vocal($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $reunion = Reunion::factory()->create();

    $this->actingAs($vocal)->get(route('panel.reuniones.index'))->assertOk();
    $this->actingAs($vocal)->get(route('panel.reuniones.show', $reunion))->assertOk();
    $this->actingAs($vocal)->get(route('panel.reuniones.create'))->assertForbidden();
    $this->actingAs($vocal)->put(route('panel.reuniones.update', $reunion), datosReunionValidos())->assertForbidden();
    $this->actingAs($vocal)->post(route('panel.reuniones.cancelar', $reunion))->assertForbidden();
});

test('presidente no puede ver ni gestionar una reunión de otra JASS', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();
    $presidenteA = User::factory()->presidente($jassA)->create();

    app(ContextoJass::class)->fijar($jassB);
    $reunionB = Reunion::factory()->create();

    expect($presidenteA->can('view', $reunionB))->toBeFalse();
    expect($presidenteA->can('update', $reunionB))->toBeFalse();
});
