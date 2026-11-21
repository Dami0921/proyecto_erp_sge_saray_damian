<?php

namespace App\Providers;

use App\Models\Modulo;
use App\Models\Organizacion;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Throwable;

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

        // Los correos y el título salen con el nombre de la empresa. El worker de la cola vive mucho tiempo:
        // se refresca antes de cada trabajo para que un cambio de nombre se vea sin reiniciarlo.
        $this->aplicarIdentidad();
        Queue::before(fn () => $this->aplicarIdentidad());
    }

    private function aplicarIdentidad(): void
    {
        try {
            $nombre = Organizacion::actual()->nombre;
            config(['app.name' => $nombre, 'mail.from.name' => $nombre]);
        } catch (Throwable) {
            // Antes de migrar la tabla todavía no existe: se conserva el nombre del .env
        }
    }
}
