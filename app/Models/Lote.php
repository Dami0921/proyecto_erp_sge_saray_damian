<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    protected $table = 'lotes';

    protected $fillable = [
        'producto_id', 'numero_lote', 'fecha_ingreso',
        'fecha_vencimiento', 'cantidad_inicial', 'cantidad_actual', 'costo_unitario',
        'detalle_compra_id',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_vencimiento' => 'date',
        'costo_unitario' => 'decimal:2',
    ];

    // Lotes que se pueden vender: con existencias y sin vencer
    public function scopeVigentes($query)
    {
        return $query->where('cantidad_actual', '>', 0)
            ->whereDate('fecha_vencimiento', '>=', today());
    }

    // Línea de compra de la que llegó este lote (null si es un lote cargado a mano o por seeder)
    public function detalleCompra()
    {
        return $this->belongsTo(DetalleCompra::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
