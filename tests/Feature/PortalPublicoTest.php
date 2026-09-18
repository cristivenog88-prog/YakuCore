<?php

use App\Models\Anuncio;
use App\Models\Gasto;
use App\Models\Jass;
use App\Models\Reunion;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Pagos\RegistradorPago;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

test('el buscador público encuentra una JASS por nombre y solo expone campos básicos', function () {
    Jass::factory()->create(['nombre' => 'Villa Sol']);
    Jass::factory()->create(['nombre' => 'Los Andes']);

    $response = $this->get(route('buscar-jass', ['buscar' => 'Villa']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('resultados', 1)
        ->where('resultados.0.nombre', 'Villa Sol')
        ->missing('resultados.0.tarifa_base_m3')
        ->missing('resultados.0.ultimo_numero_recibo')
    );
});

test('la página pública de una JASS solo muestra anuncios visibles y reuniones programadas', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $this->actingAs($presidente);

    Anuncio::factory()->create(['titulo' => 'Corte de agua', 'estado' => 'visible']);
    Anuncio::factory()->create(['titulo' => 'Anuncio viejo', 'estado' => 'inactivo']);

    Reunion::factory()->create(['titulo' => 'Asamblea próxima', 'estado' => 'programada']);
    Reunion::factory()->create(['titulo' => 'Asamblea pasada', 'estado' => 'finalizada']);

    $response = $this->get(route('portal.jass.show', $jass));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('anuncios', 1)
        ->where('anuncios.0.titulo', 'Corte de agua')
        ->has('reuniones', 1)
        ->where('reuniones.0.titulo', 'Asamblea próxima')
    );
});

test('la transparencia pública de una JASS no mezcla datos de otra JASS', function () {
    Carbon::setTestNow('2026-03-15');

    $jassA = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jassA);
    $presidenteA = User::factory()->presidente($jassA)->create();
    $socioA = Socio::factory()->create();
    $viviendaA = Vivienda::factory()->create(['socio_id' => $socioA->id]);
    $this->actingAs($presidenteA);
    app(RegistradorPago::class)->mantenimiento($socioA, $viviendaA, '2026-03', 40, '2026-03-10');
    Gasto::factory()->create(['monto' => 15]);

    $jassB = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jassB);
    $presidenteB = User::factory()->presidente($jassB)->create();
    $socioB = Socio::factory()->create();
    $viviendaB = Vivienda::factory()->create(['socio_id' => $socioB->id]);
    $this->actingAs($presidenteB);
    app(RegistradorPago::class)->mantenimiento($socioB, $viviendaB, '2026-03', 999, '2026-03-10');

    $response = $this->get(route('portal.jass.show', ['jass' => $jassA, 'anio' => 2026]));

    $response->assertInertia(fn ($page) => $page
        ->where('transparencia.recaudadoMantenimiento', 40)
        ->where('transparencia.totalGastos', 15)
    );
});

test('el buscador de deuda no expone teléfono ni dirección', function () {
    $jass = Jass::factory()->sinMedidor()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $this->actingAs($presidente);
    $socio = Socio::factory()->create(['dni' => '87654321', 'telefono' => '999888777', 'direccion' => 'Jr. Secreto 123']);
    Vivienda::factory()->create(['socio_id' => $socio->id]);

    $response = $this->post(route('portal.deuda.store', $jass), ['dni' => '87654321']);

    $response->assertSessionHas('resultado');
    $resultado = collect(session('resultado'));

    expect($resultado)->toHaveCount(1);
    $primeraFila = $resultado->first();
    expect($primeraFila)->not->toHaveKey('telefono')
        ->and($primeraFila)->not->toHaveKey('direccion')
        ->and($primeraFila['vivienda'])->toBe('Vivienda 1');
});
