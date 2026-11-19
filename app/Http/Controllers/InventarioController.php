<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAjusteRequest;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InventarioController extends Controller
{
    // Permiso de Spatie que exige cada acción (si no lo tiene, responde 403)
    public function __construct(private InventarioService $inventario)
    {
        $this->middleware('permission:ver-kardex')->only('kardex');
        $this->middleware('permission:ajustar-kardex')->only(['ajustar', 'guardarAjuste']);
    }

    /** Kardex: entradas, salidas y saldo de un producto, con filtros por fechas y concepto. */
    public function kardex(Request $request)
    {
        $productos = Producto::withTrashed()->orderBy('nombre')->get(['id', 'nombre', 'unidad_medida', 'deleted_at']);
        $producto = $request->filled('producto') ? $productos->firstWhere('id', $request->integer('producto')) : null;

        return view('inventario.kardex', [
            'productos' => $productos,
            'producto' => $producto,
            'movimientos' => $producto ? $this->consulta($request, $producto)->paginate(20)->withQueryString() : null,
            'totales' => $producto ? $this->totales($request, $producto) : null,
            'lotes' => $producto ? Lote::where('producto_id', $producto->id)->orderBy('fecha_vencimiento')->get() : collect(),
            'conceptos' => MovimientoInventario::CONCEPTOS,
        ]);
    }

    public function ajustar(Producto $producto)
    {
        return view('inventario.ajustar', [
            'producto' => $producto,
            'lotes' => Lote::where('producto_id', $producto->id)->orderBy('fecha_vencimiento')->get(),
            'ultimos' => $producto->movimientos()->with('usuario')->latest('id')->limit(5)->get(),
        ]);
    }

    public function guardarAjuste(StoreAjusteRequest $request, Producto $producto)
    {
        $lote = Lote::where('producto_id', $producto->id)->findOrFail($request->integer('lote_id'));

        try {
            $movimiento = $this->inventario->ajustar($lote, $request->integer('cantidad_contada'), $request->validated('motivo'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        if (! $movimiento) {
            return back()->with('error', 'El conteo coincide con las existencias del sistema: no se registró ningún ajuste.');
        }

        return redirect()->route('inventario.kardex', ['producto' => $producto->id])
            ->with('success', "Ajuste registrado: {$movimiento->tipo} de {$movimiento->cantidad} unidad(es) en el lote {$lote->numero_lote}. Nuevo saldo del producto: {$movimiento->saldo}.");
    }

    private function consulta(Request $request, Producto $producto)
    {
        return $producto->movimientos()
            ->with(['usuario:id,name', 'lote:id,numero_lote', 'referencia'])
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('hasta')))
            ->when($request->filled('concepto'), fn ($q) => $q->where('concepto', $request->input('concepto')))
            ->orderBy('id');
    }

    private function totales(Request $request, Producto $producto): array
    {
        $consulta = $this->consulta($request, $producto)->reorder();
        $ultimoSaldo = $producto->movimientos()->latest('id')->value('saldo');
        $existencias = (int) Lote::where('producto_id', $producto->id)->sum('cantidad_actual');

        return [
            'entradas' => (int) (clone $consulta)->where('tipo', 'entrada')->sum('cantidad'),
            'salidas' => (int) (clone $consulta)->where('tipo', 'salida')->sum('cantidad'),
            'existencias' => $existencias,
            // Regla de control: el último saldo del kardex debe coincidir con la suma de los lotes
            'cuadra' => $ultimoSaldo === null ? $existencias === 0 : (int) $ultimoSaldo === $existencias,
        ];
    }
}
