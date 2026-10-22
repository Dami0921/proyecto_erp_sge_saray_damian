<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('titulo')</title>
    {{-- DomPDF no carga Tailwind: todo el estilo va en línea; DejaVu Sans soporta tildes y la ñ --}}
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0; color: #166534; }
        .encabezado { width: 100%; border-bottom: 2px solid #166534; padding-bottom: 8px; margin-bottom: 12px; }
        .encabezado td { vertical-align: top; }
        .derecha { text-align: right; }
        .muted { color: #6b7280; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th { background: #166534; color: #ffffff; padding: 5px; font-size: 10px; text-align: left; }
        table.items td { border-bottom: 1px solid #e5e7eb; padding: 5px; }
        table.items th.num, table.items td.num { text-align: right; }
        table.totales { width: 40%; margin-left: 60%; margin-top: 10px; border-collapse: collapse; }
        table.totales td { padding: 3px 5px; }
        table.totales tr.total td { font-weight: bold; font-size: 13px; color: #166534; border-top: 1px solid #166534; }
        .estado { display: inline-block; margin-top: 6px; padding: 2px 8px; border: 1px solid #166534; color: #166534; font-weight: bold; }
        .estado.anulada { border-color: #b91c1c; color: #b91c1c; }
        .pie { margin-top: 30px; font-size: 9px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <table class="encabezado">
        <tr>
            <td>
                <h1>{{ $empresa['nombre'] }}</h1>
                @if ($empresa['nit'])<div class="muted">NIT: {{ $empresa['nit'] }}</div>@endif
                @if ($empresa['direccion'] || $empresa['telefono'])
                    <div class="muted">{{ $empresa['direccion'] }}@if ($empresa['direccion'] && $empresa['telefono']) · @endif @if ($empresa['telefono']) Tel. {{ $empresa['telefono'] }} @endif</div>
                @endif
            </td>
            <td class="derecha">
                <div style="font-size:14px; font-weight:bold;">@yield('documento')</div>
                <div style="font-size:14px;">@yield('numero')</div>
                <div class="muted">Fecha: @yield('fecha')</div>
            </td>
        </tr>
    </table>

    @yield('contenido')

    <div class="pie">Documento interno de control: no es factura electrónica ni tiene validez ante la DIAN.<br>Documento generado el {{ now()->format('d/m/Y H:i') }} por el sistema AgroInsumos.</div>
</body>
</html>
