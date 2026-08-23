<?php

use App\Models\Jass;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'jass'])->get('/_prueba/contexto-jass', function () {
        return response()->json(['jass_id' => app(ContextoJass::class)->id()]);
    });
});

test('fija el contexto con la jass_id del usuario autenticado', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $response = $this->actingAs($presidente)->get('/_prueba/contexto-jass');

    $response->assertJson(['jass_id' => $jass->id]);
});

test('sin usuario con jass_id responde 403', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)->get('/_prueba/contexto-jass')->assertForbidden();
});
