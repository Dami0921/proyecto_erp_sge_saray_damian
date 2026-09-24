<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $productos = Producto::with('categoria', 'proveedor')->get();

        return view('productos.index', ['productos' => $productos]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categorias = Categoria::all();
        $proveedores = Proveedor::all();

        return view('productos.create', [
            'categorias' => $categorias,
            'proveedores' => $proveedores,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'unidad_medida' => ['required', 'string', 'max:30'],
        ]);

        Producto::create([
            'nombre' => $request->nombre,
            'categoria_id' => $request->categoria_id,
            'proveedor_id' => $request->proveedor_id,
            'unidad_medida' => $request->unidad_medida,
        ]);

        return redirect()->route('productos.index')->with('status', 'Producto creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $producto = Producto::findOrFail($id);
        $categorias = Categoria::all();
        $proveedores = Proveedor::all();

        return view('productos.edit', [
            'producto' => $producto,
            'categorias' => $categorias,
            'proveedores' => $proveedores,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'unidad_medida' => ['required', 'string', 'max:30'],
        ]);

        $producto = Producto::findOrFail($id);

        $producto->update([
            'nombre' => $request->nombre,
            'categoria_id' => $request->categoria_id,
            'proveedor_id' => $request->proveedor_id,
            'unidad_medida' => $request->unidad_medida,
        ]);

        return redirect()->route('productos.index')->with('status', 'Producto actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $producto = Producto::findOrFail($id);
        $producto->delete();

        return redirect()->route('productos.index')->with('status', 'Producto eliminado.');
    }
}
