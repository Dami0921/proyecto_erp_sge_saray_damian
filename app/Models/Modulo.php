<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Modulo extends Model
{
    protected $table = 'modulos';

    protected $fillable = ['clave', 'nombre', 'activo', 'es_nucleo'];

    protected $casts = [
        'activo' => 'boolean',
        'es_nucleo' => 'boolean',
    ];

    private const CACHE_KEY = 'modulos.estado';

    // Un módulo solo funciona si los módulos de los que depende también están activos
    private const DEPENDENCIAS = [
        'ventas' => ['inventario'],   // descuentan stock de los lotes
        'compras' => ['inventario'],  // crean lotes al recibir mercancía
    ];

    // Si cambia cualquier módulo, se descarta la caché para que el cambio se vea de inmediato
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    // Modulo::activo('clientes') -> true/false. El núcleo siempre es true.
    public static function activo(string $clave): bool
    {
        $estado = Cache::rememberForever(self::CACHE_KEY, fn () => static::all()
            ->mapWithKeys(fn (self $m) => [$m->clave => $m->es_nucleo || $m->activo])
            ->all());

        if (! ($estado[$clave] ?? false)) {
            return false;
        }

        foreach (self::DEPENDENCIAS[$clave] ?? [] as $dependencia) {
            if (! static::activo($dependencia)) {
                return false;
            }
        }

        return true;
    }
}
