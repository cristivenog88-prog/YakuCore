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
            'jass:id,nombre,comunidad,distrito,prefijo',
        ]);

        return Inertia::render('panel/recibos/show', [
            'pago' => [
                'id' => $pago->id,
                'codigoRecibo' => Pago::formatearCodigo($pago->jass->prefijo, $pago->numero_recibo),
                'tipo' => $pago->tipo->value,
                'periodo' => $pago->periodo,
                'concepto' => $pago->concepto,
                'lectura_anterior' => $pago->lectura_anterior,
                'lectura_actual' => $pago->lectura_actual,
                'monto' => $pago->monto,
                'fecha_pago' => $pago->fecha_pago->toDateString(),
                'socio' => $pago->socio,
                'vivienda' => $pago->vivienda,
                'medidor' => $pago->medidor,
                'usuario' => $pago->usuario,
                'jass' => $pago->jass->only(['id', 'nombre', 'comunidad', 'distrito']),
            ],
        ]);
    }
}
