<?php

use App\Models\Jass;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Pagos\RegistradorPago;
use App\Services\Tenancy\ContextoJass;

test('la ficha del socio incluye el historial de pagos de todas sus viviendas', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $this->actingAs($presidente);

    $socio = Socio::factory()->create();
    $viviendaA = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $viviendaB = Vivienda::factory()->create(['socio_id' => $socio->id]);

    app(RegistradorPago::class)->mantenimiento($socio, $viviendaA, '2026-01', 10, '2026-01-05');
    app(RegistradorPago::class)->mantenimiento($socio, $viviendaB, '2026-02', 10, '2026-02-05');

    $response = $this->get(route('panel.socios.show', $socio));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('historialPagos', 2));
});

test('el historial no incluye pagos de otros socios', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $this->actingAs($presidente);

    $socioA = Socio::factory()->create();
    $viviendaA = Vivienda::factory()->create(['socio_id' => $socioA->id]);
    app(RegistradorPago::class)->mantenimiento($socioA, $viviendaA, '2026-01', 10, '2026-01-05');

    $socioB = Socio::factory()->create();
    $viviendaB = Vivienda::factory()->create(['socio_id' => $socioB->id]);
    app(RegistradorPago::class)->mantenimiento($socioB, $viviendaB, '2026-01', 10, '2026-01-05');

    $response = $this->get(route('panel.socios.show', $socioA));

    $response->assertInertia(fn ($page) => $page->has('historialPagos', 1));
});
