<?php

namespace App\Http\Controllers;

use App\Exports\PlantillaProductosExport;
use App\Exports\ProductosExport;
use App\Exports\VentasExport;
use App\Http\Requests\ImportarProductosRequest;
use App\Imports\ProductosImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/** Intercambio de información con hojas de cálculo: exportar productos y ventas, importar productos. */
class ExcelController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:exportar-productos')->only('exportarProductos');
        $this->middleware('permission:exportar-ventas')->only('exportarVentas');
        $this->middleware('permission:importar-productos')->only(['formularioImportar', 'importar', 'plantilla']);
    }

    public function exportarProductos(Request $request)
    {
        return Excel::download(new ProductosExport($request->input('buscar')), 'productos_'.now()->format('Ymd_His').'.xlsx');
    }

    public function exportarVentas(Request $request)
    {
        $datos = $request->validate(['desde' => ['nullable', 'date'], 'hasta' => ['nullable', 'date']]);

        return Excel::download(new VentasExport($datos['desde'] ?? null, $datos['hasta'] ?? null), 'ventas_'.now()->format('Ymd_His').'.xlsx');
    }

    public function plantilla()
    {
        return Excel::download(new PlantillaProductosExport(), 'plantilla_productos.xlsx');
    }

    public function formularioImportar()
    {
        return view('productos.importar');
    }

    public function importar(ImportarProductosRequest $request)
    {
        $importacion = new ProductosImport();
        Excel::import($importacion, $request->file('archivo'));

        // Filas omitidas: cada una con su número de fila, columna y mensajes de error
        $fallas = collect($importacion->failures())->map(fn ($f) => [
            'fila' => $f->row(),
            'columna' => $f->attribute(),
            'errores' => $f->errors(),
        ])->values()->all();

        return redirect()->route('productos.importar')
            ->with('success', "Importación terminada: {$importacion->creados} producto(s) creado(s) y {$importacion->actualizados} actualizado(s).")
            ->with('fallas', $fallas);
    }
}
