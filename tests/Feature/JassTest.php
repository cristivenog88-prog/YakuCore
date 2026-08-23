<?php

use App\Models\Jass;
use Illuminate\Database\QueryException;

test('el prefijo se autogenera a partir del nombre', function () {
    $jass = Jass::factory()->create(['nombre' => 'Huaraz']);

    expect($jass->prefijo)->toBe('JASSHuaraz');
});

test('dos nombres distintos que normalizan al mismo prefijo no colisionan', function () {
    // "Test Uno" y "test-uno" son valores distintos para el UNIQUE de nombre,
    // pero Str::studly() los normaliza al mismo resultado ("TestUno").
    $primera = Jass::factory()->create(['nombre' => 'Test Uno']);
    $segunda = Jass::factory()->create(['nombre' => 'test-uno']);

    expect($primera->prefijo)->toBe('JASSTestUno');
    expect($segunda->prefijo)->toBe('JASSTestUno2');
});

test('el prefijo no se sobrescribe si se asigna explícitamente', function () {
    $jass = Jass::factory()->create(['nombre' => 'Recuay', 'prefijo' => 'JASSPersonalizado']);

    expect($jass->prefijo)->toBe('JASSPersonalizado');
});

test('nombre y prefijo son únicos', function () {
    Jass::factory()->create(['nombre' => 'Caraz']);

    expect(fn () => Jass::factory()->create(['nombre' => 'Caraz']))
        ->toThrow(QueryException::class);
});
