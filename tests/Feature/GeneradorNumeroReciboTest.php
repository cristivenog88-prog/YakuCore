<?php

use App\Models\Jass;
use App\Services\Pagos\GeneradorNumeroRecibo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * phpunit.xml fuerza DB_DATABASE=:memory: para toda la suite (incluida la
 * conexión "mysql"), así que aquí se define una conexión aparte con los
 * valores fijos del proyecto (CLAUDE.md: MySQL puerto 3307, BD BDyakucore)
 * para poder hablar con el MySQL real y probar concurrencia genuina.
 */
function configurarConexionMysqlReal(): void
{
    config(['database.connections.mysql_real' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3307',
        'database' => 'BDyakucore',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ]]);
}

function mysqlRealDisponible(): bool
{
    try {
        configurarConexionMysqlReal();
        DB::connection('mysql_real')->getPdo();

        return true;
    } catch (Throwable) {
        return false;
    }
}

test('el correlativo es secuencial por JASS', function () {
    $jass = Jass::factory()->create();
    $generador = app(GeneradorNumeroRecibo::class);

    expect($generador->generar($jass->id))->toBe(1);
    expect($generador->generar($jass->id))->toBe(2);
    expect($generador->generar($jass->id))->toBe(3);
});

test('el correlativo es independiente entre JASS distintas', function () {
    $jassA = Jass::factory()->create();
    $jassB = Jass::factory()->create();
    $generador = app(GeneradorNumeroRecibo::class);

    $generador->generar($jassA->id);
    $generador->generar($jassA->id);
    $numeroB = $generador->generar($jassB->id);

    expect($numeroB)->toBe(1);
});

test('el correlativo se comparte entre los tres tipos de pago (regla 5.8)', function () {
    // El generador no conoce el "tipo" — un mismo contador sirve a
    // mantenimiento, consumo_medidor y consumo_directo por igual.
    $jass = Jass::factory()->create();
    $generador = app(GeneradorNumeroRecibo::class);

    $numeros = [$generador->generar($jass->id), $generador->generar($jass->id), $generador->generar($jass->id)];

    expect($numeros)->toBe([1, 2, 3]);
});

/**
 * La garantía de "sin números duplicados ni huecos ante concurrencia" la da
 * lockForUpdate() sobre MySQL en producción. El resto de la suite corre
 * sobre SQLite :memory: (phpunit.xml), que no comparte conexión entre
 * procesos y no permite simular un bloqueo de fila real entre dos
 * transacciones concurrentes genuinas. Esta prueba sí lo hace: lanza dos
 * procesos de artisan tinker independientes contra la conexión mysql real
 * (BDyakucore), cada uno generando un número para la misma JASS, y verifica
 * que resultan en números consecutivos distintos. Limpia la fila creada.
 */
test('el correlativo no se duplica ante dos altas realmente concurrentes (MySQL)', function () {
    configurarConexionMysqlReal();

    $jassId = DB::connection('mysql_real')->table('jass')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'nombre' => 'Concurrencia '.uniqid(),
        'prefijo' => 'JASSConcurrencia'.uniqid(),
        'comunidad' => 'X',
        'distrito' => 'X',
        'tiene_medidor' => true,
        'tarifa_base_m3' => 2,
        'periodicidad_mantenimiento' => 'mensual',
        'dia_vencimiento' => 15,
        'ultimo_numero_recibo' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    try {
        $script = sprintf(
            'echo (new App\Services\Pagos\GeneradorNumeroRecibo)->generar(%d);',
            $jassId
        );

        $base = base_path();
        $comando = ['php', 'artisan', 'tinker', '--execute='.$script];

        // El proceso hijo hereda el entorno real de este proceso, incluido
        // el DB_DATABASE=:memory: que phpunit.xml fuerza para toda la
        // suite — hay que pisarlo explícitamente para que el hijo hable
        // con el MySQL real, no con :memory:.
        $envHijo = [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3307',
            'DB_DATABASE' => 'BDyakucore',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
            'DB_URL' => '',
        ];

        $procesoA = new Process($comando, $base, $envHijo);
        $procesoB = new Process($comando, $base, $envHijo);

        $procesoA->start();
        $procesoB->start();

        $procesoA->wait();
        $procesoB->wait();

        $extraerNumero = function (string $salida): ?int {
            return preg_match('/(\d+)/', trim($salida), $m) ? (int) $m[1] : null;
        };

        $numeroA = $extraerNumero($procesoA->getOutput());
        $numeroB = $extraerNumero($procesoB->getOutput());

        expect($numeroA)->not->toBeNull();
        expect($numeroB)->not->toBeNull();
        expect($numeroA)->not->toBe($numeroB);
        expect([$numeroA, $numeroB])->toEqualCanonicalizing([1, 2]);
    } finally {
        DB::connection('mysql_real')->table('jass')->where('id', $jassId)->delete();
    }
})->skip(fn () => ! mysqlRealDisponible(), 'Requiere MySQL disponible en 127.0.0.1:3307.');
