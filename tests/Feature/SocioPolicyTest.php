<?php

use App\Models\Jass;
use App\Models\Socio;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;

test('presidente y tesorero pueden crear/editar/eliminar socios de su JASS; vocal y superadmin no', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();

    $presidente = User::factory()->presidente($jass)->create();
    $tesorero = User::factory()->tesorero($jass)->create();
    $vocal = User::factory()->vocal($jass)->create();
    $superadmin = User::factory()->superadmin()->create();

    expect($presidente->can('create', Socio::class))->toBeTrue();
    expect($tesorero->can('create', Socio::class))->toBeTrue();
    expect($vocal->can('create', Socio::class))->toBeFalse();
    expect($superadmin->can('create', Socio::class))->toBeFalse();

    expect($presidente->can('update', $socio))->toBeTrue();
    expect($vocal->can('update', $socio))->toBeFalse();
    expect($superadmin->can('update', $socio))->toBeFalse();
});

test('todos los roles pueden ver el listado y la ficha, incluido superadmin', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $socio = Socio::factory()->create();

    $vocal = User::factory()->vocal($jass)->create();
    $superadmin = User::factory()->superadmin()->create();

    expect($vocal->can('viewAny', Socio::class))->toBeTrue();
    expect($vocal->can('view', $socio))->toBeTrue();
    expect($superadmin->can('viewAny', Socio::class))->toBeTrue();
    expect($superadmin->can('view', $socio))->toBeTrue();
});

test('presidente no puede editar un socio de otra JASS', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    app(ContextoJass::class)->fijar($jassB);
    $socioB = Socio::factory()->create();

    $presidenteA = User::factory()->presidente($jassA)->create();

    expect($presidenteA->can('update', $socioB))->toBeFalse();
    expect($presidenteA->can('view', $socioB))->toBeFalse();
});
