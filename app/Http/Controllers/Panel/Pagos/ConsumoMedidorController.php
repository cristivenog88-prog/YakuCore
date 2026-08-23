<?php

namespace App\Http\Controllers\Panel\Pagos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pagos\StoreConsumoMedidorRequest;
use App\Models\Medidor;
use App\Models\Pago;
use App\Models\Socio;
use App\Services\Pagos\CalculadoraConsumo;
use App\Services\Pagos\RegistradorPago;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConsumoMedidorController extends Controller
{
    public function __construct(
        private readonly RegistradorPago $registradorPago,
        private readonly CalculadoraConsumo $calculadoraConsumo,
    ) {}

    public function create(Request $request): Response
    {
        $this->authorize('create', Pago::class);

        return Inertia::render('panel/pagos/consumo-medidor/create', [
            'socios' => Socio::orderBy('apellidos')->get(['id', 'nombres', 'apellidos', 'dni']),
            'medidores' => Medidor::where('activo', true)
                ->with('vivienda:id,socio_id,direccion')
                ->get(['id', 'vivienda_id', 'codigo']),
            'previsualizacion' => $this->previsualizar($request),
        ]);
    }

    public function store(StoreConsumoMedidorRequest $request): RedirectResponse
    {
        $this->authorize('create', Pago::class);

        $datos = $request->validated();
        $medidor = Medidor::findOrFail($datos['medidor_id']);

        $pago = $this->registradorPago->consumoMedidor(
            $request->socio(),
            $medidor,
            $datos['periodo'],
            (float) $datos['lectura_actual'],
            $datos['fecha_pago'],
        );

        return to_route('panel.recibos.show', $pago);
    }

    /**
     * Calcula la previsualización de lectura anterior + monto en servidor
     * (nunca en JS): el frontend la pide vía partial reload de Inertia
     * cada vez que cambian medidor_id o lectura_actual.
     *
     * @return array{lectura_anterior: float, monto: float|null, valido: bool}|null
     */
    private function previsualizar(Request $request): ?array
    {
        if (! $request->filled('medidor_id') || ! $request->filled('lectura_actual')) {
            return null;
        }

        $medidor = Medidor::find($request->integer('medidor_id'));

        if ($medidor === null) {
            return null;
        }

        $jass = app(ContextoJass::class)->jass();
        $lecturaAnterior = $this->calculadoraConsumo->lecturaAnterior($medidor);
        $lecturaActual = (float) $request->input('lectura_actual');
        $valido = $lecturaActual >= $lecturaAnterior;

        return [
            'lectura_anterior' => $lecturaAnterior,
            'monto' => $valido ? $this->calculadoraConsumo->calcularMonto($lecturaAnterior, $lecturaActual, (float) $jass->tarifa_base_m3) : null,
            'valido' => $valido,
        ];
    }
}
