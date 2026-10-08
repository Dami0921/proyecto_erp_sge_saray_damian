<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Categorías {{ $verPapelera ? '(papelera)' : '' }}
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
                    <h3 class="text-lg font-semibold text-gray-800">Listado de categorías</h3>
                    <div class="flex items-center gap-4">
                        @can('eliminar-categorias')
                            @if ($verPapelera)
                                <a href="{{ route('categorias.index') }}" class="text-sm text-green-700 hover:underline">Ver categorías</a>
                            @else
                                <a href="{{ route('categorias.index', ['papelera' => 1]) }}" class="text-sm text-green-700 hover:underline">Ver papelera ({{ $totalPapelera }})</a>
                            @endif
                        @endcan
                        @can('crear-categorias')
                            <a href="{{ route('categorias.create') }}" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">Nueva categoría</a>
                        @endcan
                    </div>
                </div>

                <form method="GET" action="{{ route('categorias.index') }}" class="flex flex-wrap gap-2">
                    @if ($verPapelera)
                        <input type="hidden" name="papelera" value="1">
                    @endif
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre..." class="border-gray-300 rounded-md shadow-sm text-sm">
                    <x-primary-button>Filtrar</x-primary-button>
                </form>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Productos</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($categorias as $categoria)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-800">{{ $categoria->nombre }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $categoria->productos_count }}</td>
                                <td class="px-4 py-2 text-sm">
                                    @if ($categoria->trashed())
                                        <form action="{{ route('categorias.restore', $categoria) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-green-700 hover:underline">Restaurar</button>
                                        </form>
                                    @else
                                        @can('editar-categorias')
                                            <a href="{{ route('categorias.edit', $categoria) }}" class="text-green-700 hover:underline mr-3">Editar</a>
                                        @endcan
                                        @can('eliminar-categorias')
                                            <form action="{{ route('categorias.destroy', $categoria) }}" method="POST" class="inline" onsubmit="return confirm('¿Enviar esta categoría a la papelera?');">
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
                                <td colspan="3" class="px-4 py-4 text-center text-sm text-gray-500">No hay categorías para mostrar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $categorias->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
