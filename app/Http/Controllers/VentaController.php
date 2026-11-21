<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVentaRequest;
use App\Models\Cliente;
use App\Models\Modulo;
use App\Models\Organizacion;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\VentaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    // Permiso de Spatie que exige cada acción (si no lo tiene, responde 403)
    public function __construct(private VentaService $service)
    {
        $this->middleware('permission:ver-ventas')->only(['index', 'show', 'pdf']);
        $this->middleware('permission:crear-ventas')->only(['create', 'store']);
        $this->middleware('permission:eliminar-ventas')->only('destroy'); // anular
    }

    public function index(Request $request)
    {
        $ventas = Venta::withTrashed()
            ->with(['cliente', 'usuario'])
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->when($request->filled('buscar'), fn ($q) => $q->where('numero_factura', 'like', '%'.$request->string('buscar').'%'))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha', '>=', $request->input('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha', '<=', $request->input('hasta')))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('ventas.index', ['ventas' => $ventas]);
    }

    public function create()
    {
        // Solo productos con stock vigente (lotes sin vencer) y con precio
        $productos = Producto::conStock()
            ->where('precio_venta', '>', 0)
            ->orderBy('nombre')
            ->get()
            ->filter(fn ($p) => (int) $p->stock > 0)
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'unidad' => $p->unidad_medida,
                'precio' => (float) $p->precio_venta,
                'iva' => (float) $p->iva_porcentaje,
                'stock' => (int) $p->stock,
            ])
            ->values();

        return view('ventas.create', [
            'productos' => $productos,
            // Sin el módulo Clientes activo no se consulta esa tabla
            'clientes' => Modulo::activo('clientes') ? Cliente::orderBy('nombre')->get(['id', 'nombre', 'documento']) : collect(),
        ]);
    }

    public function store(StoreVentaRequest $request)
    {
        // Si el stock no alcanza, el servicio lanza ValidationException y se vuelve al formulario con el error
        $venta = $this->service->registrar($request->validated(), $request->user()->id);

        return redirect()->route('ventas.show', $venta)
            ->with('success', "Venta {$venta->numero_factura} registrada. El stock de los lotes fue descontado.");
    }

    public function show(Venta $venta)
    {
        $venta->load(['cliente', 'usuario', 'detalles.producto', 'detalles.lote']);

        return view('ventas.show', ['venta' => $venta]);
    }

    // "Eliminar" una venta = anularla: devuelve el stock y deja la venta con soft delete
    public function destroy(Venta $venta)
    {
        try {
            $unidades = $this->service->anular($venta);
        } catch (ValidationException $e) {
            return redirect()->route('ventas.index')->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('ventas.index')
            ->with('success', "Venta {$venta->numero_factura} anulada. Stock devuelto a los lotes: {$unidades} unidades.");
    }

    // Comprobante de venta en PDF (la ven quienes pueden ver ventas; el almacenista recibe 403)
    public function pdf(Venta $venta)
    {
        $venta->load(['cliente', 'usuario', 'detalles.producto', 'detalles.lote']);

        return Pdf::loadView('pdf.comprobante_venta', ['venta' => $venta, 'empresa' => Organizacion::actual()->paraPdf()])
            ->setPaper('letter')
            ->stream("comprobante-{$venta->numero_factura}.pdf");
    }
}
