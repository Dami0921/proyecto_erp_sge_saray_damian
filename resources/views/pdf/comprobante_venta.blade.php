@extends('pdf.base')

@section('titulo', 'Comprobante '.$venta->numero_factura)
@section('documento', 'COMPROBANTE DE VENTA')
@section('numero', $venta->numero_factura)
@section('fecha', $venta->fecha->format('d/m/Y'))

@section('contenido')
    <p>
        <strong>Cliente:</strong> {{ $venta->cliente?->nombre ?? 'Consumidor final' }}
        @if ($venta->cliente?->documento) · Documento: {{ $venta->cliente->documento }} @endif
        <br>
        <strong>Vendedor:</strong> {{ $venta->usuario?->name }}
        <br>
        <span class="estado {{ $venta->estaAnulada() ? 'anulada' : '' }}">{{ $venta->estaAnulada() ? 'ANULADA' : 'COMPLETADA' }}</span>
    </p>

    <table class="items">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Lote</th>
                <th class="num">Cant.</th>
                <th class="num">Precio unit.</th>
                <th class="num">IVA</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($venta->detalles as $d)
                <tr>
                    <td>{{ $d->producto?->nombre }}</td>
                    <td>{{ $d->lote?->numero_lote }}</td>
                    <td class="num">{{ $d->cantidad }}</td>
                    <td class="num">${{ number_format($d->precio_unitario, 2, ',', '.') }}</td>
                    <td class="num">{{ (float) $d->iva_porcentaje }} %</td>
                    <td class="num">${{ number_format($d->subtotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales">
        <tr><td>Subtotal</td><td class="derecha">${{ number_format($venta->subtotal, 2, ',', '.') }}</td></tr>
        <tr><td>IVA</td><td class="derecha">${{ number_format($venta->iva, 2, ',', '.') }}</td></tr>
        <tr class="total"><td>Total</td><td class="derecha">${{ number_format($venta->total, 2, ',', '.') }}</td></tr>
    </table>

    @if ($venta->observaciones)
        <p><strong>Observaciones:</strong> {{ $venta->observaciones }}</p>
    @endif
@endsection
