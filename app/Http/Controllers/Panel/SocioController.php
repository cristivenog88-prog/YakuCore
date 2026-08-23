<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Socios\StoreSocioRequest;
use App\Http\Requests\Socios\UpdateSocioRequest;
use App\Models\Socio;
use App\Services\Auditoria\RegistradorAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SocioController extends Controller
{
    public function __construct(private readonly RegistradorAuditoria $auditoria) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Socio::class);

        $busqueda = trim((string) $request->query('buscar', ''));

        $socios = Socio::query()
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($sub) use ($busqueda) {
                    $sub->where('dni', 'like', "%{$busqueda}%")
                        ->orWhere('apellidos', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('apellidos')
            ->get(['id', 'nombres', 'apellidos', 'dni', 'telefono']);

        return Inertia::render('panel/socios/index', [
            'socios' => $socios,
            'busqueda' => $busqueda,
            'puedeGestionar' => $request->user()->can('create', Socio::class),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Socio::class);

        return Inertia::render('panel/socios/create');
    }

    public function store(StoreSocioRequest $request): RedirectResponse
    {
        $this->authorize('create', Socio::class);

        $socio = Socio::create($request->validated());

        $this->auditoria->registrar('crear', $socio, $request->validated());

        return to_route('panel.socios.index');
    }

    public function edit(Socio $socio): Response
    {
        $this->authorize('update', $socio);

        return Inertia::render('panel/socios/edit', [
            'socio' => $socio->only(['id', 'nombres', 'apellidos', 'dni', 'telefono', 'direccion']),
        ]);
    }

    public function update(UpdateSocioRequest $request, Socio $socio): RedirectResponse
    {
        $this->authorize('update', $socio);

        $socio->update($request->validated());

        $this->auditoria->registrar('actualizar', $socio, $request->validated());

        return to_route('panel.socios.index');
    }

    public function destroy(Socio $socio): RedirectResponse
    {
        $this->authorize('delete', $socio);

        $socio->delete();

        $this->auditoria->registrar('eliminar', $socio);

        return to_route('panel.socios.index');
    }
}
