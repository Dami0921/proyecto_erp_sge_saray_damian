<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una fila del kardex: cada entrada o salida de mercancía, con el saldo resultante.
 * Solo la crea InventarioService; nunca se edita ni se elimina desde la aplicación.
 */
class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    // Etiquetas para mostrar el concepto en pantalla
    public const CONCEPTOS = [
        'inventario_inicial' => 'Inventario inicial',
        'compra' => 'Compra (recepción)',
        'venta' => 'Venta',
        'anulacion_venta' => 'Anulación de venta',
        'ajuste' => 'Ajuste de inventario',
        'baja_vencimiento' => 'Baja por vencimiento',
    ];

    protected $fillable = [
        'producto_id', 'lote_id', 'tipo', 'concepto', 'cantidad', 'saldo',
        'costo_unitario', 'referencia_type', 'referencia_id', 'user_id', 'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'saldo' => 'integer',
        'costo_unitario' => 'decimal:2',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class)->withTrashed();
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    // Documento que originó el movimiento: una venta, una compra o nada (ajuste / inventario inicial).
    // withTrashed: una venta anulada queda en la papelera, pero su número debe seguir en el kardex.
    public function referencia()
    {
        return $this->morphTo('referencia')->withTrashed();
    }

    public function conceptoEtiqueta(): string
    {
        return self::CONCEPTOS[$this->concepto] ?? $this->concepto;
    }

    // Texto corto del documento: CV-00001, OC-00001...
    public function documento(): ?string
    {
        return $this->referencia?->numero_factura ?? $this->referencia?->numero_orden;
    }
}
