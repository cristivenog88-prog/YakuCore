<?php

use App\Models\Concerns\PerteneceAJass;
use App\Models\Jass;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo de prueba desechable para verificar PerteneceAJass + TenantScope
 * sin depender de modelos de negocio (Socio, Vivienda, ... llegan en Fase 2).
 */
function crearModeloDePruebaConTenancy(): Model
{
    return new class extends Model
    {
        use PerteneceAJass;

        protected $table = 'cosas_prueba_tenancy';

        protected $fillable = ['nombre', 'jass_id'];
    };
}

beforeEach(function () {
    Schema::create('cosas_prueba_tenancy', function ($table) {
        $table->id();
        $table->unsignedBigInteger('jass_id')->nullable();
        $table->string('nombre');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('cosas_prueba_tenancy');
});

test('asigna jass_id automáticamente desde ContextoJass al crear', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);

    $modelo = crearModeloDePruebaConTenancy()::create(['nombre' => 'Prueba']);

    expect($modelo->jass_id)->toBe($jass->id);
});

test('ignora cualquier jass_id enviado por mass assignment', function () {
    $jassContexto = Jass::factory()->create();
    $jassAtacante = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jassContexto);

    $modelo = crearModeloDePruebaConTenancy()::create([
        'nombre' => 'Intento de suplantar jass_id',
        'jass_id' => $jassAtacante->id,
    ]);

    expect($modelo->jass_id)->toBe($jassContexto->id);
    expect($modelo->jass_id)->not->toBe($jassAtacante->id);
});

test('el scope solo expone registros de la JASS activa', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    app(ContextoJass::class)->fijar($jassA);
    crearModeloDePruebaConTenancy()::create(['nombre' => 'De A']);

    app(ContextoJass::class)->fijar($jassB);
    crearModeloDePruebaConTenancy()::create(['nombre' => 'De B']);

    app(ContextoJass::class)->fijar($jassA);
    $modeloClase = crearModeloDePruebaConTenancy();
    $visibles = $modeloClase::query()->get();

    expect($visibles)->toHaveCount(1);
    expect($visibles->first()->nombre)->toBe('De A');
});

test('sin contexto de JASS fijado no se expone ningún registro', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    crearModeloDePruebaConTenancy()::create(['nombre' => 'Cualquiera']);

    app(ContextoJass::class)->limpiar();

    expect(crearModeloDePruebaConTenancy()::query()->count())->toBe(0);
});
