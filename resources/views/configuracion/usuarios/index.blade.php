<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Usuarios {{ $verPapelera ? '(papelera)' : '' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">

                <div class="flex flex-wrap justify-between items-center gap-3">
                    <h3 class="text-lg font-semibold text-gray-800">Listado de usuarios</h3>
                    <div class="flex items-center gap-4">
                        @if ($verPapelera)
                            <a href="{{ route('configuracion.usuarios.index') }}" class="text-sm text-green-700 hover:underline">Ver usuarios</a>
                        @else
                            <a href="{{ route('configuracion.usuarios.index', ['papelera' => 1]) }}" class="text-sm text-green-700 hover:underline">Ver papelera ({{ $totalPapelera }})</a>
                        @endif
                        <a href="{{ route('configuracion.usuarios.create') }}" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">Nuevo usuario</a>
                    </div>
                </div>

                <form method="GET" action="{{ route('configuracion.usuarios.index') }}" class="flex flex-wrap gap-2">
                    @if ($verPapelera)
                        <input type="hidden" name="papelera" value="1">
                    @endif
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre o correo..." class="border-gray-300 rounded-md shadow-sm text-sm">
                    <x-primary-button>Filtrar</x-primary-button>
                </form>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Correo</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Roles</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Permisos directos</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($usuarios as $u)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-800">
                                    {{ $u->name }}
                                    @if ($u->is(auth()->user()))
                                        <span class="ms-1 text-xs rounded-full bg-gray-100 text-gray-600 px-2 py-0.5">tú</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $u->email }}</td>
                                <td class="px-4 py-2 text-sm">
                                    @forelse ($u->roles as $rol)
                                        <span class="inline-block text-xs rounded-full bg-green-100 text-green-800 px-2 py-0.5">{{ $rol->name }}</span>
                                    @empty
                                        <span class="text-xs text-red-600">sin rol</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    @forelse ($u->permissions as $permiso)
                                        <span class="inline-block text-xs rounded-full bg-amber-100 text-amber-800 px-2 py-0.5">{{ $permiso->name }}</span>
                                    @empty
                                        <span class="text-xs text-gray-400">—</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    @if ($u->trashed())
                                        <form action="{{ route('configuracion.usuarios.restore', $u) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-green-700 hover:underline">Restaurar</button>
                                        </form>
                                    @else
                                        <a href="{{ route('configuracion.usuarios.edit', $u) }}" class="text-green-700 hover:underline mr-3">Editar</a>
                                        <form action="{{ route('configuracion.usuarios.destroy', $u) }}" method="POST" class="inline" onsubmit="return confirm('¿Enviar este usuario a la papelera? Ya no podrá iniciar sesión.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500">No hay usuarios para mostrar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $usuarios->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
