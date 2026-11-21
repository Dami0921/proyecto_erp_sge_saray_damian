<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Identidad corporativa de la empresa que usa el ERP (nombre, NIT, logo, color...).
 * Es una tabla de UNA fila: el mismo código sirve a cualquier empresa cambiando estos datos.
 */
class Organizacion extends Model
{
    use Auditable;

    protected $table = 'organizacion';

    protected $fillable = ['nombre', 'nit', 'direccion', 'telefono', 'email', 'eslogan', 'descripcion', 'color', 'logo'];

    protected string $auditLog = 'organizacion';

    // El logo pesa mucho para la bitácora: su cambio se registra aparte («logo actualizado»)
    protected array $auditExcept = ['logo'];

    private const CACHE_KEY = 'organizacion.actual';

    /** Paletas disponibles: el «verde» es el diseño original y no necesita ajustes. */
    public const PALETAS = [
        'verde' => ['nombre' => 'Verde', 'tonos' => [
            '50' => '#f0fdf4', '100' => '#dcfce7', '200' => '#bbf7d0', '400' => '#4ade80', '500' => '#22c55e',
            '600' => '#16a34a', '700' => '#15803d', '800' => '#166534', '900' => '#14532d',
        ]],
        'azul' => ['nombre' => 'Azul', 'tonos' => [
            '50' => '#eff6ff', '100' => '#dbeafe', '200' => '#bfdbfe', '400' => '#60a5fa', '500' => '#3b82f6',
            '600' => '#2563eb', '700' => '#1d4ed8', '800' => '#1e40af', '900' => '#1e3a8a',
        ]],
        'turquesa' => ['nombre' => 'Turquesa', 'tonos' => [
            '50' => '#f0fdfa', '100' => '#ccfbf1', '200' => '#99f6e4', '400' => '#2dd4bf', '500' => '#14b8a6',
            '600' => '#0d9488', '700' => '#0f766e', '800' => '#115e59', '900' => '#134e4a',
        ]],
        'naranja' => ['nombre' => 'Naranja', 'tonos' => [
            '50' => '#fff7ed', '100' => '#ffedd5', '200' => '#fed7aa', '400' => '#fb923c', '500' => '#f97316',
            '600' => '#ea580c', '700' => '#c2410c', '800' => '#9a3412', '900' => '#7c2d12',
        ]],
        'morado' => ['nombre' => 'Morado', 'tonos' => [
            '50' => '#faf5ff', '100' => '#f3e8ff', '200' => '#e9d5ff', '400' => '#c084fc', '500' => '#a855f7',
            '600' => '#9333ea', '700' => '#7e22ce', '800' => '#6b21a8', '900' => '#581c87',
        ]],
        'gris' => ['nombre' => 'Gris azulado', 'tonos' => [
            '50' => '#f8fafc', '100' => '#f1f5f9', '200' => '#e2e8f0', '400' => '#94a3b8', '500' => '#64748b',
            '600' => '#475569', '700' => '#334155', '800' => '#1e293b', '900' => '#0f172a',
        ]],
    ];

    // Si cambia la identidad, se descarta la caché para que se vea de inmediato (igual que los módulos)
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /** Valores iniciales: salen de config/empresa.php (variables EMPRESA_* del .env). */
    public static function porDefecto(): array
    {
        return [
            'nombre' => config('empresa.nombre'),
            'nit' => config('empresa.nit') ?: null,
            'direccion' => config('empresa.direccion') ?: null,
            'telefono' => config('empresa.telefono') ?: null,
            'eslogan' => 'Gestión inteligente para tu almacén agrícola',
            'descripcion' => 'Controla semillas, fertilizantes, agroquímicos y equipos de riego, con trazabilidad por lote y alertas de vencimiento.',
            'color' => 'verde',
        ];
    }

    /** La identidad vigente. Si todavía no se ha configurado, usa los valores por defecto (sin guardar nada). */
    public static function actual(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::first() ?? new static(self::porDefecto()));
    }

    /** Tonos del color elegido (si la clave no existe, vuelve al verde). */
    public function tonos(): array
    {
        return (self::PALETAS[$this->color] ?? self::PALETAS['verde'])['tonos'];
    }

    /** Solo hay que redefinir colores cuando se eligió algo distinto del verde original. */
    public function paleta(): ?array
    {
        return isset(self::PALETAS[$this->color]) && $this->color !== 'verde' ? $this->tonos() : null;
    }

    /** Datos para los documentos PDF y correos. */
    public function paraPdf(): array
    {
        return [
            'nombre' => $this->nombre,
            'nit' => $this->nit,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'logo' => $this->logo,
            'color' => $this->tonos()['800'],
        ];
    }
}
