<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Categoria;
use App\Models\Modulo;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    // Permiso de Spatie que exige cada acción (si no lo tiene, responde 403)
    public function __construct()
    {
        $this->middleware('permission:ver-productos')->only('index');
        $this->middleware('permission:crear-productos')->only(['create', 'store']);
        $this->middleware('permission:editar-productos')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-productos')->only(['destroy', 'restore']);
    }

    public function index(Request $request)
    {
        // La papelera solo la ve quien puede eliminar
        $verPapelera = $request->boolean('papelera') && $request->user()->can('eliminar-productos');

        $productos = Producto::query()
            ->with('categoria')
            ->conStock()
            ->when(Modulo::activo('proveedores'), fn ($q) => $q->with('proveedor'))
            ->when($verPapelera, fn ($q) => $q->onlyTrashed())
            ->when($request->filled('buscar'), fn ($q) => $q->where('nombre', 'like', '%'.$request->string('buscar').'%'))
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria_id', $request->integer('categoria')))
            ->latest($verPapelera ? 'deleted_at' : 'created_at')
            ->paginate(10)
            ->withQueryString();

        return view('productos.index', [
            'productos' => $productos,
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
            'verPapelera' => $verPapelera,
            'totalPapelera' => Producto::onlyTrashed()->count(),
        ]);
    }

    public function create()
    {
        return view('productos.create', [
            'producto' => new Producto(),
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
            'proveedores' => $this->proveedores(),
        ]);
    }

    public function store(StoreProductoRequest $request)
    {
        Producto::create($request->validated());

        return redirect()->route('productos.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    public function edit(Producto $producto)
    {
        return view('productos.edit', [
            'producto' => $producto,
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
            'proveedores' => $this->proveedores(),
        ]);
    }

    public function update(UpdateProductoRequest $request, Producto $producto)
    {
        $producto->update($request->validated());

        return redirect()->route('productos.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    // Soft delete: el producto va a la papelera (se llena deleted_at)
    public function destroy(Producto $producto)
    {
        $producto->delete();

        return redirect()->route('productos.index')
            ->with('success', "Producto «{$producto->nombre}» enviado a la papelera.");
    }

    public function restore(Producto $producto)
    {
        if ($producto->categoria?->trashed()) {
            return back()->with('error', "Restaura primero la categoría «{$producto->categoria->nombre}».");
        }

        $producto->restore();

        return redirect()->route('productos.index', ['papelera' => 1])
            ->with('success', "Producto «{$producto->nombre}» restaurado.");
    }

    // Sin el módulo Proveedores activo no se consulta esa tabla
    private function proveedores()
    {
        return Modulo::activo('proveedores')
            ? Proveedor::orderBy('nombre')->get(['id', 'nombre'])
            : collect();
    }
}
