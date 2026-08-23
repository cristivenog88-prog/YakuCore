<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Medidores\ReemplazarMedidorRequest;
use App\Http\Requests\Medidores\StoreMedidorRequest;
use App\Http\Requests\Medidores\UpdateMedidorRequest;
use App\Models\Medidor;
use App\Models\Vivienda;
use App\Services\Auditoria\RegistradorAuditoria;
use App\Services\Medidores\ReemplazoMedidor;
use Illuminate\Http\RedirectResponse;

/**
 * 0..1 medidor por vivienda (regla 5.2): sin index/show propios, se
 * gestiona inline en la ficha del socio. Solo alcanzable si
 * jass.tiene_medidor (AsegurarJassTieneMedidor, 404 en caso contrario).
 */
class MedidorController extends Controller
{
    public function __construct(private readonly RegistradorAuditoria $auditoria) {}

    public function store(StoreMedidorRequest $request, Vivienda $vivienda): RedirectResponse
    {
        $this->authorize('create', Medidor::class);

        abort_if($vivienda->medidor()->exists(), 422, 'Esta vivienda ya tiene un medidor registrado.');

        $medidor = $vivienda->medidor()->create($request->validated());

        $this->auditoria->registrar('crear', $medidor, $request->validated());

        return to_route('panel.socios.show', $vivienda->socio_id);
    }

    public function update(UpdateMedidorRequest $request, Vivienda $vivienda, Medidor $medidor): RedirectResponse
    {
        $this->authorize('update', $medidor);

        $medidor->update($request->validated());

        $this->auditoria->registrar('actualizar', $medidor, $request->validated());

        return to_route('panel.socios.show', $vivienda->socio_id);
    }

    /**
     * Flujo de reemplazo de medidor (8.3): requiere confirmar contraseña
     * (password.confirm en la ruta) y deja traza de las lecturas previas.
     */
    public function reemplazar(
        ReemplazarMedidorRequest $request,
        Vivienda $vivienda,
        Medidor $medidor,
        ReemplazoMedidor $servicio
    ): RedirectResponse {
        $this->authorize('update', $medidor);

        $servicio->ejecutar($medidor, $request->validated('motivo'));

        return to_route('panel.socios.show', $vivienda->socio_id);
    }
}
