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
        //para rodar todo dia às 23 horas
        // $schedule->command('app:pcp-search')->withoutOverlapping()->dailyAt('23:00');
        // $schedule->command('app:pncp-search')->withoutOverlapping()->hourly();
        // $schedule->command('app:pcp-get-items')->withoutOverlapping()->hourly();
        $schedule->command('app:pncp-get-items')->dailyAt('09:00');
        $schedule->command('app:alerta-licitacao-search')->dailyAt('19:00');
        $schedule->command('app:send-notification-whatsapp')->cron('0 19 */2 * *');  
        $schedule->command('app:automation-populate')->everyTwoMinutes();
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
