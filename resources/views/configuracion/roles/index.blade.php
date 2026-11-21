<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Roles y permisos · Matriz de seguridad</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded-md">{{ session('error') }}</div>
            @endif
            <x-input-error :messages="$errors->get('rol')" />

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600">
                Cada tabla muestra, para un rol, qué <strong>acciones</strong> puede hacer en cada <strong>módulo</strong>.
                El rol <strong>admin</strong> siempre conserva todos los permisos (no se puede editar). Los cambios se aplican
                de inmediato a todos los usuarios que tengan ese rol activo.
            </div>

            <form method="POST" action="{{ route('configuracion.roles.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                @foreach ($roles as $rol)
                    @php($bloqueado = $rol->name === 'admin')
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="flex items-center justify-between gap-3 px-6 py-3 bg-green-50 border-b border-green-100">
                            <div>
                                <span class="font-semibold text-green-900">{{ $rol->name }}</span>
                                <span class="ms-2 text-xs text-gray-500">{{ $rol->users_count }} usuario(s)</span>
                                @if ($bloqueado)
                                    <span class="ms-2 text-xs rounded-full bg-gray-200 text-gray-700 px-2 py-0.5">todos los permisos · bloqueado</span>
                                @elseif (in_array($rol->name, $rolesBase, true))
                                    <span class="ms-2 text-xs rounded-full bg-gray-100 text-gray-600 px-2 py-0.5">rol base</span>
                                @endif
                            </div>
                            @unless (in_array($rol->name, $rolesBase, true))
                                <button type="submit" form="eliminar-rol-{{ $rol->id }}" class="text-sm text-red-600 hover:underline"
                                    onclick="return confirm('¿Eliminar el rol {{ $rol->name }}?');">Eliminar rol</button>
                            @endunless
                        </div>

                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Módulo</th>
                                    @foreach (\App\Support\CatalogoPermisos::ACCIONES as $accion => $etiqueta)
                                        <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ $etiqueta }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($tabla as $modulo => $acciones)
                                    <tr>
                                        <td class="px-4 py-1.5 text-sm text-gray-800">{{ \App\Support\CatalogoPermisos::etiquetaModulo($modulo) }}</td>
                                        @foreach (\App\Support\CatalogoPermisos::ACCIONES as $accion => $etiqueta)
                                            <td class="px-2 py-1.5 text-center">
                                                @if (isset($acciones[$accion]))
                                                    <input type="checkbox" name="permisos[{{ $rol->id }}][]" value="{{ $acciones[$accion] }}"
                                                        class="rounded border-gray-300 text-green-700 focus:ring-green-600 {{ $bloqueado ? 'opacity-60' : '' }}"
                                                        title="{{ $acciones[$accion] }}"
                                                        @checked($bloqueado || $rol->permissions->contains('name', $acciones[$accion]))
                                                        @disabled($bloqueado)>
                                                @else
                                                    <span class="text-gray-300">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                <x-primary-button>Guardar matriz de permisos</x-primary-button>
            </form>

            {{-- Formularios de eliminar (fuera del formulario principal: HTML no permite formularios anidados) --}}
            @foreach ($roles as $rol)
                @unless (in_array($rol->name, $rolesBase, true))
                    <form id="eliminar-rol-{{ $rol->id }}" method="POST" action="{{ route('configuracion.roles.destroy', $rol) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                @endunless
            @endforeach

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-2">Crear un rol nuevo</h3>
                <form method="POST" action="{{ route('configuracion.roles.store') }}" class="flex flex-wrap items-start gap-3">
                    @csrf
                    <div>
                        <x-text-input name="nombre" type="text" placeholder="ej.: jefe-bodega" :value="old('nombre')" required />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>
                    <x-primary-button>Crear rol</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
