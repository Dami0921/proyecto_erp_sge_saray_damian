<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LoteController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST del ERP (Clase 13). Todas las rutas quedan bajo /api/v1/...
|--------------------------------------------------------------------------
| Versionar la API (v1) permite cambiarla en el futuro sin romper las apps que ya la usan.
| Los permisos son los mismos de la web (Spatie), calculados con el ROL ACTIVO guardado en el token.
*/
Route::prefix('v1')->group(function () {

    // Pública: máximo 5 intentos de login por minuto por IP
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // Protegidas con token Bearer de Sanctum (60 peticiones por minuto, límite del grupo «api»)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        // Igual que en la web: si el módulo está apagado, el recurso responde 404 (en JSON)
        Route::middleware('modulo:inventario')->group(function () {
            Route::get('productos', [ProductoController::class, 'index']);
            Route::get('productos/{producto}', [ProductoController::class, 'show']);

            Route::get('lotes/por-vencer', [LoteController::class, 'porVencer']);
        });

        Route::middleware('modulo:ventas')->group(function () {
            Route::get('ventas/{venta}', [VentaController::class, 'show'])->withTrashed();
            Route::post('ventas', [VentaController::class, 'store']);
        });
    });
});
