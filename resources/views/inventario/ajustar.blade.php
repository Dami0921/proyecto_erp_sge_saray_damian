<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ajustar inventario · {{ $producto->nombre }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded-md">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600">
                Use este formulario después de un <strong>conteo físico</strong> en bodega. Elija el lote, escriba la cantidad
                realmente contada y explique el motivo: el sistema registra solo la diferencia como una entrada o una salida
                en el kardex, a nombre suyo.
            </div>

            <form method="POST" action="{{ route('inventario.ajustar.guardar', $producto) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf

                <div>
                    <x-input-label for="lote_id" value="Lote" />
                    <select id="lote_id" name="lote_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Seleccione un lote...</option>
                        @foreach ($lotes as $lote)
                            <option value="{{ $lote->id }}" @selected((int) old('lote_id') === $lote->id)>
                                {{ $lote->numero_lote }} · vence {{ $lote->fecha_vencimiento->format('d/m/Y') }} · en sistema: {{ $lote->cantidad_actual }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('lote_id')" />
                </div>

                <div>
                    <x-input-label for="cantidad_contada" value="Cantidad contada en bodega" />
                    <x-text-input id="cantidad_contada" name="cantidad_contada" type="number" min="0" class="mt-1 block w-full" :value="old('cantidad_contada')" required />
                    <x-input-error class="mt-2" :messages="$errors->get('cantidad_contada')" />
                </div>

                <div>
                    <x-input-label for="motivo" value="Motivo del ajuste (obligatorio)" />
                    <textarea id="motivo" name="motivo" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>{{ old('motivo') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('motivo')" />
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>Registrar ajuste</x-primary-button>
                    <a href="{{ route('inventario.kardex', ['producto' => $producto->id]) }}" class="underline text-sm text-gray-600">Volver al kardex</a>
                </div>
            </form>

            @if ($ultimos->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Últimos movimientos del producto</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        @foreach ($ultimos as $m)
                            <li>{{ $m->created_at->format('d/m/Y H:i') }} · {{ $m->conceptoEtiqueta() }} · {{ $m->tipo }} de {{ $m->cantidad }} · saldo {{ $m->saldo }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
