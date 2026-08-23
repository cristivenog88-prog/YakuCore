<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Publico\ConsultaDeudaRequest;
use App\Models\Jass;
use App\Services\Publico\ConsultaDeuda;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConsultaDeudaController extends Controller
{
    public function __construct(private readonly ConsultaDeuda $consultaDeuda) {}

    public function create(Jass $jass): Response
    {
        return Inertia::render('publico/jass/deuda', [
            'jass' => $jass->only(['id', 'nombre', 'tiene_medidor']),
            'resultado' => session('resultado'),
        ]);
    }

    public function store(ConsultaDeudaRequest $request, Jass $jass): RedirectResponse
    {
        $datos = $request->validated();
        $clave = $this->consultaDeuda->clave($request->ip(), $jass->id, $datos['dni']);

        $this->consultaDeuda->asegurarNoBloqueado($clave);

        $resultado = $this->consultaDeuda->consultar($jass, $clave, $datos['dni'], $datos['correlativo_medidor'] ?? null);

        return back()->with('resultado', $resultado);
    }
}
