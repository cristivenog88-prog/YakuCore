<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reuniones\StoreReunionRequest;
use App\Http\Requests\Reuniones\UpdateReunionRequest;
use App\Models\Reunion;
use App\Services\Auditoria\RegistradorAuditoria;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReunionController extends Controller
{
    public function __construct(private readonly RegistradorAuditoria $auditoria) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Reunion::class);

        $reuniones = Reunion::orderByDesc('fecha')
            ->get(['id', 'uuid', 'titulo', 'fecha', 'hora', 'lugar', 'estado'])
            ->map(fn (Reunion $reunion) => [
                'id' => $reunion->id,
                'uuid' => $reunion->uuid,
                'titulo' => $reunion->titulo,
                'fecha' => $reunion->fecha->toDateString(),
                'hora' => substr((string) $reunion->hora, 0, 5),
                'lugar' => $reunion->lugar,
                'estado' => $reunion->estado->value,
            ]);

        return Inertia::render('panel/reuniones/index', [
            'reuniones' => $reuniones,
            'puedeGestionar' => request()->user()->can('create', Reunion::class),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Reunion::class);

        return Inertia::render('panel/reuniones/create');
    }

    public function store(StoreReunionRequest $request): RedirectResponse
    {
        $this->authorize('create', Reunion::class);

        $reunion = Reunion::create($request->validated());

        $this->auditoria->registrar('crear', $reunion, $request->validated());

        return to_route('panel.reuniones.show', $reunion);
    }

    public function show(Reunion $reunion): Response
    {
        $this->authorize('view', $reunion);

        return Inertia::render('panel/reuniones/show', [
            'reunion' => [
                'id' => $reunion->id,
                'uuid' => $reunion->uuid,
                'titulo' => $reunion->titulo,
                'descripcion' => $reunion->descripcion,
                'fecha' => $reunion->fecha->toDateString(),
                'hora' => substr((string) $reunion->hora, 0, 5),
                'lugar' => $reunion->lugar,
                'puntosTratar' => $reunion->puntos_tratar,
                'estado' => $reunion->estado->value,
            ],
            'puedeGestionar' => request()->user()->can('update', $reunion),
            'puedeTomarAsistencia' => request()->user()->can('tomarAsistencia', $reunion),
            'asistencias' => $reunion->estado->value === 'finalizada'
                ? $reunion->asistencias()->with('socio:id,nombres,apellidos')->get(['id', 'socio_id', 'estado'])
                : [],
        ]);
    }

    public function edit(Reunion $reunion): Response
    {
        $this->authorize('update', $reunion);

        return Inertia::render('panel/reuniones/edit', [
            'reunion' => [
                'id' => $reunion->id,
                'uuid' => $reunion->uuid,
                'titulo' => $reunion->titulo,
                'descripcion' => $reunion->descripcion,
                'fecha' => $reunion->fecha->toDateString(),
                'hora' => substr((string) $reunion->hora, 0, 5),
                'lugar' => $reunion->lugar,
                'puntos_tratar' => $reunion->puntos_tratar,
            ],
        ]);
    }

    public function update(UpdateReunionRequest $request, Reunion $reunion): RedirectResponse
    {
        $this->authorize('update', $reunion);

        $reunion->update($request->validated());

        $this->auditoria->registrar('actualizar', $reunion, $request->validated());

        return to_route('panel.reuniones.show', $reunion);
    }

    public function cancelar(Reunion $reunion): RedirectResponse
    {
        $this->authorize('cancelar', $reunion);

        $reunion->update(['estado' => 'cancelada']);

        $this->auditoria->registrar('cancelar', $reunion);

        return to_route('panel.reuniones.show', $reunion);
    }
}
