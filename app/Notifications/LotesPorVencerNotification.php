<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Resumen diario de lotes que vencen pronto (lo envía la tarea programada erp:lotes-por-vencer).
 * En un almacén de agroinsumos, un lote vencido no se puede vender: avisar a tiempo evita la pérdida.
 */
class LotesPorVencerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param array<int, array{producto: string, lote: string, vence: string, dias: int, cantidad: int}> $lotes */
    public function __construct(public array $lotes, public int $dias) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mensaje = (new MailMessage)
            ->subject('Lotes por vencer en los próximos '.$this->dias.' días ('.count($this->lotes).')')
            ->greeting("Hola, {$notifiable->name}")
            ->line('Estos lotes con existencias vencen pronto. Véndalos primero (FEFO) o gestione su devolución:');

        foreach (array_slice($this->lotes, 0, 15) as $l) {
            $mensaje->line("• {$l['producto']} · lote {$l['lote']} · vence el {$l['vence']} (en {$l['dias']} día(s)) · {$l['cantidad']} unidad(es)");
        }

        if (count($this->lotes) > 15) {
            $mensaje->line('... y '.(count($this->lotes) - 15).' lote(s) más. Consulte el kardex para ver el detalle.');
        }

        return $mensaje->action('Abrir el kardex', route('inventario.kardex'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => 'Lotes por vencer',
            'mensaje' => count($this->lotes)." lote(s) vencen en los próximos {$this->dias} días.",
            'url' => route('inventario.kardex'),
        ];
    }
}
