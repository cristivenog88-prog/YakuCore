<?php

use App\Models\Auditoria;
use App\Models\Gasto;
use App\Models\Jass;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function datosGastoValidos(array $sobrescribir = []): array
{
    return array_merge([
        'descripcion' => 'Compra de cloro para tratamiento',
        'proveedor' => 'Química Andina SAC',
        'fecha_compra' => '2026-03-10',
        'monto' => '150.50',
        'tipo_comprobante' => 'boleta',
    ], $sobrescribir);
}

test('presidente puede registrar un gasto sin foto y queda auditado', function () {
    Storage::fake('local');
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $response = $this->actingAs($presidente)->post(route('panel.gastos.store'), datosGastoValidos());

    $response->assertRedirect(route('panel.gastos.index'));
    $gasto = Gasto::where('descripcion', 'Compra de cloro para tratamiento')->firstOrFail();
    expect($gasto->jass_id)->toBe($jass->id)
        ->and($gasto->foto_path)->toBeNull()
        ->and((float) $gasto->monto)->toBe(150.50);

    $existeAuditoria = Auditoria::withoutGlobalScope(TenantScope::class)
        ->where('accion', 'crear')->where('entidad_tipo', Gasto::class)->where('entidad_id', $gasto->id)->exists();
    expect($existeAuditoria)->toBeTrue();
});

test('el comprobante se guarda en el disco privado bajo comprobantes/{jass_id}', function () {
    Storage::fake('local');
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $foto = UploadedFile::fake()->image('boleta.jpg');

    $this->actingAs($presidente)->post(route('panel.gastos.store'), datosGastoValidos(['foto' => $foto]))
        ->assertRedirect(route('panel.gastos.index'));

    $gasto = Gasto::firstOrFail();
    expect($gasto->foto_path)->toStartWith("comprobantes/{$jass->id}/");
    Storage::disk('local')->assertExists($gasto->foto_path);
});

test('el comprobante rechaza archivos que no son jpg/jpeg/png/webp/pdf', function () {
    Storage::fake('local');
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $archivo = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');

    $response = $this->actingAs($presidente)->post(route('panel.gastos.store'), datosGastoValidos(['foto' => $archivo]));

    $response->assertSessionHasErrors('foto');
    expect(Gasto::count())->toBe(0);
});

test('el comprobante rechaza archivos mayores a 5 MB', function () {
    Storage::fake('local');
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $archivo = UploadedFile::fake()->create('boleta.pdf', 6000, 'application/pdf');

    $response = $this->actingAs($presidente)->post(route('panel.gastos.store'), datosGastoValidos(['foto' => $archivo]));

    $response->assertSessionHasErrors('foto');
});

test('el monto debe ser mayor a 0', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $response = $this->actingAs($presidente)->post(route('panel.gastos.store'), datosGastoValidos(['monto' => '0']));

    $response->assertSessionHasErrors('monto');
});

test('vocal puede ver el listado de gastos pero no registrar ni eliminar', function () {
    $jass = Jass::factory()->create();
    $vocal = User::factory()->vocal($jass)->create();

    $this->actingAs($vocal)->get(route('panel.gastos.index'))->assertOk();
    $this->actingAs($vocal)->get(route('panel.gastos.create'))->assertForbidden();
    $this->actingAs($vocal)->post(route('panel.gastos.store'), datosGastoValidos())->assertForbidden();
});

test('eliminar un gasto exige confirmar la contraseña y queda soft-deleted', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    app(ContextoJass::class)->fijar($jass);
    $gasto = Gasto::factory()->create();

    $this->actingAs($presidente)->delete(route('panel.gastos.destroy', $gasto))
        ->assertRedirect(route('password.confirm'));

    session(['auth.password_confirmed_at' => time()]);
    $this->actingAs($presidente)->delete(route('panel.gastos.destroy', $gasto))
        ->assertRedirect(route('panel.gastos.index'));

    $this->assertSoftDeleted($gasto);
});

test('el comprobante no es accesible desde otra JASS', function () {
    Storage::fake('local');
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();
    $presidenteA = User::factory()->presidente($jassA)->create();
    $presidenteB = User::factory()->presidente($jassB)->create();

    $foto = UploadedFile::fake()->image('boleta.jpg');
    $this->actingAs($presidenteA)->post(route('panel.gastos.store'), datosGastoValidos(['foto' => $foto]));
    $gasto = Gasto::firstOrFail();

    $this->actingAs($presidenteB)->get(route('panel.gastos.comprobante', $gasto))->assertNotFound();
});

test('el dueño de la JASS puede descargar su propio comprobante', function () {
    Storage::fake('local');
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();

    $foto = UploadedFile::fake()->image('boleta.jpg');
    $this->actingAs($presidente)->post(route('panel.gastos.store'), datosGastoValidos(['foto' => $foto]));
    $gasto = Gasto::firstOrFail();

    $this->actingAs($presidente)->get(route('panel.gastos.comprobante', $gasto))->assertOk();
});

test('presidente puede editar un gasto dentro de las 24 horas', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $gasto = Gasto::factory()->create(['descripcion' => 'Original']);

    $response = $this->actingAs($presidente)->put(route('panel.gastos.update', $gasto), datosGastoValidos(['descripcion' => 'Corregido']));

    $response->assertRedirect(route('panel.gastos.index'));
    expect($gasto->fresh()->descripcion)->toBe('Corregido');
});

test('pasadas 24 horas del registro, editar o eliminar un gasto queda prohibido', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);
    $gasto = Gasto::factory()->create();
    $gasto->forceFill(['created_at' => now()->subDay()->subMinute()])->save();

    $this->actingAs($presidente)->get(route('panel.gastos.edit', $gasto))->assertForbidden();
    $this->actingAs($presidente)->put(route('panel.gastos.update', $gasto), datosGastoValidos())->assertForbidden();
});

test('el listado agrupa los totales por mes', function () {
    $jass = Jass::factory()->create();
    $presidente = User::factory()->presidente($jass)->create();
    app(ContextoJass::class)->fijar($jass);

    $enero = Gasto::factory()->create(['monto' => 100]);
    $enero->forceFill(['created_at' => '2026-01-10'])->save();

    $febrero = Gasto::factory()->create(['monto' => 50]);
    $febrero->forceFill(['created_at' => '2026-02-05'])->save();

    $response = $this->actingAs($presidente)->get(route('panel.gastos.index', ['anio' => 2026]));

    $response->assertInertia(fn ($page) => $page
        ->where('totalesPorMes.2026-01', 100)
        ->where('totalesPorMes.2026-02', 50)
        ->where('totalAnio', 150)
    );
});
