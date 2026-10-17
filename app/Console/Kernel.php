<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('schedules:generate-recurring')->dailyAt('00:01');
        $schedule->command('bookings:check-expired-resaved')->daily(); // Run daily

        // Daily disbursement batch: collect at midnight, then drain one payout
        // per minute (ClickPesa permits one payout create per 60 seconds).
        $schedule->command('disbursements:collect')->dailyAt('00:00')->withoutOverlapping(30);
        $schedule->command('disbursements:process')->everyMinute()->withoutOverlapping(5);
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
