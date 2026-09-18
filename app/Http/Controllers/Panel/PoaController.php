<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poa\StorePoaRequest;
use App\Http\Requests\Poa\UpdatePoaRequest;
use App\Models\Poa;
use App\Models\Socio;
use App\Services\Auditoria\RegistradorAuditoria;
use App\Services\Poa\CalculadoraCuota;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Plan Operativo Anual (guía POA): un plan por JASS y año en curso — sin
 * selector de año en v1, siempre opera sobre now()->year. La cuota mensual
 * de mantenimiento que deriva (caso 1: única, sin diferenciar por
 * categoría) la usa MantenimientoController cuando jass.usa_poa es true.
 */
class PoaController extends Controller
{
    public function __construct(
        private readonly RegistradorAuditoria $auditoria,
        private readonly CalculadoraCuota $calculadora,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Poa::class);

        $jass = app(ContextoJass::class)->jass();
        $poa = $this->poaDelAnioActual();

        $resumen = null;

        if ($poa !== null) {
            $numeroSocios = Socio::count();

            $resumen = [
                'anio' => $poa->anio,
                'otrosIngresos' => (float) $poa->otros_ingresos,
                'presupuestoBase' => $this->calculadora->presupuestoBase($poa),
                'reserva' => $this->calculadora->reserva($poa),
                'presupuestoNeto' => $this->calculadora->presupuestoNeto($poa),
                'numeroSocios' => $numeroSocios,
                'cuotaMensual' => $this->calculadora->cuotaMensual($poa, $numeroSocios),
                'actividades' => $poa->actividades()->orderBy('id')->get()->map(fn ($actividad) => [
                    'id' => $actividad->id,
                    'categoria' => $actividad->categoria->value,
                    'descripcion' => $actividad->descripcion,
                    'frecuenciaAnual' => (float) $actividad->frecuencia_anual,
                    'costoUnitario' => (float) $actividad->costo_unitario,
                    'mes' => $actividad->mes,
                    'responsable' => $actividad->responsable,
                    'subtotal' => $actividad->subtotal(),
                ]),
            ];
        }

        return Inertia::render('panel/poa/index', [
            'anioActual' => now()->year,
            'usaPoa' => (bool) $jass?->usa_poa,
            'poa' => $resumen,
            'puedeGestionar' => $request->user()->can('create', Poa::class),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $this->authorize('create', Poa::class);

        if ($this->poaDelAnioActual() !== null) {
            return to_route('panel.poa.edit');
        }

        return Inertia::render('panel/poa/create', ['anioActual' => now()->year]);
    }

    public function store(StorePoaRequest $request): RedirectResponse
    {
        $this->authorize('create', Poa::class);

        abort_if($this->poaDelAnioActual() !== null, 422, 'Ya existe un POA para el año en curso. Edítalo en vez de crear uno nuevo.');

        $datos = $request->validated();

        $poa = DB::transaction(function () use ($datos) {
            $poa = Poa::create([
                'anio' => now()->year,
                'otros_ingresos' => $datos['otros_ingresos'] ?? 0,
            ]);

            foreach ($datos['actividades'] as $actividad) {
                $poa->actividades()->create($actividad);
            }

            return $poa;
        });

        $this->auditoria->registrar('crear', $poa, ['anio' => $poa->anio, 'actividades' => count($datos['actividades'])]);

        return to_route('panel.poa.index');
    }

    public function edit(): Response
    {
        $poa = $this->poaDelAnioActual();

        abort_if($poa === null, 404);

        $this->authorize('update', $poa);

        return Inertia::render('panel/poa/edit', [
            'anioActual' => $poa->anio,
            'otrosIngresos' => (float) $poa->otros_ingresos,
            'actividades' => $poa->actividades()->orderBy('id')->get()->map(fn ($actividad) => [
                'categoria' => $actividad->categoria->value,
                'descripcion' => $actividad->descripcion,
                'frecuencia_anual' => $actividad->frecuencia_anual,
                'costo_unitario' => (float) $actividad->costo_unitario,
                'mes' => $actividad->mes,
                'responsable' => $actividad->responsable,
            ]),
        ]);
    }

    public function update(UpdatePoaRequest $request): RedirectResponse
    {
        $poa = $this->poaDelAnioActual();

        abort_if($poa === null, 404);

        $this->authorize('update', $poa);

        $datos = $request->validated();

        DB::transaction(function () use ($poa, $datos) {
            $poa->update(['otros_ingresos' => $datos['otros_ingresos'] ?? 0]);

            $poa->actividades()->delete();

            foreach ($datos['actividades'] as $actividad) {
                $poa->actividades()->create($actividad);
            }
        });

        $this->auditoria->registrar('actualizar', $poa, ['anio' => $poa->anio, 'actividades' => count($datos['actividades'])]);

        return to_route('panel.poa.index');
    }

    private function poaDelAnioActual(): ?Poa
    {
        return Poa::where('anio', now()->year)->first();
    }
}
