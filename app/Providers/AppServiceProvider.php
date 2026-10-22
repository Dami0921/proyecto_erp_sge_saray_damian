<?php

namespace App\Providers;

use App\Models\Modulo;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Directiva para las vistas: @modulo('inventario') ... @endmodulo
        Blade::if('modulo', fn (string $clave) => Modulo::activo($clave));
    }
}
