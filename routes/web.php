<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
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

require __DIR__.'/auth.php';
