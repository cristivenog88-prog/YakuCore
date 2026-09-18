<?php

use App\Models\Informe;
use App\Models\Jass;
use App\Models\Rendicion;
use App\Models\User;
use App\Services\Informe\GeneradorInforme;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Support\Facades\Storage;

test('no se puede generar informe desde una rendición en borrador', function () {
    Storage::fake('local');
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $r = Rendicion::factory()->create(['jass_id' => $jass->id, 'estado' => 'borrador']);

    expect(fn () => app(GeneradorInforme::class)->handle($r, $presidente))
        ->toThrow(InvalidArgumentException::class);
});

test('rendición aprobada genera informe con correlativo 1 y PDF en storage', function () {
    Storage::fake('local');
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $r = Rendicion::factory()->create([
        'jass_id' => $jass->id,
        'estado' => 'aprobada',
        'aprobada_at' => now(),
        'anio' => 2026,
        'trimestre' => 1,
    ]);

    $informe = app(GeneradorInforme::class)->handle($r, $presidente);

    expect($informe->numero)->toBe(1);
    expect($informe->anio)->toBe(2026);
    expect($informe->codigo)->toBe('INFORME N.° 0001-2026-JASS');
    expect($informe->pdf_path)->not->toBeNull();
    Storage::disk('local')->assertExists($informe->pdf_path);
});

test('regenerar sobre la misma rendición devuelve el existente sin duplicar', function () {
    Storage::fake('local');
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $r = Rendicion::factory()->create(['jass_id' => $jass->id, 'estado' => 'aprobada', 'aprobada_at' => now(), 'anio' => 2026]);

    $i1 = app(GeneradorInforme::class)->handle($r, $presidente);
    $i2 = app(GeneradorInforme::class)->handle($r, $presidente);

    expect($i1->id)->toBe($i2->id);
    expect(Informe::count())->toBe(1);
});

test('correlativo se autoincrementa dentro del mismo año por JASS', function () {
    Storage::fake('local');
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $r1 = Rendicion::factory()->create(['jass_id' => $jass->id, 'estado' => 'aprobada', 'aprobada_at' => now(), 'anio' => 2026, 'trimestre' => 1]);
    $r2 = Rendicion::factory()->create(['jass_id' => $jass->id, 'estado' => 'aprobada', 'aprobada_at' => now(), 'anio' => 2026, 'trimestre' => 2]);

    $i1 = app(GeneradorInforme::class)->handle($r1, $presidente);
    $i2 = app(GeneradorInforme::class)->handle($r2, $presidente);

    expect($i1->numero)->toBe(1);
    expect($i2->numero)->toBe(2);
});

test('correlativo reinicia por año', function () {
    Storage::fake('local');
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $r1 = Rendicion::factory()->create(['jass_id' => $jass->id, 'estado' => 'aprobada', 'aprobada_at' => now(), 'anio' => 2026, 'trimestre' => 4]);
    $r2 = Rendicion::factory()->create(['jass_id' => $jass->id, 'estado' => 'aprobada', 'aprobada_at' => now(), 'anio' => 2027, 'trimestre' => 1]);

    app(GeneradorInforme::class)->handle($r1, $presidente);
    $i2 = app(GeneradorInforme::class)->handle($r2, $presidente);

    expect($i2->numero)->toBe(1);
    expect($i2->anio)->toBe(2027);
});

test('presidente puede descargar PDF; usuario de otra JASS obtiene 404 por scope', function () {
    Storage::fake('local');
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jassA);
    $presidenteA = User::factory()->presidente($jassA)->create();
    $rA = Rendicion::factory()->create(['jass_id' => $jassA->id, 'estado' => 'aprobada', 'aprobada_at' => now()]);
    $informe = app(GeneradorInforme::class)->handle($rA, $presidenteA);

    $this->actingAs($presidenteA)->get(route('panel.informes.descargar', $informe->uuid))->assertStatus(200);

    app(ContextoJass::class)->fijar($jassB);
    $fiscalB = User::factory()->fiscal($jassB)->create();
    $this->actingAs($fiscalB)->get(route('panel.informes.descargar', $informe->uuid))->assertStatus(404);
});

test('vocal no puede generar informe (solo presidente/tesorero)', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $vocal = User::factory()->vocal($jass)->create();
    $r = Rendicion::factory()->create(['jass_id' => $jass->id, 'estado' => 'aprobada', 'aprobada_at' => now()]);

    $this->actingAs($vocal)->post(route('panel.informes.store', $r->uuid))->assertStatus(403);
});
