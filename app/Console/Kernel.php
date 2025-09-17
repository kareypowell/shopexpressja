<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        
        // Schedule the weekly user signup report command
        $schedule->command('shs:user-signup-report')
            ->weeklyOn(7, '00:00'); // Runs every Monday at midnight
            
        // Process scheduled broadcast messages every 5 minutes
        $schedule->command('broadcast:process-scheduled')
            ->everyFiveMinutes()
            ->withoutOverlapping(10) // Prevent overlapping runs, timeout after 10 minutes
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Scheduled broadcast processing completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Scheduled broadcast processing failed');
            });

        // Execute scheduled backups every hour
        $schedule->command('backup:scheduled')
            ->hourly()
            ->withoutOverlapping(30) // Prevent overlapping runs, timeout after 30 minutes
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Scheduled backup check completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Scheduled backup check failed');
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
