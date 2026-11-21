<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kardex de inventario</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded-md">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                <form method="GET" action="{{ route('inventario.kardex') }}" class="flex flex-wrap items-end gap-2">
                    <div>
                        <x-input-label for="producto" value="Producto" />
                        <select id="producto" name="producto" class="mt-1 border-gray-300 rounded-md shadow-sm text-sm" required>
                            <option value="">Seleccione un producto...</option>
                            @foreach ($productos as $p)
                                <option value="{{ $p->id }}" @selected($producto?->id === $p->id)>{{ $p->nombre }}{{ $p->trashed() ? ' (en papelera)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="concepto" value="Concepto" />
                        <select id="concepto" name="concepto" class="mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Todos</option>
                            @foreach ($conceptos as $clave => $etiqueta)
                                <option value="{{ $clave }}" @selected(request('concepto') === $clave)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="desde" value="Desde" />
                        <input id="desde" type="date" name="desde" value="{{ request('desde') }}" class="mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <x-input-label for="hasta" value="Hasta" />
                        <input id="hasta" type="date" name="hasta" value="{{ request('hasta') }}" class="mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                    </div>
                    <x-primary-button>Ver kardex</x-primary-button>
                </form>
            </div>

            @if ($producto)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ $producto->nombre }}</h3>
                            <p class="text-sm text-gray-500">Unidad: {{ $producto->unidad_medida }}</p>
                        </div>
                        @can('ajustar-kardex')
                            <a href="{{ route('inventario.ajustar', $producto) }}" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">Ajustar inventario</a>
                        @endcan
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                        <div class="rounded-md bg-green-50 p-3"><div class="text-gray-500">Entradas</div><div class="text-xl font-semibold text-green-800">{{ $totales['entradas'] }}</div></div>
                        <div class="rounded-md bg-red-50 p-3"><div class="text-gray-500">Salidas</div><div class="text-xl font-semibold text-red-800">{{ $totales['salidas'] }}</div></div>
                        <div class="rounded-md bg-gray-50 p-3"><div class="text-gray-500">Existencias en lotes</div><div class="text-xl font-semibold text-gray-800">{{ $totales['existencias'] }}</div></div>
                        <div class="rounded-md p-3 {{ $totales['cuadra'] ? 'bg-green-50' : 'bg-amber-50' }}">
                            <div class="text-gray-500">Control</div>
                            <div class="font-semibold {{ $totales['cuadra'] ? 'text-green-800' : 'text-amber-800' }}">
                                {{ $totales['cuadra'] ? 'El saldo del kardex cuadra con los lotes' : 'El saldo NO cuadra: hubo un cambio por fuera del kardex' }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-1">Lotes del producto</h4>
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-xs text-gray-500 uppercase">
                                    <th class="p-2">Lote</th><th class="p-2">Vence</th><th class="p-2 text-right">Existencias</th><th class="p-2">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($lotes as $lote)
                                    <tr>
                                        <td class="p-2">{{ $lote->numero_lote }}</td>
                                        <td class="p-2">{{ $lote->fecha_vencimiento->format('d/m/Y') }}</td>
                                        <td class="p-2 text-right">{{ $lote->cantidad_actual }}</td>
                                        <td class="p-2">
                                            @if ($lote->fecha_vencimiento->lt(today()))
                                                <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">Vencido</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Vigente</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="p-2 text-gray-500">Este producto aún no tiene lotes.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase">
                                <th class="px-3 py-2">Fecha</th>
                                <th class="px-3 py-2">Concepto</th>
                                <th class="px-3 py-2">Documento</th>
                                <th class="px-3 py-2">Lote</th>
                                <th class="px-3 py-2 text-right">Entrada</th>
                                <th class="px-3 py-2 text-right">Salida</th>
                                <th class="px-3 py-2 text-right">Saldo</th>
                                <th class="px-3 py-2">Usuario</th>
                                <th class="px-3 py-2">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($movimientos as $m)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2">{{ $m->conceptoEtiqueta() }}</td>
                                    <td class="px-3 py-2">{{ $m->documento() ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $m->lote?->numero_lote ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right text-green-700">{{ $m->tipo === 'entrada' ? $m->cantidad : '' }}</td>
                                    <td class="px-3 py-2 text-right text-red-700">{{ $m->tipo === 'salida' ? $m->cantidad : '' }}</td>
                                    <td class="px-3 py-2 text-right font-semibold">{{ $m->saldo }}</td>
                                    <td class="px-3 py-2">{{ $m->usuario?->name ?? 'Sistema' }}</td>
                                    <td class="px-3 py-2 text-gray-500">{{ $m->observaciones }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-3 py-4 text-center text-gray-500">No hay movimientos para este producto con los filtros elegidos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $movimientos->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
