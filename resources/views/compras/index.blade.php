<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Compras</h2>
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
                    <h3 class="text-lg font-semibold text-gray-800">Órdenes de compra</h3>
                    @can('crear-compras')
                        <a href="{{ route('compras.create') }}" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">Nueva orden de compra</a>
                    @endcan
                </div>

                <form method="GET" action="{{ route('compras.index') }}" class="flex flex-wrap gap-2">
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por N° de orden..." class="border-gray-300 rounded-md shadow-sm text-sm">
                    <select name="estado" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Todos los estados</option>
                        @foreach (['pendiente' => 'Pendiente', 'parcial' => 'Parcial', 'recibida' => 'Recibida', 'anulada' => 'Anulada'] as $valor => $texto)
                            <option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $texto }}</option>
                        @endforeach
                    </select>
                    <x-primary-button>Filtrar</x-primary-button>
                </form>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Orden</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Creada por</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($compras as $compra)
                            <tr>
                                <td class="px-4 py-2 text-sm font-medium text-gray-800">{{ $compra->numero_orden }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $compra->fecha->format('d/m/Y') }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $compra->proveedor?->nombre ?? 'Sin proveedor' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $compra->usuario?->name }}</td>
                                <td class="px-4 py-2 text-sm text-gray-800 text-right">${{ number_format($compra->total, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-sm">
                                    @include('compras.partials.estado', ['estado' => $compra->estado])
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    <a href="{{ route('compras.show', $compra) }}" class="text-green-700 hover:underline mr-3">Ver</a>
                                    {{-- Solo se puede anular una orden pendiente (sin mercancía recibida) --}}
                                    @can('eliminar-compras')
                                        @if ($compra->estado === 'pendiente')
                                            <form action="{{ route('compras.destroy', $compra) }}" method="POST" class="inline" onsubmit="return confirm('¿Anular esta orden de compra?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Anular</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-sm text-gray-500">No hay órdenes de compra para mostrar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $compras->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
