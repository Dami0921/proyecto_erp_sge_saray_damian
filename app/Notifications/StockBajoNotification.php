<?php

namespace App\Notifications;

use App\Models\Producto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Alerta de stock bajo por correo y en la campana del ERP.
 * ShouldQueue: el correo se envía en segundo plano (cola), así la venta no espera al servidor de correo.
 */
class StockBajoNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Producto $producto, public int $stock) {}

    // Canales: correo + tabla notifications (campana)
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Stock bajo: {$this->producto->nombre}")
            ->greeting("Hola, {$notifiable->name}")
            ->line("El producto «{$this->producto->nombre}» quedó con {$this->stock} {$this->producto->unidad_medida}(s) disponibles.")
            ->line('El mínimo configurado es '.config('erp.umbral_stock_bajo').'.')
            ->action('Crear orden de compra', route('compras.create'))
            ->line('Este mensaje fue generado automáticamente por el ERP.');
    }

    // Lo que se guarda en la columna data de la tabla notifications (alimenta la campana)
    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => 'Stock bajo',
            'mensaje' => "«{$this->producto->nombre}» quedó con {$this->stock} unidad(es) disponibles.",
            'url' => route('inventario.kardex', ['producto' => $this->producto->id]),
        ];
    }
}
