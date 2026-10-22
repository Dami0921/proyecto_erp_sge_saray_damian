<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ventas</h2>
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
                    <h3 class="text-lg font-semibold text-gray-800">Listado de ventas</h3>
                    @can('crear-ventas')
                        <a href="{{ route('ventas.create') }}" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">Nueva venta</a>
                    @endcan
                </div>

                <form method="GET" action="{{ route('ventas.index') }}" class="flex flex-wrap gap-2">
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por N° de factura..." class="border-gray-300 rounded-md shadow-sm text-sm">
                    <select name="estado" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Todos los estados</option>
                        <option value="completada" @selected(request('estado') === 'completada')>Completada</option>
                        <option value="anulada" @selected(request('estado') === 'anulada')>Anulada</option>
                    </select>
                    <x-primary-button>Filtrar</x-primary-button>
                </form>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Factura</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Vendedor</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($ventas as $venta)
                            <tr>
                                <td class="px-4 py-2 text-sm font-medium text-gray-800">{{ $venta->numero_factura }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $venta->fecha->format('d/m/Y') }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $venta->cliente?->nombre ?? 'Consumidor final' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $venta->usuario?->name }}</td>
                                <td class="px-4 py-2 text-sm text-gray-800 text-right">${{ number_format($venta->total, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-sm">
                                    @if ($venta->estaAnulada())
                                        <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">Anulada</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Completada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    <a href="{{ route('ventas.show', $venta) }}" class="text-green-700 hover:underline mr-3">Ver</a>
                                    @can('eliminar-ventas')
                                        @unless ($venta->estaAnulada())
                                            <form action="{{ route('ventas.destroy', $venta) }}" method="POST" class="inline" onsubmit="return confirm('¿Anular esta venta? El stock volverá a los lotes.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Anular</button>
                                            </form>
                                        @endunless
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-sm text-gray-500">No hay ventas para mostrar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $ventas->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
