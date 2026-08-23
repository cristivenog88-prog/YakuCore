<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gastos\StoreGastoRequest;
use App\Http\Requests\Gastos\UpdateGastoRequest;
use App\Models\Gasto;
use App\Services\Auditoria\RegistradorAuditoria;
use App\Services\Gastos\AlmacenadorComprobante;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GastoController extends Controller
{
    public function __construct(
        private readonly RegistradorAuditoria $auditoria,
        private readonly AlmacenadorComprobante $almacenador,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Gasto::class);

        $anio = $request->integer('anio', now()->year);

        $gastos = Gasto::whereYear('created_at', $anio)
            ->with('usuario:id,name')
            ->orderByDesc('created_at')
            ->get(['id', 'usuario_id', 'descripcion', 'monto', 'tipo_comprobante', 'foto_path', 'created_at']);

        $totalesPorMes = $gastos
            ->groupBy(fn (Gasto $gasto) => $gasto->created_at->format('Y-m'))
            ->map(fn ($grupo) => (float) $grupo->sum('monto'))
            ->sortKeys()
            ->all();

        return Inertia::render('panel/gastos/index', [
            'gastos' => $gastos->map(fn (Gasto $gasto) => [
                'id' => $gasto->id,
                'descripcion' => $gasto->descripcion,
                'monto' => (float) $gasto->monto,
                'tipoComprobante' => $gasto->tipo_comprobante->value,
                'tieneFoto' => $gasto->foto_path !== null,
                'usuario' => $gasto->usuario->name,
                'fecha' => $gasto->created_at->toDateString(),
            ]),
            'totalesPorMes' => $totalesPorMes,
            'totalAnio' => array_sum($totalesPorMes),
            'anio' => $anio,
            'puedeGestionar' => $request->user()->can('create', Gasto::class),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Gasto::class);

        return Inertia::render('panel/gastos/create');
    }

    public function store(StoreGastoRequest $request): RedirectResponse
    {
        $this->authorize('create', Gasto::class);

        $datos = $request->validated();
        $fotoPath = $request->hasFile('foto')
            ? $this->almacenador->guardar($request->file('foto'), app(ContextoJass::class)->id())
            : null;

        $gasto = Gasto::create([
            'usuario_id' => Auth::id(),
            'descripcion' => $datos['descripcion'],
            'monto' => $datos['monto'],
            'tipo_comprobante' => $datos['tipo_comprobante'],
            'foto_path' => $fotoPath,
        ]);

        $this->auditoria->registrar('crear', $gasto, ['monto' => $gasto->monto]);

        return to_route('panel.gastos.index');
    }

    public function edit(Gasto $gasto): Response
    {
        $this->authorize('update', $gasto);

        return Inertia::render('panel/gastos/edit', [
            'gasto' => [
                'id' => $gasto->id,
                'descripcion' => $gasto->descripcion,
                'monto' => (float) $gasto->monto,
                'tipoComprobante' => $gasto->tipo_comprobante->value,
                'tieneFoto' => $gasto->foto_path !== null,
            ],
        ]);
    }

    public function update(UpdateGastoRequest $request, Gasto $gasto): RedirectResponse
    {
        $this->authorize('update', $gasto);

        $datos = $request->validated();

        $gasto->descripcion = $datos['descripcion'];
        $gasto->monto = $datos['monto'];
        $gasto->tipo_comprobante = $datos['tipo_comprobante'];

        if ($request->hasFile('foto')) {
            $this->almacenador->eliminar($gasto->foto_path);
            $gasto->foto_path = $this->almacenador->guardar($request->file('foto'), $gasto->jass_id);
        }

        $gasto->save();

        $this->auditoria->registrar('actualizar', $gasto, ['monto' => $gasto->monto]);

        return to_route('panel.gastos.index');
    }

    public function destroy(Gasto $gasto): RedirectResponse
    {
        $this->authorize('delete', $gasto);

        $gasto->delete();

        $this->auditoria->registrar('eliminar', $gasto);

        return to_route('panel.gastos.index');
    }

    /**
     * Única forma de acceder al comprobante (spec §7.1): jamás una URL
     * pública, siempre autorizado y servido desde el disco privado.
     */
    public function comprobante(Gasto $gasto): StreamedResponse
    {
        $this->authorize('view', $gasto);

        abort_if($gasto->foto_path === null, 404);

        return Storage::disk('local')->response($gasto->foto_path);
    }
}
