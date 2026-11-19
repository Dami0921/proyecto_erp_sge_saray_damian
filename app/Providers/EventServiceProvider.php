<?php

namespace App\Providers;

use App\Events\CompraRecibida;
use App\Events\VentaRegistrada;
use App\Listeners\NotificarCompraRecibida;
use App\Listeners\RevisarStockBajo;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        // Clase 14: reglas automáticas del negocio
        VentaRegistrada::class => [
            RevisarStockBajo::class,
        ],
        CompraRecibida::class => [
            NotificarCompraRecibida::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
