<?php

namespace App\Http\Controllers\Panel\Pagos;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Recibo imprimible (regla 5.9): número, JASS, socio, vivienda/medidor,
 * detalle, concepto/periodo y espacio de firma física. Página standalone
 * (no usa PanelLayout) para que @media print no tenga que ocultar sidebar.
 */
class ReciboController extends Controller
{
    public function show(Pago $pago): Response
    {
        $this->authorize('view', $pago);

        $pago->load([
            'socio:id,nombres,apellidos,dni',
            'vivienda:id,direccion',
            'medidor:id,codigo',
            'usuario:id,name',
            'jass:id,nombre,comunidad,distrito',
        ]);

        return Inertia::render('panel/recibos/show', [
            'pago' => $pago->only([
                'id', 'numero_recibo', 'tipo', 'periodo', 'concepto', 'lectura_anterior',
                'lectura_actual', 'monto', 'fecha_pago', 'socio', 'vivienda', 'medidor', 'usuario', 'jass',
            ]),
        ]);
    }
}
