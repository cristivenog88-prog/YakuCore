<?php

use App\Models\Jass;
use App\Models\User;

test('el panel superadmin (/superadmin) muestra el dashboard con estadísticas globales', function () {
    $superadmin = User::factory()->superadmin()->create();
    $jass = Jass::factory()->create(['nombre' => 'Villa Sol']);

    $response = $this->actingAs($superadmin)->get(route('superadmin.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('superadmin/dashboard')
        ->has('jass', 1)
        ->where('jass.0.nombre', 'Villa Sol')
        ->has('totales')
    );
});

test('desde el listado se puede entrar a supervisar cualquier JASS', function () {
    $superadmin = User::factory()->superadmin()->create();
    $jass = Jass::factory()->create();

    $this->actingAs($superadmin)->get(route('supervision.dashboard', $jass))->assertOk();
});
