<?php

use App\Models\Jass;
use App\Models\Socio;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Support\Facades\Route;

test('vocal no tiene acceso a la vista de auditoría (8.4)', function () {
    $jass = Jass::factory()->create();
    $vocal = User::factory()->vocal($jass)->create();

    $this->actingAs($vocal)->get(route('panel.auditoria.index'))->assertForbidden();
});

test('presidente y tesorero pueden ver la auditoría de su propia JASS', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    $tesorero = User::factory()->tesorero($jass)->create();

    $this->actingAs($presidente)->get(route('panel.auditoria.index'))->assertOk();
    $this->actingAs($tesorero)->get(route('panel.auditoria.index'))->assertOk();
});

test('la auditoría solo muestra los registros de la JASS activa (solo lectura, sin mezclar JASS)', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    $presidenteA = User::factory()->presidente($jassA)->create();
    $this->actingAs($presidenteA)->post(route('panel.socios.store'), [
        'nombres' => 'De la', 'apellidos' => 'Jass A', 'dni' => '11112222', 'direccion' => 'Calle 1',
    ]);

    $presidenteB = User::factory()->presidente($jassB)->create();
    $this->actingAs($presidenteB)->post(route('panel.socios.store'), [
        'nombres' => 'De la', 'apellidos' => 'Jass B', 'dni' => '33334444', 'direccion' => 'Calle 2',
    ]);

    $response = $this->actingAs($presidenteA)->get(route('panel.auditoria.index'));

    $response->assertInertia(fn ($page) => $page->has('registros', 1));
});

test('la vista de auditoría no expone rutas de edición ni borrado', function () {
    expect(Route::has('panel.auditoria.update'))->toBeFalse();
    expect(Route::has('panel.auditoria.destroy'))->toBeFalse();
});

test('superadmin puede ver la auditoría de una JASS en modo supervisión, de solo lectura', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $this->actingAs($presidente);
    Socio::factory()->create();

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)->get(route('supervision.auditoria.index', $jass))->assertOk();
});
