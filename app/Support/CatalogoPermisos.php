<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Convierte la lista plana de permisos («ver-productos», «recibir-compras»...) en una tabla
 * módulo × acción, que es lo que se dibuja en la matriz de seguridad.
 * Convención de nombres: «<acción>-<módulo>».
 */
class CatalogoPermisos
{
    public const MODULOS = [
        'productos' => 'Productos',
        'categorias' => 'Categorías',
        'lotes' => 'Lotes',
        'proveedores' => 'Proveedores',
        'clientes' => 'Clientes',
        'compras' => 'Compras',
        'ventas' => 'Ventas',
        'reportes' => 'Reportes (Ventas − Compras)',
        'modulos' => 'Módulos del sistema',
        'usuarios' => 'Usuarios',
        'roles' => 'Roles y permisos',
    ];

    public const ACCIONES = [
        'ver' => 'Ver',
        'crear' => 'Crear',
        'editar' => 'Editar',
        'eliminar' => 'Eliminar / anular',
        'recibir' => 'Recibir mercancía',
        'gestionar' => 'Gestionar',
    ];

    /** @return array<string, array<string, string>> [módulo => [acción => nombre del permiso]] */
    public static function agrupar(Collection $nombres): array
    {
        $tabla = [];

        foreach ($nombres as $nombre) {
            [$accion, $modulo] = array_pad(explode('-', $nombre, 2), 2, '');
            $tabla[$modulo][$accion] = $nombre;
        }

        // Ordena según el catálogo (los módulos desconocidos van al final)
        $orden = array_keys(self::MODULOS);
        uksort($tabla, fn ($a, $b) => (array_search($a, $orden) === false ? 99 : array_search($a, $orden))
            <=> (array_search($b, $orden) === false ? 99 : array_search($b, $orden)));

        return $tabla;
    }

    public static function etiquetaModulo(string $modulo): string
    {
        return self::MODULOS[$modulo] ?? ucfirst($modulo);
    }
}
