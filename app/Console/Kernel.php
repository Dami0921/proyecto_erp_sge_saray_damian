<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Cada día a las 07:00 (hora de Bogotá): aviso de lotes que vencen pronto
        $schedule->command('erp:lotes-por-vencer')
            ->dailyAt(config('erp.revision_vencimientos_a_las'))
            ->timezone('America/Bogota')
            ->withoutOverlapping();

        // Mantenimiento: la bitácora de auditoría conserva solo lo que define config/activitylog.php
        $schedule->command('activitylog:clean')->monthly()->timezone('America/Bogota');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
