<?php

namespace App\Services;

use App\Models\Lote;
use App\Models\MovimientoInventario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ÚNICO punto del ERP que modifica las existencias (lotes.cantidad_actual).
 * Cada cambio deja un movimiento en el kardex con el saldo resultante, quién lo hizo
 * y el documento que lo originó. Ventas, anulaciones, compras y ajustes pasan por aquí.
 *
 * El saldo del kardex es el de TODO el producto (suma de sus lotes), no solo del lote movido.
 */
class InventarioService
{
    /**
     * Nace un lote nuevo con su cantidad (recepción de una compra, inventario inicial) y queda en el kardex.
     *
     * @param array $datosLote campos de Lote; 'cantidad_actual' debe ser la cantidad que ingresa
     */
    public function ingresarLote(array $datosLote, string $concepto, ?Model $referencia = null, ?string $observaciones = null): MovimientoInventario
    {
        return DB::transaction(function () use ($datosLote, $concepto, $referencia, $observaciones) {
            $lote = Lote::create($datosLote);

            return $this->registrar(
                $lote, 'entrada', (int) $lote->cantidad_actual, $concepto,
                $referencia, (float) $lote->costo_unitario, $observaciones
            );
        });
    }

    /** Entrada sobre un lote que ya existe (por ejemplo, la devolución de una venta anulada). */
    public function entrada(Lote $lote, int $cantidad, string $concepto, ?Model $referencia = null, ?float $costo = null, ?string $observaciones = null): MovimientoInventario
    {
        return $this->mover($lote, 'entrada', $cantidad, $concepto, $referencia, $costo, $observaciones);
    }

    /** Salida de un lote (venta). Nunca deja el lote en negativo. */
    public function salida(Lote $lote, int $cantidad, string $concepto, ?Model $referencia = null, ?float $costo = null, ?string $observaciones = null): MovimientoInventario
    {
        return $this->mover($lote, 'salida', $cantidad, $concepto, $referencia, $costo, $observaciones);
    }

    /**
     * Ajuste por conteo físico: lleva el lote a la cantidad contada y registra solo la diferencia.
     * Devuelve null si el conteo coincide con el sistema.
     */
    public function ajustar(Lote $lote, int $cantidadContada, string $motivo): ?MovimientoInventario
    {
        return DB::transaction(function () use ($lote, $cantidadContada, $motivo) {
            $lote = Lote::lockForUpdate()->findOrFail($lote->id);
            $diferencia = $cantidadContada - (int) $lote->cantidad_actual;

            if ($diferencia === 0) {
                return null;
            }

            return $this->mover(
                $lote, $diferencia > 0 ? 'entrada' : 'salida', abs($diferencia), 'ajuste', null, null, $motivo
            );
        });
    }

    /** Baja de un lote vencido: saca del inventario todo lo que queda en él. */
    public function darDeBaja(Lote $lote, ?string $motivo = null): ?MovimientoInventario
    {
        return DB::transaction(function () use ($lote, $motivo) {
            $lote = Lote::lockForUpdate()->findOrFail($lote->id);

            if ((int) $lote->cantidad_actual <= 0) {
                return null;
            }

            return $this->mover(
                $lote, 'salida', (int) $lote->cantidad_actual, 'baja_vencimiento', null, (float) $lote->costo_unitario,
                $motivo ?? "Lote {$lote->numero_lote} vencido el {$lote->fecha_vencimiento->format('d/m/Y')}"
            );
        });
    }

    /**
     * Registra como «inventario inicial» los lotes que existían antes del kardex (o cargados a mano).
     * Es acumulativo: el saldo de cada movimiento suma los lotes ya registrados del producto.
     * Devuelve cuántos lotes se registraron.
     */
    public function abrirInventario(): int
    {
        $total = 0;

        DB::transaction(function () use (&$total) {
            $lotes = Lote::whereDoesntHave('movimientos')
                ->where('cantidad_actual', '>', 0)
                ->orderBy('producto_id')->orderBy('id')
                ->lockForUpdate()
                ->get();

            $acumulado = [];
            foreach ($lotes as $lote) {
                $acumulado[$lote->producto_id] ??= (int) (MovimientoInventario::where('producto_id', $lote->producto_id)
                    ->orderByDesc('id')->value('saldo') ?? 0);
                $acumulado[$lote->producto_id] += (int) $lote->cantidad_actual;

                MovimientoInventario::create([
                    'producto_id' => $lote->producto_id,
                    'lote_id' => $lote->id,
                    'tipo' => 'entrada',
                    'concepto' => 'inventario_inicial',
                    'cantidad' => (int) $lote->cantidad_actual,
                    'saldo' => $acumulado[$lote->producto_id],
                    'costo_unitario' => $lote->costo_unitario,
                    'user_id' => auth()->id(),
                    'observaciones' => 'Saldo inicial del lote '.$lote->numero_lote,
                ]);
                $total++;
            }
        });

        return $total;
    }

    // ------------------------------------------------------------------ Internos

    private function mover(Lote $lote, string $tipo, int $cantidad, string $concepto, ?Model $referencia, ?float $costo, ?string $observaciones): MovimientoInventario
    {
        if ($cantidad <= 0) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad del movimiento debe ser mayor que cero.']);
        }

        return DB::transaction(function () use ($lote, $tipo, $cantidad, $concepto, $referencia, $costo, $observaciones) {
            // Se vuelve a leer y bloquear la fila: el cálculo parte siempre del valor real en la BD
            $lote = Lote::lockForUpdate()->findOrFail($lote->id);

            $nueva = $tipo === 'entrada'
                ? (int) $lote->cantidad_actual + $cantidad
                : (int) $lote->cantidad_actual - $cantidad;

            if ($nueva < 0) {
                throw ValidationException::withMessages([
                    'items' => "Existencias insuficientes en el lote {$lote->numero_lote}. Disponible: {$lote->cantidad_actual}, solicitado: {$cantidad}.",
                ]);
            }

            $lote->update(['cantidad_actual' => $nueva]);

            return $this->registrar($lote, $tipo, $cantidad, $concepto, $referencia, $costo, $observaciones);
        });
    }

    // Crea la fila del kardex. El saldo es la suma ACTUAL de los lotes del producto (ya con el cambio aplicado).
    private function registrar(Lote $lote, string $tipo, int $cantidad, string $concepto, ?Model $referencia, ?float $costo, ?string $observaciones): MovimientoInventario
    {
        $saldo = (int) Lote::where('producto_id', $lote->producto_id)->sum('cantidad_actual');

        return MovimientoInventario::create([
            'producto_id' => $lote->producto_id,
            'lote_id' => $lote->id,
            'tipo' => $tipo,
            'concepto' => $concepto,
            'cantidad' => $cantidad,
            'saldo' => $saldo,
            'costo_unitario' => $costo,
            'referencia_type' => $referencia?->getMorphClass(),
            'referencia_id' => $referencia?->getKey(),
            'user_id' => auth()->id(),
            'observaciones' => $observaciones,
        ]);
    }
}
