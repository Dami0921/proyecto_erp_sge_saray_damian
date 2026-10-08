<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Lote;
use App\Models\Producto;
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
            ],
            [
                'titulo' => 'Categorías',
                'descripcion' => $user->can('crear-categorias')
                    ? 'Organiza los productos por categoría.'
                    : 'Consulta las categorías de productos.',
                'ruta' => 'categorias.index',
                'permiso' => 'ver-categorias',
            ],
        ])->filter(fn (array $m) => $user->can($m['permiso']))->values();

        $indicadores = collect();

        if ($user->can('ver-productos')) {
            $indicadores->push(['etiqueta' => 'Total de productos', 'valor' => Producto::count()]);
        }
        if ($user->can('ver-categorias')) {
            $indicadores->push(['etiqueta' => 'Total de categorías', 'valor' => Categoria::count()]);
        }
        if ($user->can('ver-clientes')) {
            $indicadores->push(['etiqueta' => 'Total de clientes', 'valor' => Cliente::count()]);
        }
        if ($user->can('ver-lotes')) {
            $indicadores->push([
                'etiqueta' => 'Lotes por vencer (30 días)',
                'valor' => Lote::whereBetween('fecha_vencimiento', [today(), today()->addDays(30)])->count(),
            ]);
        }

        return view('dashboard', [
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'modulos' => $modulos,
            'indicadores' => $indicadores,
        ]);
    }
}
