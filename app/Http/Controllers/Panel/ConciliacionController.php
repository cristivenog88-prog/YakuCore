<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conciliaciones\PrepararConciliacionRequest;
use App\Models\Conciliacion;
use App\Services\Auditoria\RegistradorAuditoria;
use App\Services\Conciliacion\CalculadoraConciliacion;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConciliacionController extends Controller
{
    public function __construct(
        private readonly RegistradorAuditoria $auditoria,
        private readonly CalculadoraConciliacion $calculadora,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Conciliacion::class);

        return Inertia::render('panel/conciliaciones/index', [
            'conciliaciones' => Conciliacion::query()
                ->orderByDesc('periodo')
                ->get()
                ->map(fn (Conciliacion $c) => [
                    'uuid' => $c->uuid,
                    'periodo' => $c->periodo,
                    'pagosTotal' => (float) $c->pagos_total,
                    'gastosTotal' => (float) $c->gastos_total,
                    'saldoCalculado' => (float) $c->saldo_calculado,
                    'fondosVerificados' => (float) $c->fondos_verificados,
                    'diferencia' => (float) $c->diferencia,
                    'estado' => $c->estado,
                    'firmadoTesorero' => $c->firmado_tesorero_at !== null,
                    'firmadoFiscal' => $c->firmado_fiscal_at !== null,
                ]),
            'puedePreparar' => $request->user()->can('preparar', Conciliacion::class),
        ]);
    }

    public function show(Request $request, Conciliacion $conciliacion): Response
    {
        $this->authorize('view', $conciliacion);

        $conciliacion->load(['tesorero:id,name', 'fiscal:id,name']);

        return Inertia::render('panel/conciliaciones/show', [
            'conciliacion' => [
                'uuid' => $conciliacion->uuid,
                'periodo' => $conciliacion->periodo,
                'pagosTotal' => (float) $conciliacion->pagos_total,
                'gastosTotal' => (float) $conciliacion->gastos_total,
                'saldoCalculado' => (float) $conciliacion->saldo_calculado,
                'efectivoVerificado' => (float) $conciliacion->efectivo_verificado,
                'bancoVerificado' => (float) $conciliacion->banco_verificado,
                'fondosVerificados' => (float) $conciliacion->fondos_verificados,
                'diferencia' => (float) $conciliacion->diferencia,
                'estado' => $conciliacion->estado,
                'observaciones' => $conciliacion->observaciones,
                'tesorero' => $conciliacion->tesorero?->name,
                'fiscal' => $conciliacion->fiscal?->name,
                'firmadoTesoreroAt' => $conciliacion->firmado_tesorero_at?->toIso8601String(),
                'firmadoFiscalAt' => $conciliacion->firmado_fiscal_at?->toIso8601String(),
            ],
            'puedeFirmarTesorero' => $request->user()->can('firmarTesorero', $conciliacion),
            'puedeFirmarFiscal' => $request->user()->can('firmarFiscal', $conciliacion),
        ]);
    }

    public function store(PrepararConciliacionRequest $request): RedirectResponse
    {
        $this->authorize('preparar', Conciliacion::class);

        $datos = $request->validated();
        $jass = app(ContextoJass::class)->jass();

        $conciliacion = $this->calculadora->handle(
            $jass,
            $datos['periodo'],
            (float) $datos['efectivo_verificado'],
            (float) $datos['banco_verificado'],
        );

        if (! empty($datos['observaciones'])) {
            $conciliacion->update(['observaciones' => $datos['observaciones']]);
        }

        $this->auditoria->registrar('preparar', $conciliacion, [
            'periodo' => $conciliacion->periodo,
            'estado' => $conciliacion->estado,
            'diferencia' => (float) $conciliacion->diferencia,
        ]);

        return redirect()->route('panel.conciliaciones.show', $conciliacion);
    }

    public function firmarTesorero(Request $request, Conciliacion $conciliacion): RedirectResponse
    {
        $this->authorize('firmarTesorero', $conciliacion);

        $conciliacion->update([
            'tesorero_user_id' => $request->user()->id,
            'firmado_tesorero_at' => now(),
        ]);

        $this->auditoria->registrar('firmar', $conciliacion, ['firma' => 'tesorero']);

        return back();
    }

    public function firmarFiscal(Request $request, Conciliacion $conciliacion): RedirectResponse
    {
        $this->authorize('firmarFiscal', $conciliacion);

        if (! $conciliacion->firmado_tesorero_at) {
            return back()->withErrors(['firma' => 'El tesorero debe firmar primero.']);
        }

        $conciliacion->update([
            'fiscal_user_id' => $request->user()->id,
            'firmado_fiscal_at' => now(),
        ]);

        $this->auditoria->registrar('firmar', $conciliacion, ['firma' => 'fiscal']);

        return back();
    }
}
