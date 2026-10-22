<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Productos {{ $verPapelera ? '(papelera)' : '' }}
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
                    <h3 class="text-lg font-semibold text-gray-800">Listado de productos</h3>
                    <div class="flex items-center gap-4">
                        @can('eliminar-productos')
                            @if ($verPapelera)
                                <a href="{{ route('productos.index') }}" class="text-sm text-green-700 hover:underline">Ver productos</a>
                            @else
                                <a href="{{ route('productos.index', ['papelera' => 1]) }}" class="text-sm text-green-700 hover:underline">Ver papelera ({{ $totalPapelera }})</a>
                            @endif
                        @endcan
                        @can('crear-productos')
                            <a href="{{ route('productos.create') }}" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">Nuevo producto</a>
                        @endcan
                    </div>
                </div>

                <form method="GET" action="{{ route('productos.index') }}" class="flex flex-wrap gap-2">
                    @if ($verPapelera)
                        <input type="hidden" name="papelera" value="1">
                    @endif
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre..." class="border-gray-300 rounded-md shadow-sm text-sm">
                    <select name="categoria" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Todas las categorías</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected(request('categoria') == $categoria->id)>{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                    <x-primary-button>Filtrar</x-primary-button>
                </form>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                            @modulo('proveedores')
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                            @endmodulo
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unidad de medida</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Precio</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($productos as $producto)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-800">{{ $producto->nombre }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $producto->categoria?->nombre }}</td>
                                @modulo('proveedores')
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $producto->proveedor?->nombre }}</td>
                                @endmodulo
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $producto->unidad_medida }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600 text-right">${{ number_format($producto->precio_venta, 0, ',', '.') }}</td>
                                <td class="px-4 py-2 text-sm text-right {{ (int) $producto->stock <= 0 ? 'text-red-600 font-semibold' : 'text-gray-600' }}">{{ (int) $producto->stock }}</td>
                                <td class="px-4 py-2 text-sm">
                                    @if ($producto->trashed())
                                        <form action="{{ route('productos.restore', $producto) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-green-700 hover:underline">Restaurar</button>
                                        </form>
                                    @else
                                        @can('editar-productos')
                                            <a href="{{ route('productos.edit', $producto) }}" class="text-green-700 hover:underline mr-3">Editar</a>
                                        @endcan
                                        @can('eliminar-productos')
                                            <form action="{{ route('productos.destroy', $producto) }}" method="POST" class="inline" onsubmit="return confirm('¿Enviar este producto a la papelera?');">
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
                                <td colspan="7" class="px-4 py-4 text-center text-sm text-gray-500">No hay productos para mostrar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $productos->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
