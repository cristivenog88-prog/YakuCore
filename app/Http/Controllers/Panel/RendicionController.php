<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rendiciones\GenerarRendicionRequest;
use App\Models\Rendicion;
use App\Services\Auditoria\RegistradorAuditoria;
use App\Services\Rendicion\GeneradorRendicion;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RendicionController extends Controller
{
    private const CHECKLIST_KEYS = [
        'libros_al_dia',
        'recibos_archivados',
        'saldo_bancario_conciliado',
        'inventario_actualizado',
        'informe_fiscal',
    ];

    public function __construct(
        private readonly RegistradorAuditoria $auditoria,
        private readonly GeneradorRendicion $generador,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Rendicion::class);

        return Inertia::render('panel/rendiciones/index', [
            'rendiciones' => Rendicion::query()
                ->orderByDesc('anio')->orderByDesc('trimestre')
                ->get()
                ->map(fn (Rendicion $r) => [
                    'uuid' => $r->uuid,
                    'trimestre' => $r->trimestre,
                    'anio' => $r->anio,
                    'saldoFinal' => (float) $r->saldo_final,
                    'estado' => $r->estado,
                    'numeroActa' => $r->numero_acta,
                ]),
            'puedeGenerar' => $request->user()->can('create', Rendicion::class),
        ]);
    }

    public function show(Request $request, Rendicion $rendicion): Response
    {
        $this->authorize('view', $rendicion);

        return Inertia::render('panel/rendiciones/show', [
            'rendicion' => [
                'uuid' => $rendicion->uuid,
                'trimestre' => $rendicion->trimestre,
                'anio' => $rendicion->anio,
                'fechaAsamblea' => $rendicion->fecha_asamblea?->toDateString(),
                'saldoInicial' => (float) $rendicion->saldo_inicial,
                'ingresosPagos' => (float) $rendicion->ingresos_pagos,
                'otrosIngresos' => (float) $rendicion->otros_ingresos,
                'ejecucionPorCategoria' => $rendicion->ejecucion_por_categoria ?? [],
                'egresosTotal' => (float) $rendicion->egresos_total,
                'saldoFinal' => (float) $rendicion->saldo_final,
                'checklist' => $rendicion->checklist ?? [],
                'estado' => $rendicion->estado,
                'numeroActa' => $rendicion->numero_acta,
            ],
            'checklistKeys' => self::CHECKLIST_KEYS,
            'puedeEditar' => $request->user()->can('update', $rendicion),
            'puedeAprobar' => $request->user()->can('aprobar', $rendicion),
        ]);
    }

    public function store(GenerarRendicionRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $jass = app(ContextoJass::class)->jass();

        $r = $this->generador->handle($jass, (int) $datos['anio'], (int) $datos['trimestre']);

        $this->auditoria->registrar('generar', $r, [
            'anio' => $r->anio, 'trimestre' => $r->trimestre, 'saldo_final' => (float) $r->saldo_final,
        ]);

        return redirect()->route('panel.rendiciones.show', $r->uuid);
    }

    public function updateBorrador(Request $request, Rendicion $rendicion): RedirectResponse
    {
        $this->authorize('update', $rendicion);

        $rules = ['fecha_asamblea' => ['nullable', 'date'], 'otros_ingresos' => ['nullable', 'numeric', 'min:0']];
        foreach (self::CHECKLIST_KEYS as $k) {
            $rules["checklist.$k"] = ['sometimes', 'boolean'];
        }
        $datos = $request->validate($rules);

        $rendicion->update([
            'checklist' => $datos['checklist'] ?? $rendicion->checklist,
            'fecha_asamblea' => $datos['fecha_asamblea'] ?? $rendicion->fecha_asamblea,
            'otros_ingresos' => $datos['otros_ingresos'] ?? $rendicion->otros_ingresos,
        ]);

        // Recalcular saldo final si cambió otros_ingresos
        if (isset($datos['otros_ingresos'])) {
            $rendicion->update([
                'saldo_final' => round(
                    (float) $rendicion->saldo_inicial + (float) $rendicion->ingresos_pagos
                    + (float) $rendicion->otros_ingresos - (float) $rendicion->egresos_total,
                    2
                ),
            ]);
        }

        return back();
    }

    public function aprobar(Request $request, Rendicion $rendicion): RedirectResponse
    {
        $this->authorize('aprobar', $rendicion);

        $checklist = $rendicion->checklist ?? [];
        foreach (self::CHECKLIST_KEYS as $k) {
            if (empty($checklist[$k])) {
                return back()->withErrors(['checklist' => 'Debes marcar todos los ítems del control documental antes de aprobar.']);
            }
        }
        if (! $rendicion->fecha_asamblea) {
            return back()->withErrors(['fecha_asamblea' => 'Registra la fecha de la Asamblea antes de aprobar.']);
        }

        $datos = $request->validate(['numero_acta' => ['required', 'string', 'max:100']]);

        $rendicion->update([
            'estado' => 'aprobada',
            'aprobada_at' => now(),
            'numero_acta' => $datos['numero_acta'],
            'aprobada_por_user_id' => $request->user()->id,
        ]);

        $this->auditoria->registrar('aprobar', $rendicion, ['numero_acta' => $datos['numero_acta']]);

        return redirect()->route('panel.rendiciones.show', $rendicion->uuid);
    }
}
