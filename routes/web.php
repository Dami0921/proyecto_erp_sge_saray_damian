<?php

use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\Configuracion\ConfiguracionController;
use App\Http\Controllers\Configuracion\RolController;
use App\Http\Controllers\Configuracion\UsuarioController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RolActivoController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rol activo: un usuario con varios roles elige con cuál trabaja (solo requiere estar autenticado)
Route::middleware('auth')->group(function () {
    Route::get('rol-activo', [RolActivoController::class, 'create'])->name('rol.seleccionar');
    Route::post('rol-activo', [RolActivoController::class, 'store'])->name('rol.cambiar');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'rol.activo', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'rol.activo'])->group(function () {

    // Campana de notificaciones (Clase 14): cada usuario solo ve las suyas
    Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/leer-todas', [NotificacionController::class, 'leerTodas'])->name('notificaciones.leer-todas');
    Route::get('notificaciones/{id}/abrir', [NotificacionController::class, 'abrir'])->name('notificaciones.abrir');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CONFIGURACIÓN (NÚCLEO: nunca depende de un módulo apagado). Cada sección exige su propio permiso.
    Route::get('configuracion', ConfiguracionController::class)
        ->middleware('permission:gestionar-usuarios|gestionar-roles|gestionar-modulos')
        ->name('configuracion.index');

    Route::prefix('configuracion')->name('configuracion.')->group(function () {
        Route::patch('usuarios/{usuario}/restore', [UsuarioController::class, 'restore'])
            ->withTrashed()
            ->name('usuarios.restore');
        Route::resource('usuarios', UsuarioController::class)->except('show');

        Route::get('roles', [RolController::class, 'index'])->name('roles.index');
        Route::put('roles', [RolController::class, 'update'])->name('roles.update');
        Route::post('roles', [RolController::class, 'store'])->name('roles.store');
        Route::delete('roles/{rol}', [RolController::class, 'destroy'])->name('roles.destroy');
    });

    // Auditoría (Clase 12). NÚCLEO: nunca depende de un módulo apagado
    Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');

    // Panel de módulos (NÚCLEO: nunca depende de un módulo apagado)
    Route::middleware('permission:gestionar-modulos')->group(function () {
        Route::get('modulos', [ModuloController::class, 'index'])->name('modulos.index');
        Route::patch('modulos/{modulo}', [ModuloController::class, 'toggle'])->name('modulos.toggle');
    });

    // Módulo CLIENTES: si está apagado, estas rutas responden 404
    Route::middleware('modulo:clientes')->group(function () {
        Route::patch('clientes/{cliente}/restore', [ClienteController::class, 'restore'])
            ->withTrashed()
            ->name('clientes.restore');
        Route::resource('clientes', ClienteController::class)->except('show')->names('clientes');
    });

    // Módulo PROVEEDORES: si está apagado, estas rutas responden 404
    Route::middleware('modulo:proveedores')->group(function () {
        Route::patch('proveedores/{proveedor}/restore', [ProveedorController::class, 'restore'])
            ->withTrashed()
            ->name('proveedores.restore');
        Route::resource('proveedores', ProveedorController::class)
            ->except('show')
            ->parameters(['proveedores' => 'proveedor'])
            ->names('proveedores');
    });

    // Módulo COMPRAS (depende de Inventario): si alguno está apagado, estas rutas responden 404
    Route::middleware('modulo:compras')->group(function () {
        // La compra anulada queda con soft delete, pero se sigue pudiendo consultar (withTrashed)
        Route::get('compras/{compra}/pdf', [CompraController::class, 'pdf'])->withTrashed()->name('compras.pdf');
        Route::post('compras/{compra}/recibir', [CompraController::class, 'recibir'])->name('compras.recibir');
        Route::resource('compras', CompraController::class)
            ->only(['index', 'create', 'store', 'show', 'destroy'])
            ->withTrashed(['show'])
            ->names('compras');
    });

    // Módulo VENTAS (depende de Inventario): si alguno está apagado, estas rutas responden 404
    Route::middleware('modulo:ventas')->group(function () {
        // La venta anulada queda con soft delete, pero se sigue pudiendo consultar (withTrashed)
        Route::get('ventas/exportar', [ExcelController::class, 'exportarVentas'])->name('ventas.exportar');
        Route::get('ventas/{venta}/pdf', [VentaController::class, 'pdf'])->withTrashed()->name('ventas.pdf');
        Route::resource('ventas', VentaController::class)
            ->only(['index', 'create', 'store', 'show', 'destroy'])
            ->withTrashed(['show'])
            ->names('ventas');
    });

    // Módulo INVENTARIO: si está apagado, todas estas rutas responden 404
    Route::middleware('modulo:inventario')->group(function () {
        // Productos (los permisos se validan dentro del controlador)
        // La ruta restore va antes del resource y usa withTrashed() para encontrar registros en la papelera
        // Excel (Clase 13): exportar el catálogo e importar productos con validación por fila
        Route::get('productos/exportar', [ExcelController::class, 'exportarProductos'])->name('productos.exportar');
        Route::get('productos/plantilla', [ExcelController::class, 'plantilla'])->name('productos.plantilla');
        Route::get('productos/importar', [ExcelController::class, 'formularioImportar'])->name('productos.importar');
        Route::post('productos/importar', [ExcelController::class, 'importar'])->name('productos.importar.guardar');
        Route::patch('productos/{producto}/restore', [ProductoController::class, 'restore'])
            ->withTrashed()
            ->name('productos.restore');
        Route::resource('productos', ProductoController::class)->except('show')->names('productos');

        // Categorías
        Route::patch('categorias/{categoria}/restore', [CategoriaController::class, 'restore'])
            ->withTrashed()
            ->name('categorias.restore');
        Route::resource('categorias', CategoriaController::class)->except('show')->names('categorias');

        // Kardex y ajustes de inventario (Clase 12): los permisos se validan en el controlador
        Route::get('inventario/kardex', [InventarioController::class, 'kardex'])->name('inventario.kardex');
        Route::get('inventario/ajustes/{producto}', [InventarioController::class, 'ajustar'])
            ->withTrashed()->name('inventario.ajustar');
        Route::post('inventario/ajustes/{producto}', [InventarioController::class, 'guardarAjuste'])
            ->withTrashed()->name('inventario.ajustar.guardar');
    });
});

require __DIR__.'/auth.php';
