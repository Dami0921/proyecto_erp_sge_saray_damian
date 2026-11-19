<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    // Permiso de Spatie que exige cada acción (si no lo tiene, responde 403)
    public function __construct()
    {
        $this->middleware('permission:ver-categorias')->only('index');
        $this->middleware('permission:crear-categorias')->only(['create', 'store']);
        $this->middleware('permission:editar-categorias')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-categorias')->only(['destroy', 'restore']);
    }

    public function index(Request $request)
    {
        $verPapelera = $request->boolean('papelera') && $request->user()->can('eliminar-categorias');

        $categorias = Categoria::query()
            ->withCount('productos')
            ->when($verPapelera, fn ($q) => $q->onlyTrashed())
            ->when($request->filled('buscar'), fn ($q) => $q->where('nombre', 'like', '%'.$request->string('buscar').'%'))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('categorias.index', [
            'categorias' => $categorias,
            'verPapelera' => $verPapelera,
            'totalPapelera' => Categoria::onlyTrashed()->count(),
        ]);
    }

    public function create()
    {
        return view('categorias.create', ['categoria' => new Categoria]);
    }

    public function store(StoreCategoriaRequest $request)
    {
        Categoria::create($request->validated());

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría creada exitosamente.');
    }

    public function edit(Categoria $categoria)
    {
        return view('categorias.edit', ['categoria' => $categoria]);
    }

    public function update(UpdateCategoriaRequest $request, Categoria $categoria)
    {
        $categoria->update($request->validated());

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(Categoria $categoria)
    {
        // No se permite eliminar una categoría que todavía tiene productos
        if ($categoria->productos()->exists()) {
            return back()->with('error', "No se puede eliminar «{$categoria->nombre}»: tiene productos asociados.");
        }

        $categoria->delete();

        return redirect()->route('categorias.index')
            ->with('success', "Categoría «{$categoria->nombre}» enviada a la papelera.");
    }

    public function restore(Categoria $categoria)
    {
        $categoria->restore();

        return redirect()->route('categorias.index', ['papelera' => 1])
            ->with('success', "Categoría «{$categoria->nombre}» restaurada.");
    }
}
