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

        // Generate scheduled audit reports every hour
        $schedule->command('audit:generate-scheduled-reports')
            ->hourly()
            ->withoutOverlapping(15) // Prevent overlapping runs, timeout after 15 minutes
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Scheduled audit report generation completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Scheduled audit report generation failed');
            });

        // Archive old audit logs weekly (Sundays at 2 AM)
        $schedule->command('audit:archive --force')
            ->weeklyOn(0, '02:00') // Sunday at 2 AM
            ->withoutOverlapping(60) // Prevent overlapping runs, timeout after 60 minutes
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Scheduled audit archival completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Scheduled audit archival failed');
            });

        // Clean up old audit logs daily (3 AM)
        $schedule->command('audit:cleanup --archive --force')
            ->dailyAt('03:00')
            ->withoutOverlapping(30) // Prevent overlapping runs, timeout after 30 minutes
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Scheduled audit cleanup completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Scheduled audit cleanup failed');
            });

        // Warm up audit caches every 30 minutes during business hours
        $schedule->command('audit:performance warmup-cache')
            ->cron('*/30 6-18 * * 1-5') // Every 30 minutes, 6 AM to 6 PM, Monday to Friday
            ->withoutOverlapping(10)
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Audit cache warmup completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Audit cache warmup failed');
            });

        // Performance analysis weekly (Sundays at 1 AM)
        $schedule->command('audit:performance analyze-performance')
            ->weeklyOn(0, '01:00')
            ->withoutOverlapping(15)
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Audit performance analysis completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Audit performance analysis failed');
            });

        // Warm up report caches every 30 minutes during business hours
        $schedule->command('reports:warm-cache --days=30')
            ->cron('*/30 6-18 * * 1-5') // Every 30 minutes, 6 AM to 6 PM, Monday to Friday
            ->withoutOverlapping(15)
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Report cache warmup completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Report cache warmup failed');
            });

        // Full report cache warmup daily at 5 AM
        $schedule->command('reports:warm-cache --days=90 --force')
            ->dailyAt('05:00')
            ->withoutOverlapping(30)
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Full report cache warmup completed successfully');
            })
            ->onFailure(function () {
                \Log::error('Full report cache warmup failed');
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
