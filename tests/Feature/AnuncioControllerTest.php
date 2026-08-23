<?php

use App\Models\Anuncio;
use App\Models\Auditoria;
use App\Models\Jass;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;

function datosAnuncioValidos(array $sobrescribir = []): array
{
    return array_merge([
        'titulo' => 'Corte de agua programado',
        'contenido' => 'El día sábado se realizará mantenimiento de la red principal.',
        'fecha_publicacion' => now()->toDateString(),
        'estado' => 'visible',
    ], $sobrescribir);
}

test('presidente puede publicar un anuncio y queda auditado', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $response = $this->actingAs($presidente)->post(route('panel.anuncios.store'), datosAnuncioValidos());

    $response->assertRedirect(route('panel.anuncios.index'));
    $anuncio = Anuncio::where('titulo', 'Corte de agua programado')->firstOrFail();
    expect($anuncio->jass_id)->toBe($jass->id)
        ->and($anuncio->usuario_id)->toBe($presidente->id);

    $existeAuditoria = Auditoria::withoutGlobalScope(TenantScope::class)
        ->where('accion', 'crear')->where('entidad_tipo', Anuncio::class)->where('entidad_id', $anuncio->id)->exists();
    expect($existeAuditoria)->toBeTrue();
});

test('un anuncio se puede editar y cambiar a inactivo', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $anuncio = Anuncio::factory()->create();

    $response = $this->actingAs($presidente)->put(
        route('panel.anuncios.update', $anuncio),
        datosAnuncioValidos(['estado' => 'inactivo'])
    );

    $response->assertRedirect(route('panel.anuncios.index'));
    expect($anuncio->fresh()->estado->value)->toBe('inactivo');
});

test('eliminar un anuncio exige confirmar la contraseña y queda soft-deleted', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $anuncio = Anuncio::factory()->create();

    $this->actingAs($presidente)->delete(route('panel.anuncios.destroy', $anuncio))
        ->assertRedirect(route('password.confirm'));

    session(['auth.password_confirmed_at' => time()]);
    $this->actingAs($presidente)->delete(route('panel.anuncios.destroy', $anuncio))
        ->assertRedirect(route('panel.anuncios.index'));

    $this->assertSoftDeleted($anuncio);
});

test('vocal puede ver anuncios pero no crear, editar ni eliminar', function () {
    $jass = Jass::factory()->create();
    $vocal = User::factory()->vocal($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $anuncio = Anuncio::factory()->create();

    $this->actingAs($vocal)->get(route('panel.anuncios.index'))->assertOk();
    $this->actingAs($vocal)->get(route('panel.anuncios.create'))->assertForbidden();
    $this->actingAs($vocal)->put(route('panel.anuncios.update', $anuncio), datosAnuncioValidos())->assertForbidden();
});

test('presidente no puede gestionar un anuncio de otra JASS', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();
    $presidenteA = User::factory()->presidente($jassA)->create();

    app(ContextoJass::class)->fijar($jassB);
    $anuncioB = Anuncio::factory()->create();

    expect($presidenteA->can('view', $anuncioB))->toBeFalse();
    expect($presidenteA->can('update', $anuncioB))->toBeFalse();
    expect($presidenteA->can('delete', $anuncioB))->toBeFalse();
});
