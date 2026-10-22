@extends('pdf.base')

@section('titulo', 'Orden de compra '.$compra->numero_orden)
@section('documento', 'ORDEN DE COMPRA')
@section('numero', $compra->numero_orden)
@section('fecha', $compra->fecha->format('d/m/Y'))

@section('contenido')
    <p>
        <strong>Proveedor:</strong> {{ $compra->proveedor?->nombre ?? 'Sin proveedor' }}
        @if ($compra->proveedor?->nit) · NIT: {{ $compra->proveedor->nit }} @endif
        <br>
        <strong>Creada por:</strong> {{ $compra->usuario?->name }}
        <br>
        <span class="estado {{ $compra->estaAnulada() ? 'anulada' : '' }}">{{ strtoupper($compra->estado) }}</span>
        @if ($compra->recibida_at)
            <br><strong>Recibida el:</strong> {{ $compra->recibida_at->format('d/m/Y H:i') }}
        @endif
    </p>

    <table class="items">
        <thead>
            <tr>
                <th>Producto</th>
                <th class="num">Pedida</th>
                <th class="num">Recibida</th>
                <th class="num">Pendiente</th>
                <th class="num">Costo unit.</th>
                <th class="num">IVA</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($compra->detalles as $d)
                <tr>
                    <td>{{ $d->producto?->nombre }}</td>
                    <td class="num">{{ $d->cantidad }}</td>
                    <td class="num">{{ $d->cantidad_recibida }}</td>
                    <td class="num">{{ $compra->estaAnulada() ? '—' : $d->pendiente }}</td>
                    <td class="num">${{ number_format($d->costo_unitario, 2, ',', '.') }}</td>
                    <td class="num">{{ (float) $d->iva_porcentaje }} %</td>
                    <td class="num">${{ number_format($d->subtotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales">
        <tr><td>Subtotal</td><td class="derecha">${{ number_format($compra->subtotal, 2, ',', '.') }}</td></tr>
        <tr><td>IVA</td><td class="derecha">${{ number_format($compra->iva, 2, ',', '.') }}</td></tr>
        <tr class="total"><td>Total</td><td class="derecha">${{ number_format($compra->total, 2, ',', '.') }}</td></tr>
    </table>

    @if ($compra->observaciones)
        <p><strong>Observaciones:</strong> {{ $compra->observaciones }}</p>
    @endif
@endsection
