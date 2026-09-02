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
        $schedule->command('stock:check-alerts')
            ->everyMinute()
            ->withoutOverlapping()
            // Tanpa ini output dibuang ke NUL dan penyebab
            // kegagalan tidak terlihat sama sekali.
            ->appendOutputTo(storage_path('logs/stock-alerts.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
