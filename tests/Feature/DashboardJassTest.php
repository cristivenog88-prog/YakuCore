<?php

use App\Models\Gasto;
use App\Models\Jass;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Pagos\RegistradorPago;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

afterEach(function () {
    Carbon::setTestNow();
});

test('el dashboard de la JASS calcula los KPIs solo con sus propios datos', function () {
    $jassA = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual']);
    $jassB = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual']);

    Carbon::setTestNow('2026-03-15');

    app(ContextoJass::class)->fijar($jassA);
    $presidenteA = User::factory()->presidente($jassA)->create();
    $socioA = Socio::factory()->create();
    $viviendaA = Vivienda::factory()->create(['socio_id' => $socioA->id]);
    $this->actingAs($presidenteA);
    app(RegistradorPago::class)->mantenimiento($socioA, $viviendaA, '2026-03', 50, '2026-03-10');
    Gasto::factory()->create(['monto' => 20]);

    app(ContextoJass::class)->fijar($jassB);
    $presidenteB = User::factory()->presidente($jassB)->create();
    $socioB = Socio::factory()->create();
    $viviendaB = Vivienda::factory()->create(['socio_id' => $socioB->id]);
    $this->actingAs($presidenteB);
    app(RegistradorPago::class)->mantenimiento($socioB, $viviendaB, '2026-03', 999, '2026-03-10');

    $response = $this->actingAs($presidenteA)->get(route('panel.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('estadisticas.sociosActivos', 1)
        ->where('estadisticas.viviendasActivas', 1)
        ->where('estadisticas.recaudadoMes.mantenimiento', 50)
        ->where('estadisticas.recaudadoAnio.mantenimiento', 50)
        ->where('estadisticas.gastosMes', 20)
        ->where('estadisticas.balanceAnio', 30)
    );
});

test('el balance y la morosidad reflejan solo la JASS activa', function () {
    $jass = Jass::factory()->create(['periodicidad_mantenimiento' => 'mensual', 'dia_vencimiento' => 15]);
    app(ContextoJass::class)->fijar($jass);

    Carbon::setTestNow('2026-01-01');
    $presidente = User::factory()->presidente($jass)->create();
    $socio = Socio::factory()->create();
    Vivienda::factory()->create(['socio_id' => $socio->id]);

    Carbon::setTestNow('2026-03-20');
    Artisan::call('yaku:marcar-morosos');

    $response = $this->actingAs($presidente)->get(route('panel.dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->where('estadisticas.viviendasMorosas.cantidad', 1)
        ->where('estadisticas.viviendasMorosas.porcentaje', 100)
    );
});
