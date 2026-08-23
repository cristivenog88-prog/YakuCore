<?php

use App\Models\Jass;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Pagos\RegistradorPago;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

test('el dashboard superadmin agrega correctamente los datos de todas las JASS sin mezclarlos', function () {
    Carbon::setTestNow('2026-03-15');

    $jassA = Jass::factory()->create(['nombre' => 'Villa Sol']);
    app(ContextoJass::class)->fijar($jassA);
    $presidenteA = User::factory()->presidente($jassA)->create();
    $socioA = Socio::factory()->create();
    $viviendaA = Vivienda::factory()->create(['socio_id' => $socioA->id]);
    $this->actingAs($presidenteA);
    app(RegistradorPago::class)->mantenimiento($socioA, $viviendaA, '2026-03', 40, '2026-03-10');

    $jassB = Jass::factory()->create(['nombre' => 'Los Andes']);
    app(ContextoJass::class)->fijar($jassB);
    $presidenteB = User::factory()->presidente($jassB)->create();
    $socioB1 = Socio::factory()->create();
    $socioB2 = Socio::factory()->create();
    Vivienda::factory()->create(['socio_id' => $socioB1->id]);
    Vivienda::factory()->create(['socio_id' => $socioB2->id]);

    $superadmin = User::factory()->superadmin()->create();

    $response = $this->actingAs($superadmin)->get(route('superadmin.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('totales.jass', 2)
        ->where('totales.socios', 3)
        ->where('totales.recaudacionAnio', 40)
    );

    $filaA = collect($response->viewData('page')['props']['jass'])->firstWhere('nombre', 'Villa Sol');
    $filaB = collect($response->viewData('page')['props']['jass'])->firstWhere('nombre', 'Los Andes');

    expect($filaA['socios'])->toBe(1)
        ->and((float) $filaA['recaudacionAnio'])->toBe(40.0)
        ->and($filaB['socios'])->toBe(2)
        ->and((float) $filaB['recaudacionAnio'])->toBe(0.0);
});

test('presidente y tesorero no pueden acceder al dashboard superadmin', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $this->actingAs($presidente)->get(route('superadmin.dashboard'))->assertForbidden();
});
