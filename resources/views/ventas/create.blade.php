@php
    $itemsViejos = old('items', [['producto_id' => '', 'cantidad' => 1]]);
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nueva venta</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                {{-- Errores de negocio (por ejemplo: stock insuficiente) --}}
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md text-sm space-y-1">
                        @foreach (array_unique($errors->all()) as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('ventas.store') }}">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        @modulo('clientes')
                        <div>
                            <x-input-label for="cliente_id" value="Cliente" />
                            <select id="cliente_id" name="cliente_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="">-- Consumidor final --</option>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->nombre }}{{ $cliente->documento ? ' · '.$cliente->documento : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endmodulo
                        <div>
                            <x-input-label for="fecha" value="Fecha" />
                            <x-text-input id="fecha" name="fecha" type="date" class="mt-1 block w-full" :value="old('fecha', now()->toDateString())" max="{{ now()->toDateString() }}" required />
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-input-label for="observaciones" value="Observaciones (opcional)" />
                        <x-text-input id="observaciones" name="observaciones" type="text" class="mt-1 block w-full" :value="old('observaciones')" />
                    </div>

                    <h3 class="font-semibold text-gray-800 mb-2">Productos</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                    <th class="px-2 py-2">Producto</th>
                                    <th class="px-2 py-2 w-28">Cantidad</th>
                                    <th class="px-2 py-2 text-right">Precio unit.</th>
                                    <th class="px-2 py-2 text-right">Subtotal</th>
                                    <th class="px-2 py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="lineas"></tbody>
                        </table>
                    </div>

                    <button type="button" id="agregar" class="mt-2 text-sm text-green-700 hover:underline">+ Agregar producto</button>

                    <div class="ms-auto w-full max-w-xs text-sm space-y-1 mt-4">
                        <div class="flex justify-between"><span class="text-gray-600">Subtotal</span><span id="t-subtotal">$0</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">IVA</span><span id="t-iva">$0</span></div>
                        <div class="flex justify-between text-base font-bold text-green-800"><span>Total</span><span id="t-total">$0</span></div>
                    </div>

                    <div class="flex items-center gap-3 mt-6">
                        <x-primary-button>Registrar venta</x-primary-button>
                        <a href="{{ route('ventas.index') }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Los totales de pantalla son solo informativos: el servidor recalcula todo al guardar
        const PRODUCTOS = @json($productos);
        const ITEMS_INICIALES = @json($itemsViejos);
        const moneda = (n) => '$' + Number(n).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const lineas = document.getElementById('lineas');
        let contador = 0;

        function opciones(seleccionado) {
            let html = '<option value="">-- Seleccione --</option>';
            PRODUCTOS.forEach(p => {
                const sel = String(p.id) === String(seleccionado) ? 'selected' : '';
                html += `<option value="${p.id}" ${sel}>${p.nombre} (${p.unidad}) · stock ${p.stock}</option>`;
            });
            return html;
        }

        function agregarFila(item = { producto_id: '', cantidad: 1 }) {
            const i = contador++;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-2 py-1"><select name="items[${i}][producto_id]" class="producto block w-full border-gray-300 rounded-md shadow-sm text-sm" required>${opciones(item.producto_id)}</select></td>
                <td class="px-2 py-1"><input type="number" name="items[${i}][cantidad]" value="${item.cantidad ?? 1}" min="1" step="1" class="cantidad block w-full border-gray-300 rounded-md shadow-sm text-sm" required><span class="aviso block text-xs text-red-600 mt-1"></span></td>
                <td class="px-2 py-1 text-right precio">$0.00</td>
                <td class="px-2 py-1 text-right sub">$0.00</td>
                <td class="px-2 py-1 text-right"><button type="button" class="quitar text-red-600 hover:underline">Quitar</button></td>`;
            lineas.appendChild(tr);
            recalcular();
        }

        function recalcular() {
            let subtotal = 0;
            let iva = 0;
            lineas.querySelectorAll('tr').forEach(tr => {
                const p = PRODUCTOS.find(x => String(x.id) === tr.querySelector('.producto').value);
                const cantInput = tr.querySelector('.cantidad');
                const cant = parseInt(cantInput.value) || 0;
                const precio = p ? p.precio : 0;
                tr.querySelector('.aviso').textContent = (p && cant > p.stock) ? `Supera el stock (${p.stock})` : '';
                tr.querySelector('.precio').textContent = moneda(precio);
                tr.querySelector('.sub').textContent = moneda(precio * cant);
                subtotal += precio * cant;
                iva += precio * cant * (p ? p.iva : 0) / 100;
            });
            iva = Math.round(iva * 100) / 100;
            document.getElementById('t-subtotal').textContent = moneda(subtotal);
            document.getElementById('t-iva').textContent = moneda(iva);
            document.getElementById('t-total').textContent = moneda(subtotal + iva);
        }

        document.getElementById('agregar').addEventListener('click', () => agregarFila());
        lineas.addEventListener('input', recalcular);
        lineas.addEventListener('change', recalcular);
        lineas.addEventListener('click', (e) => {
            if (e.target.classList.contains('quitar') && lineas.querySelectorAll('tr').length > 1) {
                e.target.closest('tr').remove();
                recalcular();
            }
        });

        ITEMS_INICIALES.forEach(item => agregarFila(item));
    </script>
</x-app-layout>
