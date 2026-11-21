<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Proveedores {{ $verPapelera ? '(papelera)' : '' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">

                <div class="flex flex-wrap justify-between items-center gap-3">
                    <h3 class="text-lg font-semibold text-gray-800">Listado de proveedores</h3>
                    <div class="flex items-center gap-4">
                        @can('eliminar-proveedores')
                            @if ($verPapelera)
                                <a href="{{ route('proveedores.index') }}" class="text-sm text-green-700 hover:underline">Ver proveedores</a>
                            @else
                                <a href="{{ route('proveedores.index', ['papelera' => 1]) }}" class="text-sm text-green-700 hover:underline">Ver papelera ({{ $totalPapelera }})</a>
                            @endif
                        @endcan
                        @can('importar-proveedores')
                            <a href="{{ route('proveedores.importar') }}" class="text-sm text-green-700 hover:underline">Importar desde Excel</a>
                        @endcan
                        @can('crear-proveedores')
                            <a href="{{ route('proveedores.create') }}" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">Nuevo proveedor</a>
                        @endcan
                    </div>
                </div>

                <form method="GET" action="{{ route('proveedores.index') }}" class="flex flex-wrap gap-2">
                    @if ($verPapelera)
                        <input type="hidden" name="papelera" value="1">
                    @endif
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre o NIT..." class="border-gray-300 rounded-md shadow-sm text-sm">
                    <x-primary-button>Filtrar</x-primary-button>
                </form>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">NIT</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dirección</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($proveedores as $proveedor)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-800">{{ $proveedor->nombre }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $proveedor->nit }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $proveedor->telefono }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $proveedor->direccion }}</td>
                                <td class="px-4 py-2 text-sm">
                                    @if ($proveedor->trashed())
                                        <form action="{{ route('proveedores.restore', $proveedor) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-green-700 hover:underline">Restaurar</button>
                                        </form>
                                    @else
                                        @can('editar-proveedores')
                                            <a href="{{ route('proveedores.edit', $proveedor) }}" class="text-green-700 hover:underline mr-3">Editar</a>
                                        @endcan
                                        @can('eliminar-proveedores')
                                            <form action="{{ route('proveedores.destroy', $proveedor) }}" method="POST" class="inline" onsubmit="return confirm('¿Enviar este proveedor a la papelera?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500">No hay proveedores para mostrar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $proveedores->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
