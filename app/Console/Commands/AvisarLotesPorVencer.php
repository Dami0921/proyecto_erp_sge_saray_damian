<?php

namespace App\Console\Commands;

use App\Models\Lote;
use App\Models\Modulo;
use App\Models\User;
use App\Notifications\LotesPorVencerNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class AvisarLotesPorVencer extends Command
{
    protected $signature = 'erp:lotes-por-vencer {--dias= : Días hacia adelante a revisar (por defecto, config erp.dias_alerta_vencimiento)}';

    protected $description = 'Avisa por correo y en la campana de los lotes con existencias que vencen pronto, a quienes ven lotes';

    public function handle(): int
    {
        if (! Modulo::activo('inventario')) {
            $this->info('El módulo de Inventario está desactivado: no hay lotes que revisar.');

            return self::SUCCESS;
        }

        $dias = (int) ($this->option('dias') ?: config('erp.dias_alerta_vencimiento'));

        $lotes = Lote::with('producto')
            ->where('cantidad_actual', '>', 0)
            ->whereDate('fecha_vencimiento', '>=', today())
            ->whereDate('fecha_vencimiento', '<=', today()->addDays($dias))
            ->orderBy('fecha_vencimiento')
            ->get()
            ->map(fn (Lote $l) => [
                'producto' => $l->producto?->nombre ?? 'Producto eliminado',
                'lote' => $l->numero_lote,
                'vence' => $l->fecha_vencimiento->format('d/m/Y'),
                'dias' => (int) today()->diffInDays($l->fecha_vencimiento),
                'cantidad' => (int) $l->cantidad_actual,
            ])->all();

        if ($lotes === []) {
            $this->info("No hay lotes con existencias que venzan en los próximos {$dias} días.");

            return self::SUCCESS;
        }

        $destinatarios = User::permission('ver-lotes')->get();
        Notification::send($destinatarios, new LotesPorVencerNotification($lotes, $dias));

        $this->info(count($lotes)." lote(s) por vencer. Aviso enviado a {$destinatarios->count()} usuario(s).");

        return self::SUCCESS;
    }
}
