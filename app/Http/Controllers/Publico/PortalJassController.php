<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Anuncio;
use App\Models\Gasto;
use App\Models\Jass;
use App\Models\Pago;
use App\Models\Reunion;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Página pública de una JASS (spec §6.13): anuncios visibles, reuniones
 * programadas y portal de transparencia (recaudado por tipo + gastos del
 * año, P9). Fija el contexto de tenancy manualmente porque estas rutas no
 * pasan por 'auth' + 'jass' (son públicas, sin sesión) — así los modelos
 * con PerteneceAJass quedan correctamente scopeados a esta JASS únicamente,
 * en vez de tener que liberar TenantScope en cada consulta.
 */
class PortalJassController extends Controller
{
    public function show(Request $request, Jass $jass): Response
    {
        app(ContextoJass::class)->fijar($jass);

        $anio = $request->integer('anio', now()->year);

        $anuncios = Anuncio::where('estado', 'visible')
            ->orderByDesc('fecha_publicacion')
            ->get(['id', 'titulo', 'contenido', 'fecha_publicacion']);

        $reuniones = Reunion::where('estado', 'programada')
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get(['id', 'titulo', 'fecha', 'hora', 'lugar']);

        $inicioAnio = Carbon::create($anio, 1, 1)->startOfDay();
        $finAnio = Carbon::create($anio, 12, 31)->endOfDay();

        $recaudadoMantenimiento = (float) Pago::where('tipo', 'mantenimiento')
            ->whereBetween('fecha_pago', [$inicioAnio, $finAnio])
            ->sum('monto');

        $recaudadoConsumo = (float) Pago::whereIn('tipo', ['consumo_medidor', 'consumo_directo'])
            ->whereBetween('fecha_pago', [$inicioAnio, $finAnio])
            ->sum('monto');

        $gastos = Gasto::whereBetween('created_at', [$inicioAnio, $finAnio])
            ->orderByDesc('created_at')
            ->get(['id', 'descripcion', 'monto', 'tipo_comprobante', 'created_at'])
            ->map(fn (Gasto $gasto) => [
                'descripcion' => $gasto->descripcion,
                'monto' => (float) $gasto->monto,
                'tipoComprobante' => $gasto->tipo_comprobante->value,
                'fecha' => $gasto->created_at->toDateString(),
            ]);

        return Inertia::render('publico/jass/show', [
            'jass' => $jass->only(['id', 'nombre', 'comunidad', 'distrito', 'tiene_medidor']),
            'anuncios' => $anuncios,
            'reuniones' => $reuniones,
            'anio' => $anio,
            'transparencia' => [
                'recaudadoMantenimiento' => $recaudadoMantenimiento,
                'recaudadoConsumo' => $recaudadoConsumo,
                'gastos' => $gastos,
                'totalGastos' => (float) $gastos->sum('monto'),
            ],
        ]);
    }
}
