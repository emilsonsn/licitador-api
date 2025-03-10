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
        $schedule->command('app:alerta-licitacao-search')->dailyAt('19:00');
        // $schedule->command('app:pncp-search')->everyThreeHours()->withoutOverlapping();
        $schedule->command('app:pncp-get-items')->dailyAt('09:00');
        $schedule->command('app:compras-api-search')->dailyAt('12:00');
        $schedule->command('app:document-notification-whatsapp')->dailyAt('18:00');
        $schedule->command('app:send-notification-whatsapp')->cron('0 19 */2 * *');  
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
