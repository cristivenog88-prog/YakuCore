<?php

use App\Models\Gasto;
use App\Models\Jass;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;

test('presidente y tesorero pueden crear/editar/eliminar gastos de su JASS; vocal y superadmin no', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $gasto = Gasto::factory()->create();

    $presidente = User::factory()->presidente($jass)->create();
    $tesorero = User::factory()->tesorero($jass)->create();
    $vocal = User::factory()->vocal($jass)->create();
    $superadmin = User::factory()->superadmin()->create();

    expect($presidente->can('create', Gasto::class))->toBeTrue();
    expect($tesorero->can('create', Gasto::class))->toBeTrue();
    expect($vocal->can('create', Gasto::class))->toBeFalse();
    expect($superadmin->can('create', Gasto::class))->toBeFalse();

    expect($presidente->can('update', $gasto))->toBeTrue();
    expect($presidente->can('delete', $gasto))->toBeTrue();
    expect($vocal->can('update', $gasto))->toBeFalse();
    expect($superadmin->can('update', $gasto))->toBeFalse();
});

test('todos los roles pueden ver el listado, incluido superadmin de solo lectura', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $gasto = Gasto::factory()->create();

    $vocal = User::factory()->vocal($jass)->create();
    $superadmin = User::factory()->superadmin()->create();

    expect($vocal->can('viewAny', Gasto::class))->toBeTrue();
    expect($vocal->can('view', $gasto))->toBeTrue();
    expect($superadmin->can('viewAny', Gasto::class))->toBeTrue();
    expect($superadmin->can('view', $gasto))->toBeTrue();
});

test('presidente no puede ver ni gestionar un gasto de otra JASS', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    app(ContextoJass::class)->fijar($jassB);
    $gastoB = Gasto::factory()->create();

    $presidenteA = User::factory()->presidente($jassA)->create();

    expect($presidenteA->can('view', $gastoB))->toBeFalse();
    expect($presidenteA->can('update', $gastoB))->toBeFalse();
    expect($presidenteA->can('delete', $gastoB))->toBeFalse();
});
