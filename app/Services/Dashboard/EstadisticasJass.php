<?php

namespace App\Services\Dashboard;

use App\Enums\EstadoReunion;
use App\Enums\TipoPago;
use App\Models\Gasto;
use App\Models\Jass;
use App\Models\Pago;
use App\Models\Reunion;
use App\Models\Socio;
use App\Models\Vivienda;
use Illuminate\Support\Carbon;

/**
 * KPIs del dashboard de una JASS (spec §6.3). Cada número se obtiene con una
 * consulta agregada (sum/count) — nunca cargando colecciones completas para
 * sumarlas en PHP, salvo la serie mensual del gráfico, donde agrupar 12
 * meses de pagos en memoria es más simple y portable que una función de
 * fecha específica de MySQL.
 */
class EstadisticasJass
{
    public function calcular(Jass $jass): array
    {
        $hoy = Carbon::today();
        $inicioMes = $hoy->copy()->startOfMonth();
        $inicioAnio = $hoy->copy()->startOfYear();

        $sociosActivos = Socio::count();
        $viviendasActivas = Vivienda::where('activa', true)->count();

        $tiposConsumo = [TipoPago::ConsumoMedidor, TipoPago::ConsumoDirecto];

        $recaudadoMes = [
            'mantenimiento' => (float) Pago::where('tipo', TipoPago::Mantenimiento)->where('fecha_pago', '>=', $inicioMes)->sum('monto'),
            'consumo' => (float) Pago::whereIn('tipo', $tiposConsumo)->where('fecha_pago', '>=', $inicioMes)->sum('monto'),
        ];

        $recaudadoAnio = [
            'mantenimiento' => (float) Pago::where('tipo', TipoPago::Mantenimiento)->where('fecha_pago', '>=', $inicioAnio)->sum('monto'),
            'consumo' => (float) Pago::whereIn('tipo', $tiposConsumo)->where('fecha_pago', '>=', $inicioAnio)->sum('monto'),
        ];

        $gastosMes = (float) Gasto::where('created_at', '>=', $inicioMes)->sum('monto');
        $gastosAnio = (float) Gasto::where('created_at', '>=', $inicioAnio)->sum('monto');

        $viviendasMorosas = Vivienda::where('activa', true)->whereNotNull('morosa_desde')->count();

        $pagosDelAnio = Pago::where('fecha_pago', '>=', $inicioAnio)->get(['fecha_pago', 'monto']);
        $totalesPorMes = $pagosDelAnio->groupBy(fn (Pago $pago) => $pago->fecha_pago->month)->map(fn ($grupo) => (float) $grupo->sum('monto'));

        $proximaReunion = Reunion::where('estado', EstadoReunion::Programada)
            ->orderBy('fecha')->orderBy('hora')
            ->first(['id', 'uuid', 'titulo', 'fecha', 'hora']);

        return [
            'sociosActivos' => $sociosActivos,
            'viviendasActivas' => $viviendasActivas,
            'recaudadoMes' => $recaudadoMes,
            'recaudadoAnio' => $recaudadoAnio,
            'gastosMes' => $gastosMes,
            'gastosAnio' => $gastosAnio,
            'balanceAnio' => $recaudadoAnio['mantenimiento'] + $recaudadoAnio['consumo'] - $gastosAnio,
            'viviendasMorosas' => [
                'cantidad' => $viviendasMorosas,
                'porcentaje' => $viviendasActivas > 0 ? round($viviendasMorosas / $viviendasActivas * 100, 1) : 0.0,
            ],
            'proximaReunion' => $proximaReunion === null ? null : [
                'id' => $proximaReunion->id,
                'uuid' => $proximaReunion->uuid,
                'titulo' => $proximaReunion->titulo,
                'fecha' => $proximaReunion->fecha->toDateString(),
                'hora' => substr((string) $proximaReunion->hora, 0, 5),
            ],
            'ultimosPagos' => Pago::orderByDesc('fecha_pago')->orderByDesc('id')
                ->with('socio:id,nombres,apellidos')
                ->take(10)
                ->get(['id', 'socio_id', 'tipo', 'numero_recibo', 'concepto', 'monto', 'fecha_pago'])
                ->map(fn (Pago $pago) => [
                    'id' => $pago->id,
                    'tipo' => $pago->tipo->value,
                    'codigoRecibo' => Pago::formatearCodigo($jass->prefijo, $pago->numero_recibo),
                    'concepto' => $pago->concepto,
                    'monto' => $pago->monto,
                    'fecha_pago' => $pago->fecha_pago->toDateString(),
                    'socio' => $pago->socio,
                ]),
            'recaudacionMensual' => collect(range(1, 12))->map(fn (int $mes) => [
                'mes' => $mes,
                'total' => $totalesPorMes[$mes] ?? 0.0,
            ])->values(),
        ];
    }
}
