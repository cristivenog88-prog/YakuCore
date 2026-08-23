<?php

use App\Models\Jass;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Tenancy\ContextoJass;

test('presidente y tesorero pueden crear/editar/eliminar viviendas de su JASS; vocal y superadmin no', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $vivienda = Vivienda::factory()->create();

    $presidente = User::factory()->presidente($jass)->create();
    $tesorero = User::factory()->tesorero($jass)->create();
    $vocal = User::factory()->vocal($jass)->create();
    $superadmin = User::factory()->superadmin()->create();

    expect($presidente->can('create', Vivienda::class))->toBeTrue();
    expect($tesorero->can('create', Vivienda::class))->toBeTrue();
    expect($vocal->can('create', Vivienda::class))->toBeFalse();
    expect($superadmin->can('create', Vivienda::class))->toBeFalse();

    expect($presidente->can('update', $vivienda))->toBeTrue();
    expect($vocal->can('update', $vivienda))->toBeFalse();
});

test('presidente no puede gestionar viviendas de otra JASS', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    app(ContextoJass::class)->fijar($jassB);
    $viviendaB = Vivienda::factory()->create();

    $presidenteA = User::factory()->presidente($jassA)->create();

    expect($presidenteA->can('update', $viviendaB))->toBeFalse();
    expect($presidenteA->can('delete', $viviendaB))->toBeFalse();
});
