<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jass\StoreJassRequest;
use App\Http\Requests\Jass\UpdateJassRequest;
use App\Models\Jass;
use App\Services\Auditoria\RegistradorAuditoria;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class JassController extends Controller
{
    public function __construct(private readonly RegistradorAuditoria $auditoria) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Jass::class);

        return Inertia::render('superadmin/jass/index', [
            'jass' => Jass::orderBy('nombre')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Jass::class);

        return Inertia::render('superadmin/jass/create');
    }

    public function store(StoreJassRequest $request): RedirectResponse
    {
        $this->authorize('create', Jass::class);

        $datos = $request->validated();

        if (! $datos['tiene_medidor']) {
            $datos['tarifa_base_m3'] = null;
        }

        $jass = Jass::create($datos);

        $this->auditoria->registrar('crear', $jass, $datos);

        return to_route('superadmin.jass.index');
    }

    public function edit(Jass $jass): Response
    {
        $this->authorize('update', $jass);

        return Inertia::render('superadmin/jass/edit', [
            'jass' => $jass,
        ]);
    }

    public function update(UpdateJassRequest $request, Jass $jass): RedirectResponse
    {
        $this->authorize('update', $jass);

        $datos = $request->validated();

        if (! $datos['tiene_medidor']) {
            $datos['tarifa_base_m3'] = null;
        }

        $jass->update($datos);

        $this->auditoria->registrar('actualizar', $jass, $datos);

        return to_route('superadmin.jass.index');
    }

    public function destroy(Jass $jass): RedirectResponse
    {
        $this->authorize('delete', $jass);

        $jass->delete();

        $this->auditoria->registrar('eliminar', $jass);

        return to_route('superadmin.jass.index');
    }
}
