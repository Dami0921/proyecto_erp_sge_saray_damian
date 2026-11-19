<?php

namespace App\Services;

use App\Events\VentaRegistrada;
use App\Models\DetalleVenta;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaService
{
    // Clase 12: las existencias SOLO cambian a través del InventarioService (kardex)
    public function __construct(private InventarioService $inventario)
    {
    }

    /**
     * Registra una venta completa dentro de una transacción: si algo falla, no se guarda nada.
     *
     * @param array $data ['cliente_id' => ?int, 'fecha' => 'Y-m-d', 'observaciones' => ?string,
     *                     'items' => [['producto_id' => int, 'cantidad' => int], ...]]
     */
    public function registrar(array $data, int $userId): Venta
    {
        return DB::transaction(function () use ($data, $userId) {
            // 0. Si el mismo producto viene en dos filas, se suman las cantidades
            $cantidades = [];
            foreach ($data['items'] as $item) {
                $id = (int) $item['producto_id'];
                $cantidades[$id] = ($cantidades[$id] ?? 0) + (int) $item['cantidad'];
            }

            // 1. Validar stock y repartir cada producto entre sus lotes (FEFO: primero el que vence antes)
            $lineas = [];
            $subtotal = 0;
            $iva = 0; // cada producto aporta su propio IVA (0 % si no lo lleva)

            foreach ($cantidades as $productoId => $cantidad) {
                $producto = Producto::lockForUpdate()->findOrFail($productoId);
                $precio = (float) $producto->precio_venta;
                $porcentajeIva = (float) $producto->iva_porcentaje;

                if ($precio <= 0) {
                    throw ValidationException::withMessages([
                        'items' => "El producto «{$producto->nombre}» no tiene precio de venta definido.",
                    ]);
                }

                // lockForUpdate: bloquea los lotes hasta terminar (evita vender dos veces el mismo stock)
                // Solo cuentan lotes con existencias y SIN vencer
                $lotes = Lote::where('producto_id', $producto->id)
                    ->vigentes()
                    ->orderBy('fecha_vencimiento')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $disponible = (int) $lotes->sum('cantidad_actual');

                if ($disponible < $cantidad) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente para «{$producto->nombre}». Disponible: {$disponible}, solicitado: {$cantidad}.",
                    ]);
                }

                $restante = $cantidad;
                foreach ($lotes as $lote) {
                    if ($restante <= 0) {
                        break;
                    }
                    $tomar = min((int) $lote->cantidad_actual, $restante);

                    $lineas[] = [
                        'producto_id' => $producto->id,
                        'lote' => $lote,
                        'cantidad' => $tomar,
                        'precio_unitario' => $precio,
                        'iva_porcentaje' => $porcentajeIva,
                        'subtotal' => round($precio * $tomar, 2),
                    ];
                    $restante -= $tomar;
                }

                $subtotal += $precio * $cantidad;
                $iva += round($precio * $cantidad * $porcentajeIva / 100, 2);
            }

            // 2. Totales
            $subtotal = round($subtotal, 2);
            $iva = round($iva, 2);
            $total = round($subtotal + $iva, 2);

            // 3. Cabecera de la venta
            $venta = Venta::create([
                'numero_factura' => $this->siguienteNumero(),
                'cliente_id' => $data['cliente_id'] ?? null,
                'user_id' => $userId,
                'fecha' => $data['fecha'],
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,
                'estado' => Venta::COMPLETADA,
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            // 4. Detalle + descuento de stock en cada lote
            foreach ($lineas as $linea) {
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'lote_id' => $linea['lote']->id,
                    'producto_id' => $linea['producto_id'],
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'iva_porcentaje' => $linea['iva_porcentaje'],
                    'subtotal' => $linea['subtotal'],
                ]);

                $this->inventario->salida($linea['lote'], $linea['cantidad'], 'venta', $venta, $linea['precio_unitario']);
            }

            // Se avisa DESPUÉS del commit (ShouldDispatchAfterCommit): si algo falla antes, no sale ninguna alerta
            VentaRegistrada::dispatch($venta);

            return $venta;
        });
    }

    /**
     * Regla inversa: anular una venta devuelve el stock a los mismos lotes de donde salió.
     * Devuelve la cantidad de unidades reintegradas.
     */
    public function anular(Venta $venta): int
    {
        return DB::transaction(function () use ($venta) {
            $venta = Venta::withTrashed()->lockForUpdate()->findOrFail($venta->id);

            if ($venta->estaAnulada()) {
                throw ValidationException::withMessages([
                    'venta' => "La venta {$venta->numero_factura} ya fue anulada.",
                ]);
            }

            $unidades = 0;
            foreach ($venta->detalles()->get() as $detalle) {
                $this->inventario->entrada(
                    Lote::findOrFail($detalle->lote_id), (int) $detalle->cantidad, 'anulacion_venta', $venta, (float) $detalle->precio_unitario
                );
                $unidades += $detalle->cantidad;
            }

            $venta->update(['estado' => Venta::ANULADA]);
            $venta->delete(); // soft delete: la venta queda en la BD con deleted_at

            return $unidades;
        });
    }

    // CV-00001, CV-00002... (se bloquea la última fila para que dos ventas simultáneas no repitan número)
    private function siguienteNumero(): string
    {
        $ultimo = Venta::withTrashed()->lockForUpdate()->orderByDesc('id')->value('numero_factura');
        $numero = $ultimo ? ((int) substr($ultimo, 3)) + 1 : 1;

        return 'CV-'.str_pad((string) $numero, 5, '0', STR_PAD_LEFT);
    }
}
