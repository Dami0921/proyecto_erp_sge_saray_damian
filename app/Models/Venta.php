<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model
{
    use SoftDeletes;

    public const COMPLETADA = 'completada';
    public const ANULADA = 'anulada';

    protected $table = 'ventas';

    protected $fillable = [
        'numero_factura', 'cliente_id', 'user_id', 'fecha',
        'subtotal', 'iva', 'total', 'estado', 'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // withTrashed: el comprobante sigue mostrando al cliente aunque luego lo envíen a la papelera
    public function cliente()
    {
        return $this->belongsTo(Cliente::class)->withTrashed();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function estaAnulada(): bool
    {
        return $this->estado === self::ANULADA;
    }
}
