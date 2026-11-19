<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Cliente extends Model
{
    use Auditable, SoftDeletes;

    // Auditoría (Clase 12)
    protected string $auditLog = 'clientes';

    public const TIPOS = ['Natural', 'Empresa', 'Cooperativa', 'Finca'];

    protected $table = 'clientes';

    protected $fillable = ['nombre', 'tipo_cliente', 'documento', 'telefono', 'municipio'];

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    // Regla de negocio: un cliente con ventas (incluso anuladas) no se puede eliminar.
    // Schema::hasTable permite que Clientes funcione aunque el módulo Ventas no exista.
    public function tieneVentas(): bool
    {
        return Schema::hasTable('ventas') && $this->ventas()->withTrashed()->exists();
    }
}
