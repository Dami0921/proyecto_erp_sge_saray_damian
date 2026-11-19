<?php

namespace App\Services;

use App\Events\CompraRecibida;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Producto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompraService
{
    // Clase 12: los lotes nuevos y el stock pasan por el InventarioService (kardex)
    public function __construct(private InventarioService $inventario) {}

    /**
     * Registra una ORDEN DE COMPRA (estado "pendiente"). NO mueve el inventario:
     * el stock solo aumenta cuando la mercancía llega (método recibir).
     *
     * @param  array  $data  ['proveedor_id' => ?int, 'fecha' => 'Y-m-d', 'observaciones' => ?string,
     *                       'items' => [['producto_id' => int, 'cantidad' => int, 'costo_unitario' => float], ...]]
     */
    public function registrar(array $data, int $userId): Compra
    {
        return DB::transaction(function () use ($data, $userId) {
            $lineas = [];
            $subtotal = 0;
            $iva = 0;

            foreach ($data['items'] as $item) {
                $producto = Producto::findOrFail((int) $item['producto_id']);
                $cantidad = (int) $item['cantidad'];
                $costo = (float) $item['costo_unitario'];
                $porcentaje = (float) $producto->iva_porcentaje;
                $lineaSubtotal = round($cantidad * $costo, 2);

                $lineas[] = [
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costo,
                    'iva_porcentaje' => $porcentaje,
                    'subtotal' => $lineaSubtotal,
                ];

                $subtotal += $lineaSubtotal;
                $iva += round($lineaSubtotal * $porcentaje / 100, 2);
            }

            $subtotal = round($subtotal, 2);
            $iva = round($iva, 2);

            $compra = Compra::create([
                'numero_orden' => $this->siguienteNumero(),
                'proveedor_id' => $data['proveedor_id'] ?? null,
                'user_id' => $userId,
                'fecha' => $data['fecha'],
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => round($subtotal + $iva, 2),
                'estado' => Compra::PENDIENTE,
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            foreach ($lineas as $linea) {
                DetalleCompra::create($linea + ['compra_id' => $compra->id]);
            }

            return $compra;
        });
    }

    /**
     * Registra la llegada (total o parcial) de mercancía. Cada línea recibida crea un LOTE NUEVO
     * y aumenta el stock. Todo dentro de una transacción.
     *
     * @param  array  $recepcion  [detalle_id => ['cantidad' => int, 'fecha_vencimiento' => 'Y-m-d', 'numero_lote' => ?string]]
     */
    public function recibir(Compra $compra, array $recepcion): Compra
    {
        return DB::transaction(function () use ($compra, $recepcion) {
            $compra = Compra::lockForUpdate()->findOrFail($compra->id);

            // Regla: solo se recibe en órdenes pendientes o parciales
            if (! $compra->admiteRecepcion()) {
                throw ValidationException::withMessages([
                    'recepcion' => "La orden {$compra->numero_orden} está {$compra->estado} y no admite recepciones.",
                ]);
            }

            // Regla: una recepción debe traer al menos una unidad
            $conCantidad = collect($recepcion)->filter(fn ($l) => (int) ($l['cantidad'] ?? 0) > 0);
            if ($conCantidad->isEmpty()) {
                throw ValidationException::withMessages([
                    'recepcion' => 'Debe ingresar al menos una cantidad mayor que cero.',
                ]);
            }

            foreach ($conCantidad as $detalleId => $linea) {
                $detalle = DetalleCompra::where('compra_id', $compra->id)->lockForUpdate()->find($detalleId);
                if (! $detalle) {
                    throw ValidationException::withMessages(['recepcion' => 'Una de las líneas no pertenece a esta orden.']);
                }

                $nombre = $detalle->producto?->nombre;
                $cantidad = (int) $linea['cantidad'];
                $pendiente = $detalle->pendiente;

                // Regla: no recibir más de lo pedido
                if ($cantidad > $pendiente) {
                    throw ValidationException::withMessages([
                        'recepcion' => "No se puede recibir más de lo pedido para «{$nombre}». Pedido: {$detalle->cantidad}, ya recibido: {$detalle->cantidad_recibida}, pendiente: {$pendiente}.",
                    ]);
                }

                // Regla propia del negocio: no se recibe mercancía vencida (agroquímicos y semillas vencidos no se pueden vender)
                if (empty($linea['fecha_vencimiento'])) {
                    throw ValidationException::withMessages([
                        'recepcion' => "Indique la fecha de vencimiento del lote de «{$nombre}».",
                    ]);
                }
                $vence = Carbon::parse($linea['fecha_vencimiento'])->startOfDay();
                if ($vence->lt(today())) {
                    throw ValidationException::withMessages([
                        'recepcion' => "El lote de «{$nombre}» llega vencido (vence el {$vence->format('d/m/Y')}) y no se puede recibir.",
                    ]);
                }

                // Cada recepción crea un lote nuevo, con el costo de la orden
                $numeroLote = trim((string) ($linea['numero_lote'] ?? ''));
                if ($numeroLote === '') {
                    $numeroLote = 'LC-'.$compra->numero_orden.'-'.$detalle->id.'-'.($detalle->lotes()->count() + 1);
                }

                $this->inventario->ingresarLote([
                    'producto_id' => $detalle->producto_id,
                    'numero_lote' => $numeroLote,
                    'fecha_ingreso' => today(),
                    'fecha_vencimiento' => $vence->toDateString(),
                    'cantidad_inicial' => $cantidad,
                    'cantidad_actual' => $cantidad,
                    'costo_unitario' => $detalle->costo_unitario,
                    'detalle_compra_id' => $detalle->id,
                ], 'compra', $compra, "Recepción de {$compra->numero_orden}");

                $detalle->increment('cantidad_recibida', $cantidad);
            }

            // Estado: recibida si ya llegó todo lo pedido; si no, parcial
            $faltante = $compra->detalles()->whereColumn('cantidad_recibida', '<', 'cantidad')->exists();
            $compra->update([
                'estado' => $faltante ? Compra::PARCIAL : Compra::RECIBIDA,
                'recibida_at' => $faltante ? null : now(),
            ]);

            CompraRecibida::dispatch($compra, ! $faltante);

            return $compra;
        });
    }

    /**
     * Regla: solo se anula una orden SIN mercancía recibida.
     */
    public function anular(Compra $compra): void
    {
        DB::transaction(function () use ($compra) {
            $compra = Compra::withTrashed()->lockForUpdate()->findOrFail($compra->id);

            if ($compra->estaAnulada()) {
                throw ValidationException::withMessages(['compra' => "La orden {$compra->numero_orden} ya fue anulada."]);
            }

            if ($compra->detalles()->where('cantidad_recibida', '>', 0)->exists() || $compra->estado !== Compra::PENDIENTE) {
                throw ValidationException::withMessages([
                    'compra' => "No se puede anular {$compra->numero_orden}: ya tiene mercancía recibida.",
                ]);
            }

            $compra->update(['estado' => Compra::ANULADA]);
            $compra->delete(); // soft delete: la orden queda en la BD con deleted_at
        });
    }

    // OC-00001, OC-00002... (se bloquea la última fila para no repetir número)
    private function siguienteNumero(): string
    {
        $ultimo = Compra::withTrashed()->lockForUpdate()->orderByDesc('id')->value('numero_orden');
        $numero = $ultimo ? ((int) substr($ultimo, 3)) + 1 : 1;

        return 'OC-'.str_pad((string) $numero, 5, '0', STR_PAD_LEFT);
    }
}
