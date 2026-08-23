<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Viviendas\StoreViviendaRequest;
use App\Http\Requests\Viviendas\UpdateViviendaRequest;
use App\Models\Socio;
use App\Models\Vivienda;
use App\Services\Auditoria\RegistradorAuditoria;
use Illuminate\Http\RedirectResponse;

/**
 * Gestión de viviendas dentro de la ficha del socio (regla 5.1: un socio
 * puede tener N viviendas). Sin index/show propios — se listan en
 * panel/socios/show.tsx.
 */
class ViviendaController extends Controller
{
    public function __construct(private readonly RegistradorAuditoria $auditoria) {}

    public function store(StoreViviendaRequest $request, Socio $socio): RedirectResponse
    {
        $this->authorize('create', Vivienda::class);

        $vivienda = $socio->viviendas()->create($request->validated());

        $this->auditoria->registrar('crear', $vivienda, $request->validated());

        return to_route('panel.socios.show', $socio);
    }

    public function update(UpdateViviendaRequest $request, Socio $socio, Vivienda $vivienda): RedirectResponse
    {
        $this->authorize('update', $vivienda);

        $vivienda->update($request->validated());

        $this->auditoria->registrar('actualizar', $vivienda, $request->validated());

        return to_route('panel.socios.show', $socio);
    }

    public function destroy(Socio $socio, Vivienda $vivienda): RedirectResponse
    {
        $this->authorize('delete', $vivienda);

        $vivienda->delete();

        $this->auditoria->registrar('eliminar', $vivienda);

        return to_route('panel.socios.show', $socio);
    }
}
