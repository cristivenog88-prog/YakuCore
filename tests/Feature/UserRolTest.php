<?php

use App\Enums\Rol;
use App\Models\Jass;
use App\Models\User;

test('un superadmin no puede tener jass_id', function () {
    $jass = Jass::factory()->create();

    expect(fn () => User::factory()->create(['rol' => Rol::Superadmin, 'jass_id' => $jass->id]))
        ->toThrow(InvalidArgumentException::class);
});

test('un usuario no superadmin debe tener jass_id', function () {
    expect(fn () => User::factory()->create(['rol' => Rol::Presidente, 'jass_id' => null]))
        ->toThrow(InvalidArgumentException::class);
});

test('los helpers de rol identifican correctamente al usuario', function () {
    $superadmin = User::factory()->superadmin()->create();
    $presidente = User::factory()->presidente()->create();
    $tesorero = User::factory()->tesorero()->create();
    $vocal = User::factory()->vocal()->create();

    expect($superadmin->esSuperadmin())->toBeTrue();
    expect($superadmin->esPresidente())->toBeFalse();

    expect($presidente->esPresidente())->toBeTrue();
    expect($presidente->jass_id)->not->toBeNull();

    expect($tesorero->esTesorero())->toBeTrue();
    expect($vocal->esVocal())->toBeTrue();
});
