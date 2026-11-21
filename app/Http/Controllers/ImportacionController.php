<?php

namespace App\Http\Controllers;

use App\Exports\PlantillaClientesExport;
use App\Exports\PlantillaProveedoresExport;
use App\Http\Requests\ImportarArchivoRequest;
use App\Imports\ClientesImport;
use App\Imports\ProveedoresImport;
use Maatwebsite\Excel\Facades\Excel;

/** Cargas masivas desde Excel/CSV de clientes y proveedores (la de productos está en ExcelController). */
class ImportacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:importar-clientes')->only(['clientes', 'clientesPlantilla', 'clientesGuardar']);
        $this->middleware('permission:importar-proveedores')->only(['proveedores', 'proveedoresPlantilla', 'proveedoresGuardar']);
    }

    // ---------------------------------------------------------------- Clientes

    public function clientes()
    {
        return view('importacion.formulario', [
            'titulo' => 'Importar clientes desde Excel',
            'plantilla' => route('clientes.plantilla'),
            'guardar' => route('clientes.importar.guardar'),
            'volver' => route('clientes.index'),
            'volverTexto' => 'Volver a clientes',
            'columnas' => [
                'nombre' => 'obligatorio',
                'tipo_cliente' => 'obligatorio: Natural, Empresa, Cooperativa o Finca',
                'documento' => 'obligatorio: identifica al cliente',
                'telefono' => 'opcional',
                'municipio' => 'opcional',
            ],
            'criterio' => 'Si ya existe un cliente con ese documento, se actualizan su nombre, tipo, teléfono y municipio; si no, se crea.',
        ]);
    }

    public function clientesPlantilla()
    {
        return Excel::download(new PlantillaClientesExport, 'plantilla_clientes.xlsx');
    }

    public function clientesGuardar(ImportarArchivoRequest $request)
    {
        $importacion = new ClientesImport;
        Excel::import($importacion, $request->file('archivo'));

        return $this->resultado($importacion, 'clientes.importar', 'cliente');
    }

    // ---------------------------------------------------------------- Proveedores

    public function proveedores()
    {
        return view('importacion.formulario', [
            'titulo' => 'Importar proveedores desde Excel',
            'plantilla' => route('proveedores.plantilla'),
            'guardar' => route('proveedores.importar.guardar'),
            'volver' => route('proveedores.index'),
            'volverTexto' => 'Volver a proveedores',
            'columnas' => [
                'nombre' => 'obligatorio',
                'nit' => 'obligatorio: identifica al proveedor',
                'telefono' => 'opcional',
                'direccion' => 'opcional',
            ],
            'criterio' => 'Si ya existe un proveedor con ese NIT, se actualizan su nombre, teléfono y dirección; si no, se crea.',
        ]);
    }

    public function proveedoresPlantilla()
    {
        return Excel::download(new PlantillaProveedoresExport, 'plantilla_proveedores.xlsx');
    }

    public function proveedoresGuardar(ImportarArchivoRequest $request)
    {
        $importacion = new ProveedoresImport;
        Excel::import($importacion, $request->file('archivo'));

        return $this->resultado($importacion, 'proveedores.importar', 'proveedor');
    }

    // ---------------------------------------------------------------- Común

    // Filas omitidas: cada una con su número de fila, columna y mensajes de error
    private function resultado(ClientesImport|ProveedoresImport $importacion, string $ruta, string $nombre)
    {
        $fallas = collect($importacion->failures())->map(fn ($f) => [
            'fila' => $f->row(),
            'columna' => $f->attribute(),
            'errores' => $f->errors(),
        ])->values()->all();

        return redirect()->route($ruta)
            ->with('success', "Importación terminada: {$importacion->creados} {$nombre}(s) creado(s) y {$importacion->actualizados} actualizado(s).")
            ->with('fallas', $fallas);
    }
}
