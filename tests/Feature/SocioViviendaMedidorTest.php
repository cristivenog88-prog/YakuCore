<?php

use App\Models\Jass;
use App\Models\Medidor;
use App\Models\Socio;
use App\Models\Vivienda;
use App\Services\Tenancy\ContextoJass;

test('socio, vivienda y medidor heredan la jass_id del contexto activo', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    $socio = Socio::factory()->create();
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    $medidor = Medidor::factory()->create(['vivienda_id' => $vivienda->id, 'correlativo' => 12]);

    expect($socio->jass_id)->toBe($jass->id);
    expect($vivienda->jass_id)->toBe($jass->id);
    expect($medidor->jass_id)->toBe($jass->id);
});

test('el código del medidor se autogenera con el prefijo de la JASS', function () {
    $jass = Jass::factory()->create(['nombre' => 'Caraz']);
    app(ContextoJass::class)->fijar($jass);

    $vivienda = Vivienda::factory()->create();
    $medidor = Medidor::factory()->create(['vivienda_id' => $vivienda->id, 'correlativo' => 7]);

    expect($medidor->codigo)->toBe('JASSCaraz-MED-7');
});

test('socios y viviendas quedan scopeados por JASS (scoping cruzado)', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    app(ContextoJass::class)->fijar($jassA);
    Socio::factory()->create(['nombres' => 'De A']);

    app(ContextoJass::class)->fijar($jassB);
    Socio::factory()->create(['nombres' => 'De B']);

    app(ContextoJass::class)->fijar($jassA);
    $visibles = Socio::all();

    expect($visibles)->toHaveCount(1);
    expect($visibles->first()->nombres)->toBe('De A');
});

test('una vivienda puede tener un socio, y un socio puede tener varias viviendas', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    $socio = Socio::factory()->create();
    Vivienda::factory()->create(['socio_id' => $socio->id]);
    Vivienda::factory()->create(['socio_id' => $socio->id]);

    expect($socio->viviendas)->toHaveCount(2);
});
