<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Importar productos desde Excel</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-700 space-y-2">
                <p><strong>Paso 1.</strong> Descargue la plantilla y llénela (una fila por producto).
                    <a href="{{ route('productos.plantilla') }}" class="text-green-700 underline">Descargar plantilla</a></p>
                <p><strong>Paso 2.</strong> Columnas: <code>nombre</code>, <code>categoria</code> (debe existir),
                    <code>unidad_medida</code>, <code>precio_venta</code> e <code>iva_porcentaje</code> (0 si no lleva IVA).</p>
                <p><strong>Paso 3.</strong> Suba el archivo. Si un producto ya existe (mismo nombre) se actualizan su categoría,
                    unidad, precio e IVA. <strong>Las existencias nunca se importan</strong>: entran por Compras o por un ajuste del kardex.
                    Las filas con errores se omiten y se listan abajo; las demás se guardan.</p>
            </div>

            <form method="POST" action="{{ route('productos.importar.guardar') }}" enctype="multipart/form-data" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                <div>
                    <x-input-label for="archivo" value="Archivo (.xlsx, .xls o .csv, máximo 2 MB)" />
                    <input id="archivo" type="file" name="archivo" accept=".xlsx,.xls,.csv" class="mt-1 block" required>
                    <x-input-error class="mt-2" :messages="$errors->get('archivo')" />
                </div>
                <div class="flex items-center gap-4">
                    <x-primary-button>Importar</x-primary-button>
                    <a href="{{ route('productos.index') }}" class="underline text-sm text-gray-600">Volver a productos</a>
                </div>
            </form>

            @if (session('fallas'))
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-red-700 mb-2">Filas omitidas por errores ({{ count(session('fallas')) }})</h3>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-xs text-gray-500 uppercase">
                                <th class="p-2">Fila</th><th class="p-2">Columna</th><th class="p-2">Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (session('fallas') as $falla)
                                <tr class="border-b">
                                    <td class="p-2">{{ $falla['fila'] }}</td>
                                    <td class="p-2">{{ $falla['columna'] }}</td>
                                    <td class="p-2">{{ implode(' ', $falla['errores']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
