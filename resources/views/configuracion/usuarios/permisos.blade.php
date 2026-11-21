<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Permisos de {{ $usuario->name }}</h2>
            <a href="{{ route('configuracion.usuarios.index') }}" class="text-sm text-green-700 hover:underline">← Volver a usuarios</a>
        </div>
    </x-slot>

    @php
        $sinAcceso = [];
    @endphp

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3 text-sm text-gray-700">
                <p>
                    <span class="font-medium text-gray-900">{{ $usuario->name }}</span> · {{ $usuario->email }}
                    @if ($usuario->trashed())
                        <span class="ms-2 text-xs rounded-full bg-red-100 text-red-800 px-2 py-0.5">en la papelera (no puede iniciar sesión)</span>
                    @endif
                </p>
                <p>
                    Roles:
                    @forelse ($usuario->roles as $rol)
                        <span class="inline-block text-xs rounded-full bg-green-100 text-green-800 px-2 py-0.5">{{ $rol->name }}</span>
                    @empty
                        <span class="text-xs text-red-600">sin rol: no ve ningún módulo</span>
                    @endforelse
                </p>
                @if ($usuario->roles->count() > 1)
                    <p class="text-gray-600">
                        Tiene varios roles: al iniciar sesión <strong>elige con cuál trabaja</strong> (rol activo) y solo rigen los permisos de ese rol
                        <strong>más</strong> los permisos directos. La tabla muestra lo que aporta cada rol por separado.
                    </p>
                @endif
                <p class="text-gray-600">
                    <span class="inline-block text-xs rounded-full bg-green-100 text-green-800 px-2 py-0.5">Rol</span> permiso que viene del rol ·
                    <span class="inline-block text-xs rounded-full bg-amber-100 text-amber-800 px-2 py-0.5">Directo</span> permiso asignado solo a esta persona (vale con cualquier rol activo).
                </p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Módulo</th>
                            @foreach ($porRol as $nombreRol => $permisosRol)
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol {{ $nombreRol }} <span class="normal-case text-gray-400">({{ count($permisosRol) }})</span></th>
                            @endforeach
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Directos <span class="normal-case text-gray-400">({{ count($directos) }})</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($catalogo as $modulo => $acciones)
                            @php
                                $tiene = collect($acciones)->contains(fn ($permiso) => $usuario->roles->isNotEmpty() && (
                                    in_array($permiso, $directos) || collect($porRol)->contains(fn ($lista) => in_array($permiso, $lista))
                                ));
                                $clave = \App\Support\CatalogoPermisos::claveModuloSistema($modulo);
                                $apagado = $clave && ! \App\Models\Modulo::activo($clave);
                                if (! $tiene) { $sinAcceso[] = \App\Support\CatalogoPermisos::etiquetaModulo($modulo); }
                            @endphp
                            @continue(! $tiene)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ \App\Support\CatalogoPermisos::etiquetaModulo($modulo) }}
                                    @if ($apagado)
                                        <span class="ms-1 text-xs rounded-full bg-red-100 text-red-800 px-2 py-0.5">módulo desactivado</span>
                                    @endif
                                </td>
                                @foreach ($porRol as $permisosRol)
                                    <td class="px-4 py-3 text-sm">
                                        @php($delRol = collect($acciones)->filter(fn ($p) => in_array($p, $permisosRol)))
                                        @forelse ($delRol as $accion => $permiso)
                                            <span class="inline-block text-xs rounded-full bg-green-100 text-green-800 px-2 py-0.5 mb-1">{{ \App\Support\CatalogoPermisos::ACCIONES[$accion] ?? $accion }}</span>
                                        @empty
                                            <span class="text-xs text-gray-300">—</span>
                                        @endforelse
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-sm">
                                    @php($delUsuario = collect($acciones)->filter(fn ($p) => in_array($p, $directos)))
                                    @forelse ($delUsuario as $accion => $permiso)
                                        <span class="inline-block text-xs rounded-full bg-amber-100 text-amber-800 px-2 py-0.5 mb-1">{{ \App\Support\CatalogoPermisos::ACCIONES[$accion] ?? $accion }}</span>
                                    @empty
                                        <span class="text-xs text-gray-300">—</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($sinAcceso)
                <p class="text-sm text-gray-600">Sin ningún permiso en: {{ implode(', ', $sinAcceso) }}.</p>
            @endif
        </div>
    </div>
</x-app-layout>
