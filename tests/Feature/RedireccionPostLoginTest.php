<?php

use App\Models\User;

test('el superadmin es redirigido a /superadmin tras iniciar sesión', function () {
    $superadmin = User::factory()->superadmin()->create();

    $response = $this->post('/login', [
        'email' => $superadmin->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('superadmin.dashboard', absolute: false));
});

test('presidente, tesorero y vocal son redirigidos a /panel tras iniciar sesión', function () {
    $presidente = User::factory()->presidente()->create();
    $tesorero = User::factory()->tesorero()->create();
    $vocal = User::factory()->vocal()->create();

    foreach ([$presidente, $tesorero, $vocal] as $usuario) {
        $response = $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('panel.dashboard', absolute: false));

        $this->post('/logout');
    }
});

test('un usuario autenticado puede acceder a /panel y /superadmin', function () {
    $presidente = User::factory()->presidente()->create();
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($presidente)->get('/panel')->assertOk();
    $this->actingAs($superadmin)->get('/superadmin')->assertOk();
});

test('un invitado no puede acceder a /panel ni /superadmin', function () {
    $this->get('/panel')->assertRedirect('/login');
    $this->get('/superadmin')->assertRedirect('/login');
});
