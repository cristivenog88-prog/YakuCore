<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Vivienda;
use App\Services\Morosidad\EstadoCuenta;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Listado de viviendas morosas (spec §6.8). No hay un "pago de morosidad"
 * separado: cobrar la deuda ES registrar el pago del periodo vencido
 * (Fase 3), así que esta vista solo lee y enlaza a los formularios de cobro
 * ya existentes con socio/vivienda/periodo precargados por query string.
 */
class MorosidadController extends Controller
{
    public function __construct(private readonly EstadoCuenta $estadoCuenta) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Pago::class);

        $jass = app(ContextoJass::class)->jass();
        $busqueda = trim((string) $request->query('buscar', ''));

        $viviendas = Vivienda::where('activa', true)
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->whereHas('socio', function ($sub) use ($busqueda) {
                    $sub->where('dni', 'like', "%{$busqueda}%")
                        ->orWhere('apellidos', 'like', "%{$busqueda}%");
                });
            })
            ->with(['socio:id,nombres,apellidos,dni', 'medidor:id,vivienda_id,codigo'])
            ->orderBy('id')
            ->get();

        $filas = $viviendas
            ->map(function (Vivienda $vivienda) use ($jass) {
                $pendientes = $this->estadoCuenta->periodosPendientes($vivienda);

                if ($pendientes['mantenimiento'] === [] && $pendientes['consumo'] === []) {
                    return null;
                }

                return [
                    'vivienda_id' => $vivienda->id,
                    'socio_id' => $vivienda->socio_id,
                    'socio' => "{$vivienda->socio->apellidos}, {$vivienda->socio->nombres}",
                    'dni' => $vivienda->socio->dni,
                    'direccion' => $vivienda->direccion,
                    'medidor_id' => $vivienda->medidor?->id,
                    'periodosMantenimiento' => $pendientes['mantenimiento'],
                    'periodosConsumo' => $pendientes['consumo'],
                    'tipoConsumo' => $jass->tiene_medidor ? 'consumo_medidor' : 'consumo_directo',
                    'morosaDesde' => $vivienda->morosa_desde?->toDateString(),
                ];
            })
            ->filter()
            ->values();

        return Inertia::render('panel/morosidad/index', [
            'viviendas' => $filas,
            'busqueda' => $busqueda,
            'puedeGestionar' => $request->user()->can('create', Pago::class),
        ]);
    }
}
