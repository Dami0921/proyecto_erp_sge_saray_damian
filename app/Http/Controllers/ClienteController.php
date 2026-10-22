<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    // Permiso de Spatie que exige cada acción (si no lo tiene, responde 403)
    public function __construct()
    {
        $this->middleware('permission:ver-clientes')->only('index');
        $this->middleware('permission:crear-clientes')->only(['create', 'store']);
        $this->middleware('permission:editar-clientes')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-clientes')->only(['destroy', 'restore']);
    }

    public function index(Request $request)
    {
        // La papelera solo la ve quien puede eliminar
        $verPapelera = $request->boolean('papelera') && $request->user()->can('eliminar-clientes');

        $clientes = Cliente::query()
            ->when($verPapelera, fn ($q) => $q->onlyTrashed())
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $texto = '%'.$request->string('buscar').'%';
                $q->where(fn ($w) => $w->where('nombre', 'like', $texto)->orWhere('documento', 'like', $texto));
            })
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo_cliente', $request->string('tipo')))
            ->latest($verPapelera ? 'deleted_at' : 'created_at')
            ->paginate(10)
            ->withQueryString();

        return view('clientes.index', [
            'clientes' => $clientes,
            'tipos' => Cliente::TIPOS,
            'verPapelera' => $verPapelera,
            'totalPapelera' => Cliente::onlyTrashed()->count(),
        ]);
    }

    public function create()
    {
        return view('clientes.create', [
            'cliente' => new Cliente(),
            'tipos' => Cliente::TIPOS,
        ]);
    }

    public function store(StoreClienteRequest $request)
    {
        Cliente::create($request->validated());

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente creado exitosamente.');
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', [
            'cliente' => $cliente,
            'tipos' => Cliente::TIPOS,
        ]);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    // Soft delete, salvo que el cliente tenga ventas asociadas
    public function destroy(Cliente $cliente)
    {
        if ($cliente->tieneVentas()) {
            return redirect()->route('clientes.index')
                ->with('error', "No se puede eliminar «{$cliente->nombre}»: tiene ventas asociadas.");
        }

        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('success', "Cliente «{$cliente->nombre}» enviado a la papelera.");
    }

    public function restore(Cliente $cliente)
    {
        $cliente->restore();

        return redirect()->route('clientes.index', ['papelera' => 1])
            ->with('success', "Cliente «{$cliente->nombre}» restaurado.");
    }
}
