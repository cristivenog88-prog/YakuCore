<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reuniones\TomarAsistenciaRequest;
use App\Models\Reunion;
use App\Models\Socio;
use App\Services\Reuniones\RegistradorAsistencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AsistenciaController extends Controller
{
    public function __construct(private readonly RegistradorAsistencia $registrador) {}

    public function create(Reunion $reunion): Response
    {
        $this->authorize('tomarAsistencia', $reunion);

        return Inertia::render('panel/reuniones/tomar-asistencia', [
            'reunion' => [
                'id' => $reunion->id,
                'uuid' => $reunion->uuid,
                'titulo' => $reunion->titulo,
                'fecha' => $reunion->fecha->toDateString(),
            ],
            'socios' => Socio::orderBy('apellidos')->get(['id', 'nombres', 'apellidos', 'dni']),
        ]);
    }

    public function store(TomarAsistenciaRequest $request, Reunion $reunion): RedirectResponse
    {
        $this->authorize('tomarAsistencia', $reunion);

        $datos = $request->validated();

        $sociosActivos = Socio::pluck('id')->sort()->values()->all();
        $sociosEnviados = collect($datos['asistencias'])->pluck('socio_id')->sort()->values()->all();

        if ($sociosActivos !== $sociosEnviados) {
            throw ValidationException::withMessages([
                'asistencias' => 'Debes marcar la asistencia de todos los socios de la JASS.',
            ]);
        }

        $asistenciasPorSocio = collect($datos['asistencias'])->pluck('estado', 'socio_id')->all();

        $this->registrador->registrar($reunion, $asistenciasPorSocio);

        return to_route('panel.reuniones.show', $reunion);
    }
}
