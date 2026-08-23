<?php

use App\Models\Jass;
use App\Models\Medidor;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;

test('presidente y tesorero pueden crear/editar medidores de su JASS; vocal y superadmin no', function () {
    $jass = Jass::factory()->conMedidor()->create();
    app(ContextoJass::class)->fijar($jass);
    $medidor = Medidor::factory()->create();

    $presidente = User::factory()->presidente($jass)->create();
    $tesorero = User::factory()->tesorero($jass)->create();
    $vocal = User::factory()->vocal($jass)->create();
    $superadmin = User::factory()->superadmin()->create();

    expect($presidente->can('create', Medidor::class))->toBeTrue();
    expect($tesorero->can('create', Medidor::class))->toBeTrue();
    expect($vocal->can('create', Medidor::class))->toBeFalse();
    expect($superadmin->can('create', Medidor::class))->toBeFalse();

    expect($presidente->can('update', $medidor))->toBeTrue();
    expect($vocal->can('update', $medidor))->toBeFalse();
});

test('presidente no puede gestionar medidores de otra JASS', function () {
    $jassA = Jass::factory()->conMedidor()->create();
    $jassB = Jass::factory()->conMedidor()->create();

    app(ContextoJass::class)->fijar($jassB);
    $medidorB = Medidor::factory()->create();

    $presidenteA = User::factory()->presidente($jassA)->create();

    expect($presidenteA->can('update', $medidorB))->toBeFalse();
});
