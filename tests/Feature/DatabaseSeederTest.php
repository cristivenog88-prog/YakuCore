<?php

use App\Enums\Rol;
use App\Models\Jass;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('el seeder crea el superadmin, 2 JASS demo y presidente/tesorero/vocal de cada una', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(7);
    expect(Jass::count())->toBe(2);

    $superadmin = User::where('email', 'superadmin@yakucore.pe')->first();
    expect($superadmin)->not->toBeNull();
    expect($superadmin->rol)->toBe(Rol::Superadmin);
    expect($superadmin->jass_id)->toBeNull();

    $conMedidor = Jass::where('nombre', 'Villa Sol')->first();
    $sinMedidor = Jass::where('nombre', 'Los Andes')->first();

    expect($conMedidor->tiene_medidor)->toBeTrue();
    expect($sinMedidor->tiene_medidor)->toBeFalse();

    foreach ([$conMedidor, $sinMedidor] as $jass) {
        expect(User::where('jass_id', $jass->id)->where('rol', Rol::Presidente)->exists())->toBeTrue();
        expect(User::where('jass_id', $jass->id)->where('rol', Rol::Tesorero)->exists())->toBeTrue();
        expect(User::where('jass_id', $jass->id)->where('rol', Rol::Vocal)->exists())->toBeTrue();
    }
});
