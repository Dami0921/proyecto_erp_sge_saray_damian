<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Orden de compra {{ $compra->numero_orden }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif
            {{-- Errores de negocio de la recepción (por ejemplo: más de lo pedido) --}}
            @if ($errors->any())
                <div class="p-4 bg-red-100 text-red-800 rounded-md text-sm space-y-1">
                    @foreach (array_unique($errors->all()) as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap justify-between gap-4">
                    <div class="text-sm text-gray-700 space-y-1">
                        <p><span class="font-semibold">Fecha:</span> {{ $compra->fecha->format('d/m/Y') }}</p>
                        <p><span class="font-semibold">Proveedor:</span> {{ $compra->proveedor?->nombre ?? 'Sin proveedor' }}
                            @if ($compra->proveedor?->nit) (NIT {{ $compra->proveedor->nit }}) @endif
                        </p>
                        <p><span class="font-semibold">Creada por:</span> {{ $compra->usuario?->name }}</p>
                        @if ($compra->observaciones)
                            <p><span class="font-semibold">Observaciones:</span> {{ $compra->observaciones }}</p>
                        @endif
                        @if ($compra->recibida_at)
                            <p><span class="font-semibold">Recibida el:</span> {{ $compra->recibida_at->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                    <div>@include('compras.partials.estado', ['estado' => $compra->estado])</div>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Pedida</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Recibida</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Pendiente</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Costo unit.</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">IVA</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($compra->detalles as $detalle)
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-800">{{ $detalle->producto?->nombre }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 text-right">{{ $detalle->cantidad }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 text-right">{{ $detalle->cantidad_recibida }}</td>
                                <td class="px-3 py-2 text-sm text-right {{ $detalle->pendiente > 0 && ! $compra->estaAnulada() ? 'text-yellow-700 font-semibold' : 'text-gray-600' }}">{{ $compra->estaAnulada() ? '—' : $detalle->pendiente }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 text-right">${{ number_format($detalle->costo_unitario, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 text-right">{{ (float) $detalle->iva_porcentaje }} %</td>
                                <td class="px-3 py-2 text-sm text-gray-800 text-right">${{ number_format($detalle->subtotal, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="ms-auto w-full max-w-xs text-sm space-y-1">
                    <div class="flex justify-between"><span class="text-gray-600">Subtotal</span><span>${{ number_format($compra->subtotal, 2, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">IVA</span><span>${{ number_format($compra->iva, 2, ',', '.') }}</span></div>
                    <div class="flex justify-between text-base font-bold text-green-800"><span>Total</span><span>${{ number_format($compra->total, 2, ',', '.') }}</span></div>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <a href="{{ route('compras.index') }}" class="text-sm text-gray-600 hover:underline">Volver al listado</a>
                    <a href="{{ route('compras.pdf', $compra) }}" target="_blank" class="text-sm text-green-700 hover:underline">Descargar PDF</a>
                    
                    @can('eliminar-compras')
                        @if ($compra->estado === 'pendiente')
                            <form action="{{ route('compras.destroy', $compra) }}" method="POST" onsubmit="return confirm('¿Anular esta orden de compra?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">Anular orden</button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>

            {{-- Recepción de mercancía: crea un lote por cada línea recibida --}}
            @can('recibir-compras')
                @if ($compra->admiteRecepcion())
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-3">
                        <h3 class="font-semibold text-gray-800">Registrar recepción de mercancía</h3>
                        <p class="text-sm text-gray-600">Indique cuánto llega ahora y la fecha de vencimiento de cada lote. Se puede recibir por partes; no se admite mercancía vencida.</p>

                        <form method="POST" action="{{ route('compras.recibir', $compra) }}" class="space-y-3">
                            @csrf
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                            <th class="px-2 py-2">Producto</th>
                                            <th class="px-2 py-2 text-right">Pendiente</th>
                                            <th class="px-2 py-2 w-32">Llega ahora</th>
                                            <th class="px-2 py-2 w-44">Vence</th>
                                            <th class="px-2 py-2 w-44">N° de lote (opcional)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($compra->detalles->filter(fn ($d) => $d->pendiente > 0) as $detalle)
                                            <tr>
                                                <td class="px-2 py-2 text-gray-800">{{ $detalle->producto?->nombre }}</td>
                                                <td class="px-2 py-2 text-right text-gray-600">{{ $detalle->pendiente }}</td>
                                                <td class="px-2 py-1">
                                                    <input type="number" name="recepcion[{{ $detalle->id }}][cantidad]" min="0" step="1"
                                                           value="{{ old('recepcion.'.$detalle->id.'.cantidad', $detalle->pendiente) }}"
                                                           class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                </td>
                                                <td class="px-2 py-1">
                                                    <input type="date" name="recepcion[{{ $detalle->id }}][fecha_vencimiento]"
                                                           value="{{ old('recepcion.'.$detalle->id.'.fecha_vencimiento') }}"
                                                           class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                </td>
                                                <td class="px-2 py-1">
                                                    <input type="text" name="recepcion[{{ $detalle->id }}][numero_lote]" maxlength="50"
                                                           value="{{ old('recepcion.'.$detalle->id.'.numero_lote') }}"
                                                           class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <x-primary-button>Registrar recepción</x-primary-button>
                        </form>
                    </div>
                @endif
            @endcan

            {{-- Lotes que ya nacieron de esta orden --}}
            @php
                $lotes = $compra->detalles->flatMap(fn ($d) => $d->lotes->map(fn ($l) => [$d, $l]));
            @endphp
            @if ($lotes->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-2">Lotes recibidos</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lote</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ingreso</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Vence</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($lotes as [$detalle, $lote])
                                <tr>
                                    <td class="px-3 py-2 text-sm text-gray-800">{{ $lote->numero_lote }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-600">{{ $detalle->producto?->nombre }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-600 text-right">{{ $lote->cantidad_inicial }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-600">{{ $lote->fecha_ingreso->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-600">{{ $lote->fecha_vencimiento->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
