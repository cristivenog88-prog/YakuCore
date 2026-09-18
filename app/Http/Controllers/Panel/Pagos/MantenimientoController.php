<?php

namespace App\Http\Controllers\Panel\Pagos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pagos\StoreMantenimientoRequest;
use App\Models\Pago;
use App\Models\Poa;
use App\Models\Socio;
use App\Models\Vivienda;
use App\Services\Pagos\RegistradorPago;
use App\Services\Poa\CalculadoraCuota;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MantenimientoController extends Controller
{
    public function __construct(
        private readonly RegistradorPago $registradorPago,
        private readonly CalculadoraCuota $calculadora,
    ) {}

    public function create(): Response
    {
        $this->authorize('create', Pago::class);

        $jass = app(ContextoJass::class)->jass();
        $usaPoa = (bool) $jass?->usa_poa;

        return Inertia::render('panel/pagos/mantenimiento/create', [
            'socios' => Socio::orderBy('apellidos')->get(['id', 'nombres', 'apellidos', 'dni']),
            'viviendas' => Vivienda::where('activa', true)->get(['id', 'socio_id', 'direccion']),
            'usaPoa' => $usaPoa,
            'cuotaMensual' => $usaPoa ? $this->cuotaVigente() : null,
        ]);
    }

    public function store(StoreMantenimientoRequest $request): RedirectResponse
    {
        $this->authorize('create', Pago::class);

        $datos = $request->validated();
        $vivienda = Vivienda::findOrFail($datos['vivienda_id']);
        $usaPoa = (bool) app(ContextoJass::class)->jass()?->usa_poa;

        $monto = $usaPoa ? $this->cuotaVigenteOFalla() : (float) $datos['monto'];

        $pago = $this->registradorPago->mantenimiento(
            $request->socio(),
            $vivienda,
            $datos['periodo'],
            $monto,
            $datos['fecha_pago'],
        );

        return to_route('panel.recibos.show', $pago);
    }

    private function cuotaVigente(): ?float
    {
        $poa = Poa::where('anio', now()->year)->first();

        return $poa === null ? null : $this->calculadora->cuotaMensual($poa, Socio::count());
    }

    /**
     * Igual que cuotaVigente(), pero exige que exista un POA con cuota
     * calculable — nunca se cobra un monto en cero o indefinido (CLAUDE.md #2).
     */
    private function cuotaVigenteOFalla(): float
    {
        $cuota = $this->cuotaVigente();

        if ($cuota === null) {
            throw ValidationException::withMessages([
                'monto' => 'Esta JASS usa POA pero todavía no tiene uno elaborado para el año en curso, o no hay socios registrados. Completa el POA antes de cobrar mantenimiento.',
            ]);
        }

        return $cuota;
    }
}
