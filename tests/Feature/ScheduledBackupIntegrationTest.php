<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Services\BackupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ScheduledBackupIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create backup directory
        if (!file_exists(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test backup files
        $backupDir = storage_path('app/backups');
        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        parent::tearDown();
    }

    /** @test */
    public function it_executes_complete_scheduled_backup_workflow()
    {
        // Mock the backup service to return success
        $mockBackupService = \Mockery::mock(BackupService::class);
        $mockBackupService->shouldReceive('createManualBackup')
            ->once()
            ->andReturn(new \App\Services\BackupResult(true, 'Backup completed successfully', null, [
                'file_path' => 'scheduled_backup.sql',
                'file_size' => 1024
            ]));

        $this->app->instance(BackupService::class, $mockBackupService);

        // Create a due database backup schedule
        $schedule = BackupSchedule::factory()->create([
            'name' => 'Daily Database Backup',
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00:00',
            'is_active' => true,
            'retention_days' => 30,
            'next_run_at' => Carbon::now()->subMinutes(5),
            'last_run_at' => null,
        ]);

        // Execute the scheduled backup command
        $exitCode = Artisan::call('backup:scheduled');

        // Verify command executed successfully
        $this->assertEquals(0, $exitCode);

        // Verify schedule was updated
        $schedule->refresh();
        $this->assertNotNull($schedule->last_run_at);
        $this->assertGreaterThan(Carbon::now(), $schedule->next_run_at);
        
        // For daily frequency, next run should be tomorrow at 02:00
        $expectedNextRun = Carbon::tomorrow()->setTimeFromTimeString('02:00:00');
        $this->assertEquals($expectedNextRun->format('Y-m-d H:i'), $schedule->next_run_at->format('Y-m-d H:i'));
    }

    /** @test */
    public function it_handles_multiple_schedule_types_in_single_run()
    {
        // Mock the backup service to return success for all three calls
        $mockBackupService = \Mockery::mock(BackupService::class);
        $mockBackupService->shouldReceive('createManualBackup')
            ->times(3)
            ->andReturn(new \App\Services\BackupResult(true, 'Backup completed successfully', null, [
                'file_path' => 'test-backup.sql',
                'file_size' => 1024
            ]));

        $this->app->instance(BackupService::class, $mockBackupService);

        // Create multiple due schedules of different types
        $dbSchedule = BackupSchedule::factory()->create([
            'name' => 'Database Backup',
            'type' => 'database',
            'frequency' => 'daily',
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinutes(5),
        ]);

        $filesSchedule = BackupSchedule::factory()->create([
            'name' => 'Files Backup',
            'type' => 'files',
            'frequency' => 'weekly',
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinutes(10),
        ]);

        $fullSchedule = BackupSchedule::factory()->create([
            'name' => 'Full Backup',
            'type' => 'full',
            'frequency' => 'monthly',
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinutes(15),
        ]);

        // Execute the scheduled backup command
        $exitCode = Artisan::call('backup:scheduled');

        // Verify command executed successfully
        $this->assertEquals(0, $exitCode);

        // Verify all schedules were updated
        $dbSchedule->refresh();
        $filesSchedule->refresh();
        $fullSchedule->refresh();

        $this->assertNotNull($dbSchedule->last_run_at);
        $this->assertNotNull($filesSchedule->last_run_at);
        $this->assertNotNull($fullSchedule->last_run_at);
    }

    /** @test */
    public function it_respects_schedule_frequency_for_next_run_calculation()
    {
        Carbon::setTestNow('2023-01-15 10:00:00'); // Sunday

        // Mock the backup service to return success for all three calls
        $mockBackupService = \Mockery::mock(BackupService::class);
        $mockBackupService->shouldReceive('createManualBackup')
            ->times(3)
            ->andReturn(new \App\Services\BackupResult(true, 'Backup completed successfully', null, [
                'file_path' => 'test-backup.sql',
                'file_size' => 1024
            ]));

        $this->app->instance(BackupService::class, $mockBackupService);

        // Create schedules with different frequencies
        $dailySchedule = BackupSchedule::factory()->create([
            'frequency' => 'daily',
            'time' => '02:00:00',
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinutes(5),
        ]);

        $weeklySchedule = BackupSchedule::factory()->create([
            'frequency' => 'weekly',
            'time' => '03:00:00',
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinutes(5),
        ]);

        $monthlySchedule = BackupSchedule::factory()->create([
            'frequency' => 'monthly',
            'time' => '04:00:00',
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinutes(5),
        ]);

        // Execute the scheduled backup command
        Artisan::call('backup:scheduled');

        // Verify next run times are calculated correctly
        $dailySchedule->refresh();
        $weeklySchedule->refresh();
        $monthlySchedule->refresh();

        // Daily should be tomorrow at 02:00
        $expectedDaily = Carbon::parse('2023-01-16 02:00:00');
        $this->assertEquals($expectedDaily, $dailySchedule->next_run_at);

        // Weekly should be next week at 03:00
        $expectedWeekly = Carbon::parse('2023-01-22 03:00:00');
        $this->assertEquals($expectedWeekly, $weeklySchedule->next_run_at);

        // Monthly should be next month at 04:00
        $expectedMonthly = Carbon::parse('2023-02-15 04:00:00');
        $this->assertEquals($expectedMonthly, $monthlySchedule->next_run_at);
    }

    /** @test */
    public function it_handles_backup_failures_gracefully()
    {
        // Mock the backup service to simulate a failure
        $mockBackupService = \Mockery::mock(BackupService::class);
        $mockBackupService->shouldReceive('createManualBackup')
            ->once()
            ->andReturn(new \App\Services\BackupResult(false, 'Database connection failed'));

        $this->app->instance(BackupService::class, $mockBackupService);

        Log::shouldReceive('error')
            ->once()
            ->with('Scheduled backup failed', \Mockery::type('array'));

        $schedule = BackupSchedule::factory()->create([
            'name' => 'Failing Backup',
            'type' => 'database',
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinutes(5),
            'last_run_at' => null,
        ]);

        // Execute the scheduled backup command
        $exitCode = Artisan::call('backup:scheduled');

        // Verify command reports failure
        $this->assertEquals(1, $exitCode);

        // Verify schedule was NOT marked as run since it failed
        $schedule->refresh();
        $this->assertNull($schedule->last_run_at);
    }

    /** @test */
    public function it_logs_scheduled_backup_activities()
    {
        // Mock the backup service to return success
        $mockBackupService = \Mockery::mock(BackupService::class);
        $mockBackupService->shouldReceive('createManualBackup')
            ->once()
            ->andReturn(new \App\Services\BackupResult(true, 'Backup completed successfully', null, [
                'file_path' => 'test-backup.sql',
                'file_size' => 1024
            ]));

        $this->app->instance(BackupService::class, $mockBackupService);

        Log::shouldReceive('info')
            ->once()
            ->with('Scheduled backup completed successfully', \Mockery::type('array'));

        $schedule = BackupSchedule::factory()->create([
            'name' => 'Test Logging Backup',
            'type' => 'database',
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinutes(5),
            'last_run_at' => null,
        ]);

        // Execute the scheduled backup command
        Artisan::call('backup:scheduled');
    }

    /** @test */
    public function it_skips_inactive_schedules_even_if_due()
    {
        // Mock the backup service to return success for one call
        $mockBackupService = \Mockery::mock(BackupService::class);
        $mockBackupService->shouldReceive('createManualBackup')
            ->once()
            ->andReturn(new \App\Services\BackupResult(true, 'Backup completed successfully', null, [
                'file_path' => 'test-backup.sql',
                'file_size' => 1024
            ]));

        $this->app->instance(BackupService::class, $mockBackupService);

        // Create an inactive schedule that would be due
        $inactiveSchedule = BackupSchedule::factory()->create([
            'name' => 'Inactive Backup',
            'type' => 'database',
            'is_active' => false,
            'next_run_at' => Carbon::now()->subMinutes(5),
            'last_run_at' => null,
        ]);

        // Create an active schedule that's due
        $activeSchedule = BackupSchedule::factory()->create([
            'name' => 'Active Backup',
            'type' => 'database',
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinutes(5),
            'last_run_at' => null,
        ]);

        // Execute the scheduled backup command
        $exitCode = Artisan::call('backup:scheduled');

        // Verify command executed successfully
        $this->assertEquals(0, $exitCode);

        // Verify only the active schedule was marked as run
        $inactiveSchedule->refresh();
        $activeSchedule->refresh();

        $this->assertNull($inactiveSchedule->last_run_at);
        $this->assertNotNull($activeSchedule->last_run_at);
    }

    /** @test */
    public function it_passes_retention_days_from_schedule_to_backup()
    {
        // Mock the backup service to verify retention_days is passed
        $mockBackupService = \Mockery::mock(BackupService::class);
        $mockBackupService->shouldReceive('createManualBackup')
            ->once()
            ->with(\Mockery::on(function ($options) {
                return $options['retention_days'] === 45;
            }))
            ->andReturn(new \App\Services\BackupResult(true, 'Backup completed successfully', null, [
                'file_path' => 'test-backup.sql',
                'file_size' => 1024
            ]));

        $this->app->instance(BackupService::class, $mockBackupService);

        $schedule = BackupSchedule::factory()->create([
            'name' => 'Retention Test Backup',
            'type' => 'database',
            'retention_days' => 45,
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinutes(5),
            'last_run_at' => null,
        ]);

        // Execute the scheduled backup command
        Artisan::call('backup:scheduled');

        // Verify the schedule was marked as run
        $schedule->refresh();
        $this->assertNotNull($schedule->last_run_at);
    }

    /** @test */
    public function it_handles_schedule_with_null_next_run_at()
    {
        // Create a schedule with null next_run_at (edge case)
        $schedule = BackupSchedule::factory()->create([
            'name' => 'Null Next Run Backup',
            'type' => 'database',
            'is_active' => true,
            'next_run_at' => null,
            'last_run_at' => null,
        ]);

        // Execute the scheduled backup command
        $exitCode = Artisan::call('backup:scheduled');

        // Should complete successfully but not execute the schedule
        $this->assertEquals(0, $exitCode);

        // Verify no backup was created
        $this->assertCount(0, Backup::all());

        // Verify schedule was not marked as run
        $schedule->refresh();
        $this->assertNull($schedule->last_run_at);
    }

    public static function tearDownAfterClass(): void
    {
        Carbon::setTestNow(); // Reset Carbon test time
        parent::tearDownAfterClass();
    }
}