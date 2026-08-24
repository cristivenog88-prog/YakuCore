<?php

namespace Database\Seeders;

use App\Enums\TipoComprobante;
use App\Models\Anuncio;
use App\Models\Gasto;
use App\Models\Jass;
use App\Models\Medidor;
use App\Models\Reunion;
use App\Models\Socio;
use App\Models\User;
use App\Models\Vivienda;
use App\Services\Pagos\CalculadoraConsumo;
use App\Services\Pagos\RegistradorPago;
use App\Services\Reuniones\RegistradorAsistencia;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Datos demo realistas (Fase 9 tarea 4) para las dos JASS creadas en
 * DatabaseSeeder: socios/viviendas/medidores, pagos de varios meses (con
 * algunas viviendas impagas a propósito para mostrar morosidad), gastos,
 * reuniones con asistencia ya tomada, y anuncios. Pasa siempre por los
 * servicios reales (RegistradorPago, RegistradorAsistencia) para que
 * correlativos, auditoría y estado de morosidad queden consistentes.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $jassConMedidor = Jass::where('nombre', 'Villa Sol')->firstOrFail();
        $jassSinMedidor = Jass::where('nombre', 'Los Andes')->firstOrFail();

        $this->poblarJass($jassConMedidor);
        $this->poblarJass($jassSinMedidor);

        Auth::logout();
        app(ContextoJass::class)->limpiar();
    }

    private function poblarJass(Jass $jass): void
    {
        app(ContextoJass::class)->fijar($jass);
        $presidente = User::where('jass_id', $jass->id)->where('rol', 'presidente')->firstOrFail();
        Auth::login($presidente);

        $socios = collect(range(1, 10))->map(fn () => Socio::factory()->create());

        $viviendasPorSocio = $socios->mapWithKeys(function (Socio $socio, int $indice) use ($jass) {
            $cantidad = $indice === 0 ? 2 : 1;
            $viviendas = collect(range(1, $cantidad))->map(fn () => Vivienda::factory()->create(['socio_id' => $socio->id]));

            if ($jass->tiene_medidor) {
                $viviendas->each(fn (Vivienda $vivienda, int $i) => Medidor::factory()->create([
                    'vivienda_id' => $vivienda->id,
                    'correlativo' => $vivienda->id * 10 + $i,
                ]));
            }

            return [$socio->id => $viviendas];
        });

        $registrador = app(RegistradorPago::class);
        $meses = collect(range(4, 0))->map(fn (int $atras) => Carbon::now()->subMonths($atras));

        foreach ($viviendasPorSocio as $socioId => $viviendas) {
            $socio = $socios->firstWhere('id', $socioId);

            foreach ($viviendas as $indiceVivienda => $vivienda) {
                foreach ($meses as $indiceMes => $mes) {
                    // Una vivienda de cada tres se queda sin pagar los dos
                    // meses más recientes, para que la Vista Morosidad y el
                    // KPI de morosidad del dashboard tengan contenido real
                    // sin que el 100% de las viviendas aparezca como morosa.
                    $esViviendaMorosa = ($socioId + $indiceVivienda) % 3 === 0;
                    if ($esViviendaMorosa && $indiceMes >= $meses->count() - 2) {
                        continue;
                    }

                    $fechaPago = $mes->copy()->day(min(10, $mes->daysInMonth))->toDateString();
                    $registrador->mantenimiento($socio, $vivienda, $mes->format('Y-m'), fake()->randomFloat(2, 10, 20), $fechaPago);

                    if ($jass->tiene_medidor) {
                        $medidor = $vivienda->medidor()->first();
                        $lecturaAnterior = app(CalculadoraConsumo::class)->lecturaAnterior($medidor);
                        $registrador->consumoMedidor($socio, $medidor, $mes->format('Y-m'), $lecturaAnterior + fake()->randomFloat(2, 3, 12), $fechaPago);
                    } else {
                        $registrador->consumoDirecto($socio, $vivienda, $mes->format('Y-m'), fake()->randomFloat(2, 8, 18), $fechaPago);
                    }
                }
            }
        }

        foreach (range(0, 3) as $mesesAtras) {
            Gasto::factory()->create([
                'usuario_id' => $presidente->id,
                'monto' => fake()->randomFloat(2, 30, 300),
                'tipo_comprobante' => fake()->randomElement(TipoComprobante::cases()),
            ])->forceFill(['created_at' => Carbon::now()->subMonths($mesesAtras)->day(15)])->save();
        }

        $reunionPasada1 = Reunion::factory()->create([
            'titulo' => 'Asamblea general ordinaria',
            'fecha' => Carbon::now()->subMonths(2)->toDateString(),
        ]);

        $reunionPasada2 = Reunion::factory()->create([
            'titulo' => 'Rendición de cuentas del trimestre',
            'fecha' => Carbon::now()->subMonth()->toDateString(),
        ]);

        $registradorAsistencia = app(RegistradorAsistencia::class);
        foreach ([$reunionPasada1, $reunionPasada2] as $reunion) {
            $asistencias = $socios->mapWithKeys(fn (Socio $socio) => [$socio->id => fake()->randomElement(['presente', 'presente', 'presente', 'ausente'])])->all();
            $registradorAsistencia->registrar($reunion, $asistencias);
        }

        Reunion::factory()->create([
            'titulo' => 'Próxima asamblea general',
            'fecha' => Carbon::now()->addWeeks(2)->toDateString(),
        ]);

        Anuncio::factory()->create([
            'usuario_id' => $presidente->id,
            'titulo' => 'Corte de agua programado',
            'contenido' => "El día sábado se realizará mantenimiento de la red principal.\nEl servicio se restablecerá el mismo día por la tarde.",
            'estado' => 'visible',
        ]);

        Anuncio::factory()->create([
            'usuario_id' => $presidente->id,
            'titulo' => 'Resultado de la última asamblea',
            'contenido' => 'Se aprobó el presupuesto para el mantenimiento de la captación.',
            'estado' => 'visible',
        ]);

        Anuncio::factory()->create([
            'usuario_id' => $presidente->id,
            'titulo' => 'Convocatoria anterior',
            'estado' => 'inactivo',
        ]);

        Auth::logout();
    }
}
