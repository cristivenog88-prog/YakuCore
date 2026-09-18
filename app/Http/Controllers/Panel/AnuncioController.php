<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Anuncios\StoreAnuncioRequest;
use App\Http\Requests\Anuncios\UpdateAnuncioRequest;
use App\Models\Anuncio;
use App\Services\Auditoria\RegistradorAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnuncioController extends Controller
{
    public function __construct(private readonly RegistradorAuditoria $auditoria) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Anuncio::class);

        $anuncios = Anuncio::orderByDesc('fecha_publicacion')
            ->get(['id', 'uuid', 'titulo', 'contenido', 'fecha_publicacion', 'estado'])
            ->map(fn (Anuncio $anuncio) => [
                'id' => $anuncio->id,
                'uuid' => $anuncio->uuid,
                'titulo' => $anuncio->titulo,
                'contenido' => $anuncio->contenido,
                'fecha_publicacion' => $anuncio->fecha_publicacion->toDateString(),
                'estado' => $anuncio->estado->value,
            ]);

        return Inertia::render('panel/anuncios/index', [
            'anuncios' => $anuncios,
            'puedeGestionar' => request()->user()->can('create', Anuncio::class),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Anuncio::class);

        return Inertia::render('panel/anuncios/create');
    }

    public function store(StoreAnuncioRequest $request): RedirectResponse
    {
        $this->authorize('create', Anuncio::class);

        $anuncio = Anuncio::create([
            ...$request->validated(),
            'usuario_id' => Auth::id(),
        ]);

        $this->auditoria->registrar('crear', $anuncio, $request->validated());

        return to_route('panel.anuncios.index');
    }

    public function edit(Anuncio $anuncio): Response
    {
        $this->authorize('update', $anuncio);

        return Inertia::render('panel/anuncios/edit', [
            'anuncio' => [
                'id' => $anuncio->id,
                'uuid' => $anuncio->uuid,
                'titulo' => $anuncio->titulo,
                'contenido' => $anuncio->contenido,
                'fecha_publicacion' => $anuncio->fecha_publicacion->toDateString(),
                'estado' => $anuncio->estado->value,
            ],
        ]);
    }

    public function update(UpdateAnuncioRequest $request, Anuncio $anuncio): RedirectResponse
    {
        $this->authorize('update', $anuncio);

        $anuncio->update($request->validated());

        $this->auditoria->registrar('actualizar', $anuncio, $request->validated());

        return to_route('panel.anuncios.index');
    }

    public function destroy(Anuncio $anuncio): RedirectResponse
    {
        $this->authorize('delete', $anuncio);

        $anuncio->delete();

        $this->auditoria->registrar('eliminar', $anuncio);

        return to_route('panel.anuncios.index');
    }
}
