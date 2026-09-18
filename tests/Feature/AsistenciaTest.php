<?php

use App\Models\Asistencia;
use App\Models\Auditoria;
use App\Models\Jass;
use App\Models\Reunion;
use App\Models\Scopes\TenantScope;
use App\Models\Socio;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;

test('al tomar asistencia se crea una fila por socio, la reunión pasa a finalizada y queda auditado', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $reunion = Reunion::factory()->create();
    $socioA = Socio::factory()->create();
    $socioB = Socio::factory()->create();

    $payload = [
        'asistencias' => [
            ['socio_id' => $socioA->id, 'estado' => 'presente'],
            ['socio_id' => $socioB->id, 'estado' => 'ausente'],
        ],
    ];

    $response = $this->actingAs($presidente)->post(route('panel.reuniones.asistencia.store', $reunion), $payload);

    $response->assertRedirect(route('panel.reuniones.show', $reunion));
    expect($reunion->fresh()->estado->value)->toBe('finalizada')
        ->and(Asistencia::where('reunion_id', $reunion->id)->count())->toBe(2)
        ->and(Asistencia::where(['reunion_id' => $reunion->id, 'socio_id' => $socioA->id])->first()->estado->value)->toBe('presente')
        ->and(Asistencia::where(['reunion_id' => $reunion->id, 'socio_id' => $socioB->id])->first()->estado->value)->toBe('ausente');

    $existeAuditoria = Auditoria::withoutGlobalScope(TenantScope::class)
        ->where('accion', 'tomar_asistencia')->where('entidad_tipo', Reunion::class)->where('entidad_id', $reunion->id)->exists();
    expect($existeAuditoria)->toBeTrue();
});

test('tomar asistencia exige cubrir a todos los socios de la JASS, ni más ni menos', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $reunion = Reunion::factory()->create();
    Socio::factory()->create();
    Socio::factory()->create();

    $unSolo = Socio::first();
    $payload = ['asistencias' => [['socio_id' => $unSolo->id, 'estado' => 'presente']]];

    $response = $this->actingAs($presidente)->post(route('panel.reuniones.asistencia.store', $reunion), $payload);

    $response->assertSessionHasErrors('asistencias');
    expect(Asistencia::count())->toBe(0)
        ->and($reunion->fresh()->estado->value)->toBe('programada');
});

test('una reunión finalizada no admite tomar asistencia de nuevo', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $reunion = Reunion::factory()->create(['estado' => 'finalizada']);
    $socio = Socio::factory()->create();

    $payload = ['asistencias' => [['socio_id' => $socio->id, 'estado' => 'presente']]];

    $this->actingAs($presidente)->post(route('panel.reuniones.asistencia.store', $reunion), $payload)
        ->assertForbidden();
});

test('la asistencia es inmutable: la policy niega update y delete a todo rol', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $asistencia = Asistencia::factory()->create();

    $presidente = User::factory()->presidente($jass)->create();
    $superadmin = User::factory()->superadmin()->create();

    expect($presidente->can('update', $asistencia))->toBeFalse();
    expect($presidente->can('delete', $asistencia))->toBeFalse();
    expect($superadmin->can('update', $asistencia))->toBeFalse();
    expect($superadmin->can('delete', $asistencia))->toBeFalse();
});
