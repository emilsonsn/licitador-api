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
        $schedule->command('app:alerta-licitacao-search')
            ->dailyAt('08:00')
            ->withoutOverlapping();  

        $schedule->command('app:pncp-search')
            ->dailyAt('16:00')
            ->withoutOverlapping();
            
        $schedule->command('app:compras-api-search')
            ->dailyAt('00:00')
            ->withoutOverlapping();

        $schedule->command('app:pncp-search')
            ->dailyAt('23:00')
            ->withoutOverlapping();

        $schedule->command('app:compras-api-search')->dailyAt('12:00');
        $schedule->command('app:automation-populate')->everyFiveMinutes();
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
