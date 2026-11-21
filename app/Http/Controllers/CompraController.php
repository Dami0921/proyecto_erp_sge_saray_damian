<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecibirCompraRequest;
use App\Http\Requests\StoreCompraRequest;
use App\Models\Compra;
use App\Models\Lote;
use App\Models\Modulo;
use App\Models\Organizacion;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\CompraService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CompraController extends Controller
{
    // Permiso de Spatie que exige cada acción (si no lo tiene, responde 403)
    public function __construct(private CompraService $service)
    {
        $this->middleware('permission:ver-compras')->only(['index', 'show', 'pdf']);
        $this->middleware('permission:crear-compras')->only(['create', 'store']);
        $this->middleware('permission:recibir-compras')->only('recibir');
        $this->middleware('permission:eliminar-compras')->only('destroy'); // anular
    }

    public function index(Request $request)
    {
        $compras = Compra::withTrashed()
            ->with(['proveedor', 'usuario'])
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->when($request->filled('buscar'), fn ($q) => $q->where('numero_orden', 'like', '%'.$request->string('buscar').'%'))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('compras.index', ['compras' => $compras]);
    }

    public function create()
    {
        $productos = Producto::orderBy('nombre')->get()->map(function (Producto $p) {
            // Costo sugerido: el del último lote recibido de este producto
            $ultimoCosto = Lote::where('producto_id', $p->id)->latest('id')->value('costo_unitario');

            return [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'unidad' => $p->unidad_medida,
                'costo' => (float) ($ultimoCosto ?? 0),
                'iva' => (float) $p->iva_porcentaje,
            ];
        });

        return view('compras.create', [
            'productos' => $productos,
            // Sin el módulo Proveedores activo no se consulta esa tabla
            'proveedores' => Modulo::activo('proveedores') ? Proveedor::orderBy('nombre')->get(['id', 'nombre', 'nit']) : collect(),
        ]);
    }

    public function store(StoreCompraRequest $request)
    {
        $compra = $this->service->registrar($request->validated(), $request->user()->id);

        return redirect()->route('compras.show', $compra)
            ->with('success', "Orden {$compra->numero_orden} registrada. El stock aumentará cuando se reciba la mercancía.");
    }

    public function show(Compra $compra)
    {
        $compra->load(['proveedor', 'usuario', 'detalles.producto', 'detalles.lotes']);

        return view('compras.show', ['compra' => $compra]);
    }

    // Registra la llegada (total o parcial) de mercancía: crea lotes y aumenta el stock
    public function recibir(RecibirCompraRequest $request, Compra $compra)
    {
        // Si una regla de negocio falla, el servicio lanza ValidationException y se vuelve al detalle con el error
        $compra = $this->service->recibir($compra, $request->validated()['recepcion']);

        $mensaje = $compra->estado === Compra::RECIBIDA
            ? "Orden {$compra->numero_orden} recibida por completo. El stock fue actualizado."
            : "Recepción parcial registrada en {$compra->numero_orden}. El stock fue actualizado; aún hay mercancía pendiente.";

        return redirect()->route('compras.show', $compra)->with('success', $mensaje);
    }

    // "Eliminar" una compra = anularla (solo si no se ha recibido nada)
    public function destroy(Compra $compra)
    {
        try {
            $this->service->anular($compra);
        } catch (ValidationException $e) {
            return redirect()->route('compras.index')->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('compras.index')
            ->with('success', "Orden {$compra->numero_orden} anulada.");
    }

    // Orden de compra en PDF
    public function pdf(Compra $compra)
    {
        $compra->load(['proveedor', 'usuario', 'detalles.producto']);

        return Pdf::loadView('pdf.orden_compra', ['compra' => $compra, 'empresa' => Organizacion::actual()->paraPdf()])
            ->setPaper('letter')
            ->stream("orden-{$compra->numero_orden}.pdf");
    }
}
