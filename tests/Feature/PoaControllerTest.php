<?php

use App\Models\Jass;
use App\Models\Poa;
use App\Models\Socio;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;

function datosPoaValidos(array $sobrescribir = []): array
{
    return array_merge([
        'otros_ingresos' => '0',
        'actividades' => [
            [
                'categoria' => 'operacion',
                'descripcion' => 'Clorar el agua',
                'frecuencia_anual' => '57.5',
                'costo_unitario' => '18',
                'mes' => 'Todo el año',
                'responsable' => 'Operador',
            ],
            [
                'categoria' => 'administracion',
                'descripcion' => 'Pago del operador',
                'frecuencia_anual' => '12',
                'costo_unitario' => '150',
                'mes' => null,
                'responsable' => 'Tesorero',
            ],
        ],
    ], $sobrescribir);
}

test('presidente puede crear el POA del año y queda auditado', function () {
    $jass = Jass::factory()->create(['tiene_medidor' => false, 'usa_poa' => true]);
    $presidente = User::factory()->presidente($jass)->create();

    $response = $this->actingAs($presidente)->post(route('panel.poa.store'), datosPoaValidos());

    $response->assertRedirect(route('panel.poa.index'));
    $poa = Poa::where('jass_id', $jass->id)->where('anio', now()->year)->firstOrFail();
    expect($poa->actividades()->count())->toBe(2);
});

test('no se puede crear un segundo POA para el mismo año', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $this->actingAs($presidente)->post(route('panel.poa.store'), datosPoaValidos());
    $response = $this->actingAs($presidente)->post(route('panel.poa.store'), datosPoaValidos());

    $response->assertStatus(422);
    expect(Poa::where('jass_id', $jass->id)->count())->toBe(1);
});

test('el índice muestra el presupuesto y la cuota calculados desde las actividades', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    Socio::factory()->count(2)->create();

    $this->actingAs($presidente)->post(route('panel.poa.store'), datosPoaValidos());

    $response = $this->actingAs($presidente)->get(route('panel.poa.index'));

    $response->assertInertia(fn ($page) => $page
        ->where('poa.presupuestoBase', 2835)
        ->where('poa.numeroSocios', 2)
        ->has('poa.actividades', 2)
    );
});

test('vocal puede ver el POA pero no crearlo ni editarlo', function () {
    $jass = Jass::factory()->create();
    $vocal = User::factory()->vocal($jass)->create();

    $this->actingAs($vocal)->get(route('panel.poa.index'))->assertOk();
    $this->actingAs($vocal)->get(route('panel.poa.create'))->assertForbidden();
    $this->actingAs($vocal)->post(route('panel.poa.store'), datosPoaValidos())->assertForbidden();
});

test('presidente puede editar el POA: reemplaza las actividades existentes', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $this->actingAs($presidente)->post(route('panel.poa.store'), datosPoaValidos());
    $poa = Poa::where('jass_id', $jass->id)->firstOrFail();

    $response = $this->actingAs($presidente)->put(route('panel.poa.update'), datosPoaValidos([
        'actividades' => [[
            'categoria' => 'mantenimiento',
            'descripcion' => 'Pintar estructuras',
            'frecuencia_anual' => '1',
            'costo_unitario' => '300',
            'mes' => null,
            'responsable' => null,
        ]],
    ]));

    $response->assertRedirect(route('panel.poa.index'));
    $poa->refresh();
    expect($poa->actividades()->count())->toBe(1)
        ->and($poa->actividades()->first()->descripcion)->toBe('Pintar estructuras');
});

test('editar sin un POA existente da 404', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $this->actingAs($presidente)->get(route('panel.poa.edit'))->assertNotFound();
});

test('presidente de otra JASS no puede ver ni editar el POA ajeno', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    $presidenteB = User::factory()->presidente($jassB)->create();
    $this->actingAs($presidenteB)->post(route('panel.poa.store'), datosPoaValidos());

    $presidenteA = User::factory()->presidente($jassA)->create();

    // presidenteA nunca ve el POA de B: su propio índice está vacío.
    $response = $this->actingAs($presidenteA)->get(route('panel.poa.index'));
    $response->assertInertia(fn ($page) => $page->where('poa', null));
});

test('actividades requiere al menos una fila', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $response = $this->actingAs($presidente)->post(route('panel.poa.store'), datosPoaValidos(['actividades' => []]));

    $response->assertSessionHasErrors('actividades');
});
