<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    protected $table = 'detalle_ventas';

    protected $fillable = ['venta_id', 'lote_id', 'producto_id', 'cantidad', 'precio_unitario', 'iva_porcentaje', 'subtotal'];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'iva_porcentaje' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class)->withTrashed();
    }
}
