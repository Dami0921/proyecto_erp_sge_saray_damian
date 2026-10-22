<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Compra;
use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Lote;
use App\Models\Modulo;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Venta;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Cada módulo solo se muestra si el usuario tiene el permiso indicado
        $modulos = collect([
            [
                'titulo' => 'Productos',
                'descripcion' => $user->can('crear-productos')
                    ? 'Consulta y administra el inventario de productos.'
                    : 'Consulta el inventario de productos.',
                'ruta' => 'productos.index',
                'permiso' => 'ver-productos',
                'modulo' => 'inventario',
            ],
            [
                'titulo' => 'Categorías',
                'descripcion' => $user->can('crear-categorias')
                    ? 'Organiza los productos por categoría.'
                    : 'Consulta las categorías de productos.',
                'ruta' => 'categorias.index',
                'permiso' => 'ver-categorias',
                'modulo' => 'inventario',
            ],
            [
                'titulo' => 'Clientes',
                'descripcion' => $user->can('crear-clientes')
                    ? 'Administra los clientes de la empresa.'
                    : 'Consulta los clientes.',
                'ruta' => 'clientes.index',
                'permiso' => 'ver-clientes',
                'modulo' => 'clientes',
            ],
            [
                'titulo' => 'Proveedores',
                'descripcion' => $user->can('crear-proveedores')
                    ? 'Administra los proveedores de la empresa.'
                    : 'Consulta los proveedores.',
                'ruta' => 'proveedores.index',
                'permiso' => 'ver-proveedores',
                'modulo' => 'proveedores',
            ],
            [
                'titulo' => 'Compras',
                'descripcion' => $user->can('crear-compras')
                    ? 'Pide mercancía a los proveedores y registra su llegada.'
                    : 'Consulta las órdenes de compra.',
                'ruta' => 'compras.index',
                'permiso' => 'ver-compras',
                'modulo' => 'compras',
            ],
            [
                'titulo' => 'Ventas',
                'descripcion' => $user->can('crear-ventas')
                    ? 'Registra ventas y consulta los comprobantes de venta.'
                    : 'Consulta las ventas realizadas.',
                'ruta' => 'ventas.index',
                'permiso' => 'ver-ventas',
                'modulo' => 'ventas',
            ],
        ])
            // Solo se muestra si el módulo está activo Y el usuario tiene el permiso
            ->filter(fn (array $m) => Modulo::activo($m['modulo']) && $user->can($m['permiso']))
            ->values();

        $indicadores = collect();

        if (Modulo::activo('inventario') && $user->can('ver-productos')) {
            $indicadores->push(['etiqueta' => 'Total de productos', 'valor' => Producto::count()]);
        }
        if (Modulo::activo('inventario') && $user->can('ver-categorias')) {
            $indicadores->push(['etiqueta' => 'Total de categorías', 'valor' => Categoria::count()]);
        }
        if (Modulo::activo('clientes') && $user->can('ver-clientes')) {
            $indicadores->push(['etiqueta' => 'Total de clientes', 'valor' => Cliente::count()]);
        }
        if (Modulo::activo('inventario') && $user->can('ver-lotes')) {
            $indicadores->push([
                'etiqueta' => 'Lotes por vencer (30 días)',
                'valor' => Lote::where('cantidad_actual', '>', 0)
                    ->whereBetween('fecha_vencimiento', [today(), today()->addDays(30)])
                    ->count(),
            ]);
        }

        // Stock bajo: productos cuyo stock (suma de lotes vigentes) es de 10 unidades o menos
        if (Modulo::activo('inventario') && $user->can('ver-productos')) {
            $indicadores->push([
                'etiqueta' => 'Productos con stock bajo (≤ 10)',
                'valor' => Producto::conStock()->get()->filter(fn ($p) => (int) $p->stock <= 10)->count(),
            ]);
        }

        // ---- Compras (Clase 10) ----
        $mes = [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
        $comprasDelMes = null;
        $reabastecer = null; // null = no se muestra la tabla

        if (Modulo::activo('compras') && $user->can('ver-compras')) {
            $comprasDelMes = Compra::where('estado', '!=', Compra::ANULADA)->whereBetween('fecha', $mes)->sum('total');

            $indicadores->push(['etiqueta' => 'Compras del mes', 'valor' => '$'.number_format($comprasDelMes, 0, ',', '.')]);
            $indicadores->push([
                'etiqueta' => 'Órdenes por recibir',
                'valor' => Compra::whereIn('estado', [Compra::PENDIENTE, Compra::PARCIAL])->count(),
            ]);
        }

        if (Modulo::activo('proveedores') && $user->can('ver-proveedores')) {
            $indicadores->push(['etiqueta' => 'Proveedores activos', 'valor' => Proveedor::count()]);
        }

        // Ventas - Compras del mes: solo para quien puede ver reportes (admin) y con ambos módulos activos
        if (Modulo::activo('ventas') && Modulo::activo('compras') && $user->can('ver-reportes')) {
            $ventasMes = Venta::where('estado', Venta::COMPLETADA)->whereBetween('fecha', $mes)->sum('total');
            $comprasMes = $comprasDelMes ?? Compra::where('estado', '!=', Compra::ANULADA)->whereBetween('fecha', $mes)->sum('total');
            $indicadores->push([
                'etiqueta' => 'Ventas − Compras del mes',
                'valor' => ($ventasMes - $comprasMes < 0 ? '-' : '').'$'.number_format(abs($ventasMes - $comprasMes), 0, ',', '.'),
            ]);
        }

        // Productos para reabastecer (stock ≤ 10): tabla accionable que lleva a crear una orden de compra
        if (Modulo::activo('compras') && Modulo::activo('inventario') && $user->can('crear-compras')) {
            $reabastecer = Producto::conStock()->get()
                ->filter(fn ($p) => (int) $p->stock <= 10)
                ->sortBy('stock')
                ->take(8)
                ->values();
        }

        $topProductos = null; // null = el usuario no puede ver ventas (la tabla no se muestra)

        if (Modulo::activo('ventas') && $user->can('ver-ventas')) {
            $completadas = fn () => Venta::where('estado', Venta::COMPLETADA);

            $indicadores->push([
                'etiqueta' => 'Ventas del mes',
                'valor' => '$'.number_format(
                    $completadas()->whereBetween('fecha', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('total'),
                    0, ',', '.'
                ),
            ]);
            $indicadores->push([
                'etiqueta' => 'Ventas de hoy',
                'valor' => $completadas()->whereDate('fecha', today())->count(),
            ]);

            // Top 5: productos más vendidos (solo ventas completadas, no anuladas)
            $topProductos = DetalleVenta::query()
                ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
                ->whereNull('ventas.deleted_at')
                ->where('ventas.estado', Venta::COMPLETADA)
                ->selectRaw('detalle_ventas.producto_id, SUM(detalle_ventas.cantidad) as unidades, SUM(detalle_ventas.subtotal) as ingresos')
                ->groupBy('detalle_ventas.producto_id')
                ->orderByDesc('unidades')
                ->limit(5)
                ->with('producto')
                ->get();
        }

        return view('dashboard', [
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'modulos' => $modulos,
            'indicadores' => $indicadores,
            'topProductos' => $topProductos,
            'reabastecer' => $reabastecer,
        ]);
    }
}
