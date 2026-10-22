@php
    $itemsViejos = old('items', [['producto_id' => '', 'cantidad' => 1, 'costo_unitario' => '']]);
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nueva orden de compra</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md text-sm space-y-1">
                        @foreach (array_unique($errors->all()) as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('compras.store') }}">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        @modulo('proveedores')
                        <div>
                            <x-input-label for="proveedor_id" value="Proveedor" />
                            <select id="proveedor_id" name="proveedor_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="">-- Sin proveedor --</option>
                                @foreach ($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}" @selected(old('proveedor_id') == $proveedor->id)>{{ $proveedor->nombre }} · {{ $proveedor->nit }}</option>
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

                    <h3 class="font-semibold text-gray-800 mb-2">Productos a pedir</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                                    <th class="px-2 py-2">Producto</th>
                                    <th class="px-2 py-2 w-24">Cantidad</th>
                                    <th class="px-2 py-2 w-36">Costo unit.</th>
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

                    <p class="text-xs text-gray-500 mt-4">Pedir no mueve el inventario: el stock aumenta cuando registres la recepción de la mercancía.</p>

                    <div class="flex items-center gap-3 mt-4">
                        <x-primary-button>Guardar orden</x-primary-button>
                        <a href="{{ route('compras.index') }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
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
                html += `<option value="${p.id}" ${sel}>${p.nombre} (${p.unidad})</option>`;
            });
            return html;
        }

        function agregarFila(item = { producto_id: '', cantidad: 1, costo_unitario: '' }) {
            const i = contador++;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-2 py-1"><select name="items[${i}][producto_id]" class="producto block w-full border-gray-300 rounded-md shadow-sm text-sm" required>${opciones(item.producto_id)}</select></td>
                <td class="px-2 py-1"><input type="number" name="items[${i}][cantidad]" value="${item.cantidad ?? 1}" min="1" step="1" class="cantidad block w-full border-gray-300 rounded-md shadow-sm text-sm" required></td>
                <td class="px-2 py-1"><input type="number" name="items[${i}][costo_unitario]" value="${item.costo_unitario ?? ''}" min="0.01" step="0.01" class="costo block w-full border-gray-300 rounded-md shadow-sm text-sm" required></td>
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
                const cant = parseInt(tr.querySelector('.cantidad').value) || 0;
                const costo = parseFloat(tr.querySelector('.costo').value) || 0;
                const linea = cant * costo;
                tr.querySelector('.sub').textContent = moneda(linea);
                subtotal += linea;
                iva += linea * (p ? p.iva : 0) / 100;
            });
            iva = Math.round(iva * 100) / 100;
            document.getElementById('t-subtotal').textContent = moneda(subtotal);
            document.getElementById('t-iva').textContent = moneda(iva);
            document.getElementById('t-total').textContent = moneda(subtotal + iva);
        }

        document.getElementById('agregar').addEventListener('click', () => agregarFila());
        lineas.addEventListener('input', recalcular);
        lineas.addEventListener('change', (e) => {
            // Al elegir un producto se sugiere su último costo (editable)
            if (e.target.classList.contains('producto')) {
                const p = PRODUCTOS.find(x => String(x.id) === e.target.value);
                const costo = e.target.closest('tr').querySelector('.costo');
                if (p && p.costo > 0 && !costo.value) costo.value = p.costo;
            }
            recalcular();
        });
        lineas.addEventListener('click', (e) => {
            if (e.target.classList.contains('quitar') && lineas.querySelectorAll('tr').length > 1) {
                e.target.closest('tr').remove();
                recalcular();
            }
        });

        ITEMS_INICIALES.forEach(item => agregarFila(item));
    </script>
</x-app-layout>
