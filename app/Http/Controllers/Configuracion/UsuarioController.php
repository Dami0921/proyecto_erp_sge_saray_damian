<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\User;
use App\Services\SeguridadService;
use App\Support\CatalogoPermisos;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function __construct(private SeguridadService $service)
    {
        $this->middleware('permission:gestionar-usuarios');
    }

    public function index(Request $request)
    {
        $verPapelera = $request->boolean('papelera');

        $usuarios = User::query()
            ->with(['roles', 'permissions'])
            ->when($verPapelera, fn ($q) => $q->onlyTrashed())
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $texto = '%'.$request->string('buscar').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $texto)->orWhere('email', 'like', $texto));
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('configuracion.usuarios.index', [
            'usuarios' => $usuarios,
            'verPapelera' => $verPapelera,
            'totalPapelera' => User::onlyTrashed()->count(),
        ]);
    }

    /** Permisos efectivos de un usuario: qué trae de cada rol y qué tiene como permiso directo. */
    public function permisos(User $usuario)
    {
        $usuario->load(['roles.permissions', 'permissions']);

        return view('configuracion.usuarios.permisos', [
            'usuario' => $usuario,
            'catalogo' => CatalogoPermisos::agrupar(Permission::orderBy('name')->pluck('name')),
            'porRol' => $usuario->roles->mapWithKeys(fn (Role $rol) => [$rol->name => $rol->permissions->pluck('name')->all()]),
            'directos' => $usuario->permissions->pluck('name')->all(),
        ]);
    }

    public function create()
    {
        return view('configuracion.usuarios.create', $this->datosFormulario(new User));
    }

    public function store(StoreUsuarioRequest $request)
    {
        $user = $this->service->crearUsuario($request->validated());

        return redirect()->route('configuracion.usuarios.index')
            ->with('success', "Usuario «{$user->name}» creado con el/los rol(es): ".$user->getRoleNames()->join(', ').'.');
    }

    public function edit(User $usuario)
    {
        return view('configuracion.usuarios.edit', $this->datosFormulario($usuario));
    }

    public function update(UpdateUsuarioRequest $request, User $usuario)
    {
        $this->service->actualizarUsuario($usuario, $request->validated(), $request->user());

        return redirect()->route('configuracion.usuarios.index')
            ->with('success', "Usuario «{$usuario->name}» actualizado.");
    }

    public function destroy(Request $request, User $usuario)
    {
        try {
            $this->service->eliminarUsuario($usuario, $request->user());
        } catch (ValidationException $e) {
            return redirect()->route('configuracion.usuarios.index')->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('configuracion.usuarios.index')
            ->with('success', "Usuario «{$usuario->name}» enviado a la papelera. Ya no puede iniciar sesión.");
    }

    public function restore(User $usuario)
    {
        $this->service->restaurarUsuario($usuario);

        return redirect()->route('configuracion.usuarios.index')
            ->with('success', "Usuario «{$usuario->name}» restaurado.");
    }

    private function datosFormulario(User $usuario): array
    {
        return [
            'usuario' => $usuario,
            'roles' => Role::orderBy('name')->get(),
            'permisosAgrupados' => CatalogoPermisos::agrupar(Permission::orderBy('name')->pluck('name')),
            'rolesDelUsuario' => old('roles', $usuario->exists ? $usuario->getRoleNames()->all() : []),
            'permisosDirectos' => old('permisos', $usuario->exists ? $usuario->getDirectPermissions()->pluck('name')->all() : []),
        ];
    }
}
