<?php

use App\Models\Auditoria;
use App\Models\Jass;
use App\Models\User;

test('superadmin, presidente y tesorero pueden ver el listado; vocal no', function () {
    $superadmin = User::factory()->superadmin()->create();
    $presidente = User::factory()->presidente()->create();
    $tesorero = User::factory()->tesorero()->create();
    $vocal = User::factory()->vocal()->create();

    expect($superadmin->can('viewAny', Auditoria::class))->toBeTrue();
    expect($presidente->can('viewAny', Auditoria::class))->toBeTrue();
    expect($tesorero->can('viewAny', Auditoria::class))->toBeTrue();
    expect($vocal->can('viewAny', Auditoria::class))->toBeFalse();
});

test('presidente solo puede ver auditoría de su propia JASS', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    $presidenteA = User::factory()->presidente($jassA)->create();

    $registroDeA = new Auditoria(['jass_id' => $jassA->id, 'usuario_id' => $presidenteA->id, 'accion' => 'crear', 'entidad_tipo' => 'X']);
    $registroDeB = new Auditoria(['jass_id' => $jassB->id, 'usuario_id' => $presidenteA->id, 'accion' => 'crear', 'entidad_tipo' => 'X']);

    expect($presidenteA->can('view', $registroDeA))->toBeTrue();
    expect($presidenteA->can('view', $registroDeB))->toBeFalse();
});

test('superadmin puede ver auditoría de cualquier JASS', function () {
    $superadmin = User::factory()->superadmin()->create();
    $jass = Jass::factory()->create();

    $registro = new Auditoria(['jass_id' => $jass->id, 'usuario_id' => $superadmin->id, 'accion' => 'crear', 'entidad_tipo' => 'X']);

    expect($superadmin->can('view', $registro))->toBeTrue();
});

test('nadie puede crear, editar ni eliminar auditoría vía policy, ni siquiera superadmin', function () {
    $superadmin = User::factory()->superadmin()->create();
    $jass = Jass::factory()->create();
    $registro = new Auditoria(['jass_id' => $jass->id, 'usuario_id' => $superadmin->id, 'accion' => 'crear', 'entidad_tipo' => 'X']);

    expect($superadmin->can('create', Auditoria::class))->toBeFalse();
    expect($superadmin->can('update', $registro))->toBeFalse();
    expect($superadmin->can('delete', $registro))->toBeFalse();
});
