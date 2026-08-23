<?php

use App\Models\Jass;
use App\Models\Medidor;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Publico\ConsultaDeuda;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Support\Facades\RateLimiter;

test('el dni inexistente falla y cuenta como intento fallido', function () {
    $jass = Jass::factory()->sinMedidor()->create();

    $response = $this->post(route('portal.deuda.store', $jass->id), ['dni' => '99999999']);

    $response->assertSessionHasErrors('dni');
    expect(session('resultado'))->toBeNull();
});

test('un dni válido sin medidor requerido devuelve el estado de cuenta', function () {
    $jass = Jass::factory()->sinMedidor()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $this->actingAs($presidente);
    $socio = Socio::factory()->create(['dni' => '11223344']);
    Vivienda::factory()->create(['socio_id' => $socio->id]);

    $response = $this->post(route('portal.deuda.store', $jass->id), ['dni' => '11223344']);

    $response->assertSessionHas('resultado');
    $response->assertSessionHasNoErrors();
});

test('con medidor, un correlativo que no corresponde al dni falla', function () {
    $jass = Jass::factory()->conMedidor()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $this->actingAs($presidente);

    $socio = Socio::factory()->create(['dni' => '55667788']);
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    Medidor::factory()->create(['vivienda_id' => $vivienda->id, 'correlativo' => 1]);

    $otroSocio = Socio::factory()->create();
    $otraVivienda = Vivienda::factory()->create(['socio_id' => $otroSocio->id]);
    Medidor::factory()->create(['vivienda_id' => $otraVivienda->id, 'correlativo' => 2]);

    $response = $this->post(route('portal.deuda.store', $jass->id), [
        'dni' => '55667788',
        'correlativo_medidor' => '2',
    ]);

    $response->assertSessionHasErrors('correlativo_medidor');
});

test('con medidor, el correlativo correcto devuelve el estado de cuenta', function () {
    $jass = Jass::factory()->conMedidor()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $this->actingAs($presidente);

    $socio = Socio::factory()->create(['dni' => '55667788']);
    $vivienda = Vivienda::factory()->create(['socio_id' => $socio->id]);
    Medidor::factory()->create(['vivienda_id' => $vivienda->id, 'correlativo' => 7]);

    $response = $this->post(route('portal.deuda.store', $jass->id), [
        'dni' => '55667788',
        'correlativo_medidor' => '7',
    ]);

    $response->assertSessionHas('resultado');
    $response->assertSessionHasNoErrors();
});

test('tras 3 intentos fallidos con el mismo dni se bloquea por 15 minutos', function () {
    $jass = Jass::factory()->sinMedidor()->create();

    for ($i = 0; $i < 3; $i++) {
        $this->post(route('portal.deuda.store', $jass->id), ['dni' => '99999999']);
    }

    $response = $this->post(route('portal.deuda.store', $jass->id), ['dni' => '99999999']);

    $response->assertSessionHasErrors('dni');
    expect(session('errors')->get('dni')[0])->toContain('Demasiados intentos');
});

test('un intento fallido con otro dni no se ve afectado por el bloqueo de un dni distinto', function () {
    $jass = Jass::factory()->sinMedidor()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $this->actingAs($presidente);
    $socio = Socio::factory()->create(['dni' => '22334455']);
    Vivienda::factory()->create(['socio_id' => $socio->id]);

    for ($i = 0; $i < 3; $i++) {
        $this->post(route('portal.deuda.store', $jass->id), ['dni' => '99999999']);
    }

    $response = $this->post(route('portal.deuda.store', $jass->id), ['dni' => '22334455']);

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('resultado');
});

test('un intento exitoso limpia el contador de intentos fallidos previos', function () {
    $jass = Jass::factory()->sinMedidor()->create();
    app(ContextoJass::class)->fijar($jass);
    $presidente = User::factory()->presidente($jass)->create();
    $this->actingAs($presidente);
    $socio = Socio::factory()->create(['dni' => '22334455']);
    Vivienda::factory()->create(['socio_id' => $socio->id]);

    $this->post(route('portal.deuda.store', $jass->id), ['dni' => '00000000']);
    $this->post(route('portal.deuda.store', $jass->id), ['dni' => '22334455']);

    $clave = app(ConsultaDeuda::class)->clave('127.0.0.1', $jass->id, '22334455');
    expect(RateLimiter::attempts($clave))->toBe(0);
});
