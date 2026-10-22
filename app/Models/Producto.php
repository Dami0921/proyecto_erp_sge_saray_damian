<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = ['nombre', 'categoria_id', 'proveedor_id', 'unidad_medida', 'precio_venta', 'iva_porcentaje'];

    protected $casts = ['precio_venta' => 'decimal:2', 'iva_porcentaje' => 'decimal:2'];

    // Aunque la categoría esté en la papelera, el producto sigue mostrándola
    public function categoria()
    {
        return $this->belongsTo(Categoria::class)->withTrashed();
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    // Stock = suma de lotes vigentes. Uso en listados: Producto::conStock()->get() y luego $p->stock
    public function scopeConStock($query)
    {
        return $query->withSum(
            ['lotes as stock' => fn ($q) => $q->whereDate('fecha_vencimiento', '>=', today())],
            'cantidad_actual'
        );
    }

    // Stock de un solo producto (consulta propia)
    public function stockDisponible(): int
    {
        return (int) $this->lotes()->vigentes()->sum('cantidad_actual');
    }

    public function lotes()
    {
        return $this->hasMany(Lote::class);
    }
}
