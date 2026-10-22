<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleCompra extends Model
{
    protected $table = 'detalle_compras';

    protected $fillable = [
        'compra_id', 'producto_id', 'cantidad', 'cantidad_recibida',
        'costo_unitario', 'iva_porcentaje', 'subtotal',
    ];

    protected $casts = [
        'costo_unitario' => 'decimal:2',
        'iva_porcentaje' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class)->withTrashed();
    }

    // Lotes que nacieron de las recepciones de esta línea
    public function lotes()
    {
        return $this->hasMany(Lote::class, 'detalle_compra_id');
    }

    // Unidades que todavía no han llegado
    public function getPendienteAttribute(): int
    {
        return max(0, (int) $this->cantidad - (int) $this->cantidad_recibida);
    }
}
