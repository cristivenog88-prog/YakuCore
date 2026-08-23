<?php

use App\Models\Jass;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

test('la vista de morosidad lista viviendas con periodos vencidos e impagos', function () {
    $jass = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    Carbon::setTestNow('2026-01-01');
    $socio = Socio::factory()->create(['apellidos' => 'Quispe']);
    Vivienda::factory()->create(['socio_id' => $socio->id]);

    Carbon::setTestNow('2026-03-20');
    $this->actingAs($presidente);

    $response = $this->get(route('panel.morosidad.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('viviendas', 1)
        ->where('viviendas.0.socio', 'Quispe, '.$socio->nombres)
        ->where('viviendas.0.periodosMantenimiento', ['2026-01', '2026-02', '2026-03'])
    );
});

test('la vista de morosidad no incluye viviendas al dia', function () {
    $jass = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    Carbon::setTestNow('2026-03-01');
    $socio = Socio::factory()->create();
    Vivienda::factory()->create(['socio_id' => $socio->id]);

    Carbon::setTestNow('2026-03-05');
    $this->actingAs($presidente);

    $response = $this->get(route('panel.morosidad.index'));

    $response->assertInertia(fn ($page) => $page->has('viviendas', 0));
});

test('la morosidad de una jass no se filtra hacia otra', function () {
    $jassA = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);
    $jassB = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);

    Carbon::setTestNow('2026-01-01');
    app(ContextoJass::class)->fijar($jassA);
    $socioA = Socio::factory()->create();
    Vivienda::factory()->create(['socio_id' => $socioA->id]);

    app(ContextoJass::class)->fijar($jassB);
    $socioB = Socio::factory()->create();
    Vivienda::factory()->create(['socio_id' => $socioB->id]);

    Carbon::setTestNow('2026-03-20');

    $presidenteA = User::factory()->presidente($jassA)->create();
    $this->actingAs($presidenteA);

    $response = $this->get(route('panel.morosidad.index'));

    $response->assertInertia(fn ($page) => $page->has('viviendas', 1));
});

test('el vocal ve la morosidad pero no puede gestionar el cobro', function () {
    $jass = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);
    $vocal = User::factory()->vocal($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    Carbon::setTestNow('2026-01-01');
    $socio = Socio::factory()->create();
    Vivienda::factory()->create(['socio_id' => $socio->id]);

    Carbon::setTestNow('2026-03-20');
    $this->actingAs($vocal);

    $response = $this->get(route('panel.morosidad.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('puedeGestionar', false));
});
