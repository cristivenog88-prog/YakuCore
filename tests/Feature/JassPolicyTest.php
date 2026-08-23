<?php

use App\Models\Jass;
use App\Models\User;

test('solo superadmin tiene permisos sobre JASS', function () {
    $superadmin = User::factory()->superadmin()->create();
    $presidente = User::factory()->presidente()->create();
    $tesorero = User::factory()->tesorero()->create();
    $vocal = User::factory()->vocal()->create();
    $jass = Jass::factory()->create();

    foreach (['viewAny', 'create'] as $habilidad) {
        expect($superadmin->can($habilidad, Jass::class))->toBeTrue();
        expect($presidente->can($habilidad, Jass::class))->toBeFalse();
        expect($tesorero->can($habilidad, Jass::class))->toBeFalse();
        expect($vocal->can($habilidad, Jass::class))->toBeFalse();
    }

    foreach (['view', 'update', 'delete'] as $habilidad) {
        expect($superadmin->can($habilidad, $jass))->toBeTrue();
        expect($presidente->can($habilidad, $jass))->toBeFalse();
        expect($tesorero->can($habilidad, $jass))->toBeFalse();
        expect($vocal->can($habilidad, $jass))->toBeFalse();
    }
});
