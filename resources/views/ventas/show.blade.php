<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Venta {{ $venta->numero_factura }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap justify-between gap-4">
                    <div class="text-sm text-gray-700 space-y-1">
                        <p><span class="font-semibold">Fecha:</span> {{ $venta->fecha->format('d/m/Y') }}</p>
                        <p><span class="font-semibold">Cliente:</span> {{ $venta->cliente?->nombre ?? 'Consumidor final' }}
                            @if ($venta->cliente?->documento) ({{ $venta->cliente->documento }}) @endif
                        </p>
                        <p><span class="font-semibold">Vendedor:</span> {{ $venta->usuario?->name }}</p>
                        @if ($venta->observaciones)
                            <p><span class="font-semibold">Observaciones:</span> {{ $venta->observaciones }}</p>
                        @endif
                    </div>
                    <div>
                        @if ($venta->estaAnulada())
                            <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-800">Anulada</span>
                        @else
                            <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">Completada</span>
                        @endif
                    </div>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lote</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Precio unit.</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">IVA</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($venta->detalles as $detalle)
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-800">{{ $detalle->producto?->nombre }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600">{{ $detalle->lote?->numero_lote }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 text-right">{{ $detalle->cantidad }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 text-right">${{ number_format($detalle->precio_unitario, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 text-right">{{ (float) $detalle->iva_porcentaje }} %</td>
                                <td class="px-3 py-2 text-sm text-gray-800 text-right">${{ number_format($detalle->subtotal, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="ms-auto w-full max-w-xs text-sm space-y-1">
                    <div class="flex justify-between"><span class="text-gray-600">Subtotal</span><span>${{ number_format($venta->subtotal, 2, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">IVA</span><span>${{ number_format($venta->iva, 2, ',', '.') }}</span></div>
                    <div class="flex justify-between text-base font-bold text-green-800"><span>Total</span><span>${{ number_format($venta->total, 2, ',', '.') }}</span></div>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <a href="{{ route('ventas.index') }}" class="text-sm text-gray-600 hover:underline">Volver al listado</a>
                    @can('eliminar-ventas')
                        @unless ($venta->estaAnulada())
                            <form action="{{ route('ventas.destroy', $venta) }}" method="POST" onsubmit="return confirm('¿Anular esta venta? El stock volverá a los lotes.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">Anular venta</button>
                            </form>
                        @endunless
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
