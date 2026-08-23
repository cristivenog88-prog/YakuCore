<?php

use App\Models\User;

test('superadmin no puede acceder a /panel', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)->get('/panel')->assertForbidden();
});

test('presidente, tesorero y vocal no pueden acceder a /superadmin', function () {
    $presidente = User::factory()->presidente()->create();
    $tesorero = User::factory()->tesorero()->create();
    $vocal = User::factory()->vocal()->create();

    $this->actingAs($presidente)->get('/superadmin')->assertForbidden();
    $this->actingAs($tesorero)->get('/superadmin')->assertForbidden();
    $this->actingAs($vocal)->get('/superadmin')->assertForbidden();
});
