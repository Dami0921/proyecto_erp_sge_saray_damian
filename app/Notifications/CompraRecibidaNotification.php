<?php

namespace App\Notifications;

use App\Models\Compra;
use App\Models\Organizacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/** Aviso de mercancía recibida, con la orden de compra en PDF adjunta. */
class CompraRecibidaNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Compra $compra, public bool $completa) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $compra = $this->compra->load(['proveedor', 'usuario', 'detalles.producto']);
        $estado = $this->completa ? 'recibida por completo' : 'recibida parcialmente (aún hay mercancía pendiente)';

        // El PDF se genera con la misma vista de la orden de compra de la web
        $pdf = Pdf::loadView('pdf.orden_compra', ['compra' => $compra, 'empresa' => Organizacion::actual()->paraPdf()])
            ->setPaper('letter')->output();

        return (new MailMessage)
            ->subject("Mercancía recibida: {$compra->numero_orden}")
            ->greeting("Hola, {$notifiable->name}")
            ->line("La orden {$compra->numero_orden} fue {$estado}.")
            ->line('El stock ya fue actualizado en el inventario.')
            ->action('Ver la orden', route('compras.show', $compra))
            ->attachData($pdf, "orden-{$compra->numero_orden}.pdf", ['mime' => 'application/pdf']);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => $this->completa ? 'Mercancía recibida' : 'Recepción parcial',
            'mensaje' => "La orden {$this->compra->numero_orden} fue ".($this->completa ? 'recibida por completo.' : 'recibida parcialmente.'),
            'url' => route('compras.show', $this->compra),
        ];
    }
}
