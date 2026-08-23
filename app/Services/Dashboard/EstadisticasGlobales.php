<?php

namespace App\Services\Dashboard;

use App\Models\Jass;
use App\Models\Pago;
use App\Models\Scopes\TenantScope;
use App\Models\Socio;
use App\Models\Vivienda;
use Illuminate\Support\Carbon;

/**
 * KPIs del dashboard superadmin (spec §6.1): una fila por JASS más totales
 * globales. El superadmin no tiene una JASS activa en ContextoJass, así que
 * cada consulta se libera explícitamente de TenantScope y se agrupa por
 * jass_id en una sola pasada (sum/count agrupados, sección 4 de esta fase)
 * en vez de una consulta por JASS dentro de un bucle.
 */
class EstadisticasGlobales
{
    public function calcular(): array
    {
        $inicioAnio = Carbon::now()->startOfYear();

        $sociosPorJass = Socio::withoutGlobalScope(TenantScope::class)
            ->selectRaw('jass_id, COUNT(*) as total')
            ->groupBy('jass_id')
            ->pluck('total', 'jass_id');

        $viviendasActivasPorJass = Vivienda::withoutGlobalScope(TenantScope::class)
            ->where('activa', true)
            ->selectRaw('jass_id, COUNT(*) as total')
            ->groupBy('jass_id')
            ->pluck('total', 'jass_id');

        $viviendasMorosasPorJass = Vivienda::withoutGlobalScope(TenantScope::class)
            ->where('activa', true)
            ->whereNotNull('morosa_desde')
            ->selectRaw('jass_id, COUNT(*) as total')
            ->groupBy('jass_id')
            ->pluck('total', 'jass_id');

        $recaudacionPorJass = Pago::withoutGlobalScope(TenantScope::class)
            ->where('fecha_pago', '>=', $inicioAnio)
            ->selectRaw('jass_id, SUM(monto) as total')
            ->groupBy('jass_id')
            ->pluck('total', 'jass_id');

        $filas = Jass::orderBy('nombre')->get(['id', 'nombre'])
            ->map(function (Jass $jass) use ($sociosPorJass, $viviendasActivasPorJass, $viviendasMorosasPorJass, $recaudacionPorJass) {
                $viviendasActivas = (int) ($viviendasActivasPorJass[$jass->id] ?? 0);
                $viviendasMorosas = (int) ($viviendasMorosasPorJass[$jass->id] ?? 0);

                return [
                    'id' => $jass->id,
                    'nombre' => $jass->nombre,
                    'socios' => (int) ($sociosPorJass[$jass->id] ?? 0),
                    'recaudacionAnio' => (float) ($recaudacionPorJass[$jass->id] ?? 0),
                    'porcentajeMorosidad' => $viviendasActivas > 0 ? round($viviendasMorosas / $viviendasActivas * 100, 1) : 0.0,
                ];
            });

        return [
            'jass' => $filas,
            'totales' => [
                'jass' => $filas->count(),
                'socios' => $filas->sum('socios'),
                'recaudacionAnio' => $filas->sum('recaudacionAnio'),
            ],
        ];
    }
}
