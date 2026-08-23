<?php

use App\Enums\Rol;
use App\Models\Jass;
use App\Models\User;

test('superadmin y presidente pueden ver el listado; tesorero y vocal no', function () {
    $superadmin = User::factory()->superadmin()->create();
    $presidente = User::factory()->presidente()->create();
    $tesorero = User::factory()->tesorero()->create();
    $vocal = User::factory()->vocal()->create();

    expect($superadmin->can('viewAny', User::class))->toBeTrue();
    expect($presidente->can('viewAny', User::class))->toBeTrue();
    expect($tesorero->can('viewAny', User::class))->toBeFalse();
    expect($vocal->can('viewAny', User::class))->toBeFalse();
});

test('presidente solo puede ver/editar/eliminar tesorero y vocal de su propia JASS', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    $presidenteA = User::factory()->presidente($jassA)->create();
    $tesoreroA = User::factory()->tesorero($jassA)->create();
    $vocalA = User::factory()->vocal($jassA)->create();
    $otroPresidenteA = User::factory()->presidente($jassA)->create();
    $tesoreroB = User::factory()->tesorero($jassB)->create();

    expect($presidenteA->can('view', $tesoreroA))->toBeTrue();
    expect($presidenteA->can('view', $vocalA))->toBeTrue();
    expect($presidenteA->can('update', $tesoreroA))->toBeTrue();
    expect($presidenteA->can('delete', $vocalA))->toBeTrue();

    // no puede tocar a otro presidente, aunque sea de su misma JASS
    expect($presidenteA->can('view', $otroPresidenteA))->toBeFalse();

    // no puede tocar usuarios de otra JASS (scoping cruzado)
    expect($presidenteA->can('view', $tesoreroB))->toBeFalse();
    expect($presidenteA->can('update', $tesoreroB))->toBeFalse();
    expect($presidenteA->can('delete', $tesoreroB))->toBeFalse();
});

test('superadmin puede ver/editar/eliminar cualquier usuario de cualquier JASS', function () {
    $jass = Jass::factory()->create();
    $superadmin = User::factory()->superadmin()->create();
    $presidente = User::factory()->presidente($jass)->create();

    expect($superadmin->can('view', $presidente))->toBeTrue();
    expect($superadmin->can('update', $presidente))->toBeTrue();
    expect($superadmin->can('delete', $presidente))->toBeTrue();
});

test('solo superadmin puede asignar el rol presidente', function () {
    $jass = Jass::factory()->create();
    $superadmin = User::factory()->superadmin()->create();
    $presidente = User::factory()->presidente($jass)->create();

    expect($superadmin->can('asignarRol', [User::class, Rol::Presidente, $jass->id]))->toBeTrue();
    expect($presidente->can('asignarRol', [User::class, Rol::Presidente, $jass->id]))->toBeFalse();
});

test('presidente solo puede asignar tesorero/vocal dentro de su propia JASS', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();
    $presidenteA = User::factory()->presidente($jassA)->create();

    expect($presidenteA->can('asignarRol', [User::class, Rol::Tesorero, $jassA->id]))->toBeTrue();
    expect($presidenteA->can('asignarRol', [User::class, Rol::Vocal, $jassA->id]))->toBeTrue();
    expect($presidenteA->can('asignarRol', [User::class, Rol::Tesorero, $jassB->id]))->toBeFalse();
    expect($presidenteA->can('asignarRol', [User::class, Rol::Superadmin, $jassA->id]))->toBeFalse();
});
