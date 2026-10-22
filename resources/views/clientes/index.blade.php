<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Clientes {{ $verPapelera ? '(papelera)' : '' }}
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
                    <h3 class="text-lg font-semibold text-gray-800">Listado de clientes</h3>
                    <div class="flex items-center gap-4">
                        @can('eliminar-clientes')
                            @if ($verPapelera)
                                <a href="{{ route('clientes.index') }}" class="text-sm text-green-700 hover:underline">Ver clientes</a>
                            @else
                                <a href="{{ route('clientes.index', ['papelera' => 1]) }}" class="text-sm text-green-700 hover:underline">Ver papelera ({{ $totalPapelera }})</a>
                            @endif
                        @endcan
                        @can('crear-clientes')
                            <a href="{{ route('clientes.create') }}" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">Nuevo cliente</a>
                        @endcan
                    </div>
                </div>

                <form method="GET" action="{{ route('clientes.index') }}" class="flex flex-wrap gap-2">
                    @if ($verPapelera)
                        <input type="hidden" name="papelera" value="1">
                    @endif
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre o documento..." class="border-gray-300 rounded-md shadow-sm text-sm">
                    <select name="tipo" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Todos los tipos</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo }}" @selected(request('tipo') === $tipo)>{{ $tipo }}</option>
                        @endforeach
                    </select>
                    <x-primary-button>Filtrar</x-primary-button>
                </form>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Municipio</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($clientes as $cliente)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-800">{{ $cliente->nombre }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $cliente->tipo_cliente }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $cliente->documento }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $cliente->telefono }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $cliente->municipio }}</td>
                                <td class="px-4 py-2 text-sm">
                                    @if ($cliente->trashed())
                                        <form action="{{ route('clientes.restore', $cliente) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-green-700 hover:underline">Restaurar</button>
                                        </form>
                                    @else
                                        @can('editar-clientes')
                                            <a href="{{ route('clientes.edit', $cliente) }}" class="text-green-700 hover:underline mr-3">Editar</a>
                                        @endcan
                                        @can('eliminar-clientes')
                                            <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" class="inline" onsubmit="return confirm('¿Enviar este cliente a la papelera?');">
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
                                <td colspan="6" class="px-4 py-4 text-center text-sm text-gray-500">No hay clientes para mostrar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $clientes->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
