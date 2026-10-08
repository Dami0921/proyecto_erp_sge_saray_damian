<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = ['nombre', 'categoria_id', 'proveedor_id', 'unidad_medida'];

    // Aunque la categoría esté en la papelera, el producto sigue mostrándola
    public function categoria()
    {
        return $this->belongsTo(Categoria::class)->withTrashed();
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function lotes()
    {
        return $this->hasMany(Lote::class);
    }
}
