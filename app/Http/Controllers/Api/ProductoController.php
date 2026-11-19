<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-productos');
    }

    // GET /api/v1/productos?buscar=urea&categoria=1&per_page=20
    public function index(Request $request)
    {
        $productos = Producto::query()
            ->with('categoria')
            ->conStock()
            ->when($request->filled('buscar'), fn ($q) => $q->where('nombre', 'like', '%'.$request->string('buscar').'%'))
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria_id', $request->integer('categoria')))
            ->orderBy('nombre')
            ->paginate(min($request->integer('per_page', 15), 100));

        return ProductoResource::collection($productos);
    }

    // GET /api/v1/productos/{id}
    public function show(Producto $producto)
    {
        return new ProductoResource($producto->load('categoria'));
    }
}
