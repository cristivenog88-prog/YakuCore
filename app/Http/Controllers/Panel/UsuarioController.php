<?php

namespace App\Http\Controllers\Panel;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Usuario\StoreUsuarioRequest;
use App\Http\Requests\Usuario\UpdateUsuarioRequest;
use App\Models\Jass;
use App\Models\User;
use App\Services\Auditoria\RegistradorAuditoria;
use App\Services\Tenancy\ContextoJass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller compartido: presidente lo usa vía /panel/usuarios (su propia
 * JASS, implícita en ContextoJass) y superadmin vía
 * /superadmin/jass/{jass}/usuarios (cualquier JASS, explícita en la ruta).
 * UserPolicy decide qué puede hacer cada uno.
 */
class UsuarioController extends Controller
{
    public function __construct(private readonly RegistradorAuditoria $auditoria) {}

    public function index(Request $request, ?Jass $jass = null): Response
    {
        $this->authorize('viewAny', User::class);

        $jassObjetivo = $this->resolverJass($request, $jass);

        return Inertia::render('panel/usuarios/index', [
            'jass' => $jassObjetivo->only(['id', 'uuid', 'nombre']),
            'esSuperadmin' => $request->user()->esSuperadmin(),
            'usuarios' => User::where('jass_id', $jassObjetivo->id)
                ->orderBy('name')
                ->get(['id', 'uuid', 'name', 'email', 'rol']),
        ]);
    }

    public function create(Request $request, ?Jass $jass = null): Response
    {
        $this->authorize('create', User::class);

        $jassObjetivo = $this->resolverJass($request, $jass);

        return Inertia::render('panel/usuarios/create', [
            'jass' => $jassObjetivo->only(['id', 'uuid', 'nombre']),
            'esSuperadmin' => $request->user()->esSuperadmin(),
        ]);
    }

    public function store(StoreUsuarioRequest $request, ?Jass $jass = null): RedirectResponse
    {
        $jassObjetivo = $this->resolverJass($request, $jass);
        $datos = $request->validated();
        $rol = Rol::from($datos['rol']);

        $this->authorize('asignarRol', [User::class, $rol, $jassObjetivo->id]);

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'rol' => $rol,
            'jass_id' => $jassObjetivo->id,
        ]);

        $this->auditoria->registrar('crear', $usuario, ['rol' => $rol->value, 'jass_id' => $jassObjetivo->id]);

        return redirect($this->rutaIndice($jass));
    }

    public function edit(Request $request, User $usuario, ?Jass $jass = null): Response
    {
        $this->authorize('update', $usuario);

        $jassObjetivo = $this->resolverJass($request, $jass);

        return Inertia::render('panel/usuarios/edit', [
            'jass' => $jassObjetivo->only(['id', 'uuid', 'nombre']),
            'esSuperadmin' => $request->user()->esSuperadmin(),
            'usuario' => $usuario->only(['id', 'uuid', 'name', 'email', 'rol']),
        ]);
    }

    public function update(UpdateUsuarioRequest $request, User $usuario, ?Jass $jass = null): RedirectResponse
    {
        $this->authorize('update', $usuario);

        $datos = $request->validated();
        $rol = Rol::from($datos['rol']);

        $this->authorize('asignarRol', [User::class, $rol, $usuario->jass_id]);

        $usuario->name = $datos['name'];
        $usuario->email = $datos['email'];
        $usuario->rol = $rol;

        if (! empty($datos['password'])) {
            $usuario->password = $datos['password'];
        }

        $usuario->save();

        $this->auditoria->registrar('actualizar', $usuario, ['rol' => $rol->value]);

        return redirect($this->rutaIndice($jass));
    }

    public function destroy(User $usuario, ?Jass $jass = null): RedirectResponse
    {
        $this->authorize('delete', $usuario);

        $usuario->delete();

        $this->auditoria->registrar('eliminar', $usuario);

        return redirect($this->rutaIndice($jass));
    }

    private function resolverJass(Request $request, ?Jass $jass): Jass
    {
        return $jass ?? app(ContextoJass::class)->jass() ?? abort(403);
    }

    private function rutaIndice(?Jass $jass): string
    {
        return $jass
            ? route('superadmin.jass.usuarios.index', $jass)
            : route('panel.usuarios.index');
    }
}
