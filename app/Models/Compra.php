<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compra extends Model
{
    use Auditable, SoftDeletes;

    // Auditoría (Clase 12)
    protected string $auditLog = 'compras';

    public const PENDIENTE = 'pendiente';
    public const PARCIAL = 'parcial';
    public const RECIBIDA = 'recibida';
    public const ANULADA = 'anulada';

    protected $table = 'compras';

    protected $fillable = [
        'numero_orden', 'proveedor_id', 'user_id', 'fecha',
        'subtotal', 'iva', 'total', 'estado', 'observaciones', 'recibida_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'recibida_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // withTrashed: la orden sigue mostrando al proveedor aunque luego lo envíen a la papelera
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class)->withTrashed();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class);
    }

    public function estaAnulada(): bool
    {
        return $this->estado === self::ANULADA;
    }

    // Una orden admite recepciones mientras esté pendiente o parcial
    public function admiteRecepcion(): bool
    {
        return in_array($this->estado, [self::PENDIENTE, self::PARCIAL], true);
    }
}
