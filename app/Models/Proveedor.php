<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Proveedor extends Model
{
    use SoftDeletes;

    protected $table = 'proveedores';

    protected $fillable = ['nombre', 'nit', 'telefono', 'direccion'];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    // Regla de negocio: un proveedor con compras (incluso anuladas) no se puede eliminar.
    // Schema::hasTable permite que Proveedores funcione aunque el módulo Compras no exista.
    public function tieneCompras(): bool
    {
        return Schema::hasTable('compras') && $this->compras()->withTrashed()->exists();
    }
}
