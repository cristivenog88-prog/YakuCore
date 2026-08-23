<?php

use App\Models\Auditoria;
use App\Models\Jass;
use App\Models\User;
use App\Services\Auditoria\RegistradorAuditoria;
use App\Services\Tenancy\ContextoJass;

test('registra usuario, jass del contexto, ip y detalles', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);

    $this->actingAs($presidente);

    $registro = app(RegistradorAuditoria::class)->registrar('crear', $jass, ['nombre' => $jass->nombre]);

    expect($registro->usuario_id)->toBe($presidente->id);
    expect($registro->jass_id)->toBe($jass->id);
    expect($registro->accion)->toBe('crear');
    expect($registro->entidad_tipo)->toBe(Jass::class);
    expect($registro->entidad_id)->toBe($jass->id);
    expect($registro->detalles)->toBe(['nombre' => $jass->nombre]);
});

test('una acción global de superadmin (sin contexto de JASS) audita jass_id null', function () {
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $jassCreada = Jass::factory()->create();

    $registro = app(RegistradorAuditoria::class)->registrar('crear', $jassCreada);

    expect($registro->jass_id)->toBeNull();
    expect($registro->usuario_id)->toBe($superadmin->id);
});

test('el modelo Auditoria no tiene updated_at', function () {
    expect((new Auditoria)->usesTimestamps())->toBeTrue();
    expect(Auditoria::UPDATED_AT)->toBeNull();
});
