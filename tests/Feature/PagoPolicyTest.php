<?php

use App\Models\Jass;
use App\Models\Pago;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;

test('presidente y tesorero pueden cobrar; vocal y superadmin solo consultan', function () {
    $jass = Jass::factory()->create();
    app(ContextoJass::class)->fijar($jass);
    $pago = Pago::factory()->create();

    $presidente = User::factory()->presidente($jass)->create();
    $tesorero = User::factory()->tesorero($jass)->create();
    $vocal = User::factory()->vocal($jass)->create();
    $superadmin = User::factory()->superadmin()->create();

    expect($presidente->can('create', Pago::class))->toBeTrue();
    expect($tesorero->can('create', Pago::class))->toBeTrue();
    expect($vocal->can('create', Pago::class))->toBeFalse();
    expect($superadmin->can('create', Pago::class))->toBeFalse();

    expect($vocal->can('view', $pago))->toBeTrue();
    expect($superadmin->can('view', $pago))->toBeTrue();
});

test('presidente no puede ver pagos de otra JASS', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();

    app(ContextoJass::class)->fijar($jassB);
    $pagoB = Pago::factory()->create();

    $presidenteA = User::factory()->presidente($jassA)->create();

    expect($presidenteA->can('view', $pagoB))->toBeFalse();
});
