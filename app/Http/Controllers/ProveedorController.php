<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProveedorRequest;
use App\Http\Requests\UpdateProveedorRequest;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    // Permiso de Spatie que exige cada acción (si no lo tiene, responde 403)
    public function __construct()
    {
        $this->middleware('permission:ver-proveedores')->only('index');
        $this->middleware('permission:crear-proveedores')->only(['create', 'store']);
        $this->middleware('permission:editar-proveedores')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-proveedores')->only(['destroy', 'restore']);
    }

    public function index(Request $request)
    {
        // La papelera solo la ve quien puede eliminar
        $verPapelera = $request->boolean('papelera') && $request->user()->can('eliminar-proveedores');

        $proveedores = Proveedor::query()
            ->when($verPapelera, fn ($q) => $q->onlyTrashed())
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $texto = '%'.$request->string('buscar').'%';
                $q->where(fn ($w) => $w->where('nombre', 'like', $texto)->orWhere('nit', 'like', $texto));
            })
            ->latest($verPapelera ? 'deleted_at' : 'created_at')
            ->paginate(10)
            ->withQueryString();

        return view('proveedores.index', [
            'proveedores' => $proveedores,
            'verPapelera' => $verPapelera,
            'totalPapelera' => Proveedor::onlyTrashed()->count(),
        ]);
    }

    public function create()
    {
        return view('proveedores.create', ['proveedor' => new Proveedor()]);
    }

    public function store(StoreProveedorRequest $request)
    {
        Proveedor::create($request->validated());

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor creado exitosamente.');
    }

    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.edit', ['proveedor' => $proveedor]);
    }

    public function update(UpdateProveedorRequest $request, Proveedor $proveedor)
    {
        $proveedor->update($request->validated());

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor actualizado exitosamente.');
    }

    // Soft delete, salvo que el proveedor tenga compras asociadas
    public function destroy(Proveedor $proveedor)
    {
        if ($proveedor->tieneCompras()) {
            return redirect()->route('proveedores.index')
                ->with('error', "No se puede eliminar «{$proveedor->nombre}»: tiene compras asociadas.");
        }

        $proveedor->delete();

        return redirect()->route('proveedores.index')
            ->with('success', "Proveedor «{$proveedor->nombre}» enviado a la papelera.");
    }

    public function restore(Proveedor $proveedor)
    {
        $proveedor->restore();

        return redirect()->route('proveedores.index', ['papelera' => 1])
            ->with('success', "Proveedor «{$proveedor->nombre}» restaurado.");
    }
}
