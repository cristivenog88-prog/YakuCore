<?php

use App\Models\Jass;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

test('superadmin puede entrar en modo supervisión a cualquier JASS', function () {
    $superadmin = User::factory()->superadmin()->create();
    $jass = Jass::factory()->create();

    $this->actingAs($superadmin)->get(route('supervision.dashboard', $jass))->assertOk();
});

test('presidente, tesorero y vocal no pueden entrar a /supervision', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente()->create();
    $tesorero = User::factory()->tesorero()->create();
    $vocal = User::factory()->vocal()->create();

    $this->actingAs($presidente)->get(route('supervision.dashboard', $jass))->assertForbidden();
    $this->actingAs($tesorero)->get(route('supervision.dashboard', $jass))->assertForbidden();
    $this->actingAs($vocal)->get(route('supervision.dashboard', $jass))->assertForbidden();
});

test('el contexto de JASS activo en supervisión es el de la ruta, no el del superadmin', function () {
    $superadmin = User::factory()->superadmin()->create();
    $jass = Jass::factory()->create();

    Route::middleware(['web', 'auth', 'rol:superadmin', 'jass'])->prefix('supervision/{jass}')->get('/_prueba/contexto', function (Request $request, Jass $jass) {
        return response()->json(['jass_id' => app(ContextoJass::class)->id()]);
    });

    $response = $this->actingAs($superadmin)->get("/supervision/{$jass->uuid}/_prueba/contexto");

    $response->assertJson(['jass_id' => $jass->id]);
});
