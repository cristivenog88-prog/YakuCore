<?php

namespace App\Http\Controllers\Panel\Pagos;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use Inertia\Inertia;
use Inertia\Response;

class ReciboController extends Controller
{
    public function show(Pago $pago): Response
    {
        $this->authorize('view', $pago);

        return Inertia::render('panel/recibos/show', [
            'pago' => $pago->load(['socio', 'vivienda', 'medidor', 'usuario', 'jass'])->only([
                'id', 'numero_recibo', 'tipo', 'periodo', 'concepto', 'lectura_anterior',
                'lectura_actual', 'monto', 'fecha_pago', 'socio', 'vivienda', 'medidor', 'usuario', 'jass',
            ]),
        ]);
    }
}
