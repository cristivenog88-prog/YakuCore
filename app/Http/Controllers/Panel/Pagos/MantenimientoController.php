<?php

namespace App\Http\Controllers\Panel\Pagos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pagos\StoreMantenimientoRequest;
use App\Models\Pago;
use App\Models\Socio;
use App\Models\Vivienda;
use App\Services\Pagos\RegistradorPago;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MantenimientoController extends Controller
{
    public function __construct(private readonly RegistradorPago $registradorPago) {}

    public function create(): Response
    {
        $this->authorize('create', Pago::class);

        return Inertia::render('panel/pagos/mantenimiento/create', [
            'socios' => Socio::orderBy('apellidos')->get(['id', 'nombres', 'apellidos', 'dni']),
            'viviendas' => Vivienda::where('activa', true)->get(['id', 'socio_id', 'direccion']),
        ]);
    }

    public function store(StoreMantenimientoRequest $request): RedirectResponse
    {
        $this->authorize('create', Pago::class);

        $datos = $request->validated();
        $vivienda = Vivienda::findOrFail($datos['vivienda_id']);

        $pago = $this->registradorPago->mantenimiento(
            $request->socio(),
            $vivienda,
            $datos['periodo'],
            (float) $datos['monto'],
            $datos['fecha_pago'],
        );

        return to_route('panel.recibos.show', $pago);
    }
}
