<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Productos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Listado de productos</h3>
                    <a href="{{ route('productos.create') }}" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">
                        Nuevo producto
                    </a>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unidad de medida</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($productos as $producto)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-800">{{ $producto->nombre }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $producto->categoria->nombre }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $producto->proveedor->nombre }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $producto->unidad_medida }}</td>
                                <td class="px-4 py-2 text-sm">
                                    <a href="{{ route('productos.edit', $producto->id) }}" class="text-green-700 hover:underline mr-3">Editar</a>
                                    <form action="{{ route('productos.destroy', $producto->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este producto?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500">No hay productos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</x-app-layout>
