<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

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
        Route::patch('productos/{producto}/restore', [ProductoController::class, 'restore'])
            ->withTrashed()
            ->name('productos.restore');
        Route::resource('productos', ProductoController::class)->except('show')->names('productos');

        // Categorías
        Route::patch('categorias/{categoria}/restore', [CategoriaController::class, 'restore'])
            ->withTrashed()
            ->name('categorias.restore');
        Route::resource('categorias', CategoriaController::class)->except('show')->names('categorias');
    });
});

require __DIR__.'/auth.php';
