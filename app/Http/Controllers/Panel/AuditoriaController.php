<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vista Auditoría (spec §6.12, decisión 8.4): solo lectura absoluta — sin
 * rutas de update/delete/create, AuditoriaPolicy las niega todas. El vocal
 * no tiene acceso (8.4); AuditoriaPolicy::viewAny ya lo excluye.
 */
class AuditoriaController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Auditoria::class);

        $query = Auditoria::with('usuario:id,name')->orderByDesc('created_at');

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->integer('usuario_id'));
        }

        if ($request->filled('accion')) {
            $query->where('accion', $request->string('accion'));
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->date('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->date('hasta'));
        }

        $registros = $query->limit(200)
            ->get(['id', 'usuario_id', 'accion', 'entidad_tipo', 'entidad_id', 'created_at'])
            ->map(fn (Auditoria $registro) => [
                'id' => $registro->id,
                'usuario' => $registro->usuario?->name ?? 'Sistema',
                'accion' => $registro->accion,
                'entidad' => class_basename($registro->entidad_tipo),
                'entidadId' => $registro->entidad_id,
                'fecha' => $registro->created_at->format('Y-m-d H:i'),
            ]);

        $jass = app(ContextoJass::class)->jass();

        return Inertia::render('panel/auditoria/index', [
            'registros' => $registros,
            'usuarios' => User::where('jass_id', $jass?->id)->orderBy('name')->get(['id', 'name']),
            'acciones' => Auditoria::distinct()->orderBy('accion')->pluck('accion'),
            'filtros' => $request->only(['usuario_id', 'accion', 'desde', 'hasta']),
        ]);
    }
}
