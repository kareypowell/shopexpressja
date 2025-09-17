<?php

namespace Tests\Feature;

use App\Console\Commands\ScheduledBackupCommand;
use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Carbon\Carbon;

class ScheduledBackupCommandTest extends TestCase
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
    public function it_shows_no_schedules_message_when_no_schedules_are_due()
    {
        // Create a schedule that's not due yet
        BackupSchedule::factory()->notDue()->create();

        $this->artisan('backup:scheduled')
            ->expectsOutput('No scheduled backups are due.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_executes_due_schedules()
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

        // Create a due schedule
        $schedule = BackupSchedule::factory()->due()->databaseOnly()->create([
            'name' => 'Test Daily Backup',
        ]);

        $this->artisan('backup:scheduled')
            ->expectsOutput('Found 1 due schedule(s).')
            ->expectsOutput("Executing scheduled backup: {$schedule->name}")
            ->assertExitCode(0);

        // Verify the schedule was marked as run
        $schedule->refresh();
        $this->assertNotNull($schedule->last_run_at);
        $this->assertGreaterThan(now(), $schedule->next_run_at);
    }

    /** @test */
    public function it_can_run_specific_schedule_by_id()
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

        $schedule = BackupSchedule::factory()->active()->databaseOnly()->create([
            'name' => 'Specific Test Backup',
        ]);

        $this->artisan('backup:scheduled', ['--schedule-id' => $schedule->id])
            ->expectsOutput("Executing scheduled backup: {$schedule->name}")
            ->assertExitCode(0);

        // Verify the schedule was marked as run
        $schedule->refresh();
        $this->assertNotNull($schedule->last_run_at);
    }

    /** @test */
    public function it_shows_error_for_non_existent_schedule_id()
    {
        $this->artisan('backup:scheduled', ['--schedule-id' => 999])
            ->expectsOutput('Schedule with ID 999 not found.')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_shows_warning_for_inactive_schedule()
    {
        $schedule = BackupSchedule::factory()->inactive()->create([
            'name' => 'Inactive Backup',
        ]);

        $this->artisan('backup:scheduled', ['--schedule-id' => $schedule->id])
            ->expectsOutput("Schedule 'Inactive Backup' is not active.")
            ->assertExitCode(0);
    }

    /** @test */
    public function it_supports_dry_run_mode()
    {
        $schedule = BackupSchedule::factory()->create([
            'name' => 'Test Backup',
            'type' => 'database',
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinutes(5),
            'last_run_at' => null,
        ]);

        $this->artisan('backup:scheduled', ['--dry-run' => true])
            ->expectsOutput('Found 1 due schedule(s).')
            ->assertExitCode(0);

        // Verify the schedule was NOT marked as run
        $schedule->refresh();
        $this->assertNull($schedule->last_run_at);
    }

    /** @test */
    public function it_supports_dry_run_for_specific_schedule()
    {
        $schedule = BackupSchedule::factory()->active()->create([
            'name' => 'Specific Backup',
            'type' => 'files',
        ]);

        $this->artisan('backup:scheduled', [
            '--schedule-id' => $schedule->id,
            '--dry-run' => true
        ])
            ->expectsOutput('Would execute schedule: Specific Backup (files)')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_multiple_due_schedules()
    {
        // Mock the backup service to return success for both calls
        $mockBackupService = \Mockery::mock(BackupService::class);
        $mockBackupService->shouldReceive('createManualBackup')
            ->twice()
            ->andReturn(new \App\Services\BackupResult(true, 'Backup completed successfully', null, [
                'file_path' => 'test-backup.sql',
                'file_size' => 1024
            ]));

        $this->app->instance(BackupService::class, $mockBackupService);

        $schedule1 = BackupSchedule::factory()->due()->databaseOnly()->create([
            'name' => 'Database Backup',
        ]);
        
        $schedule2 = BackupSchedule::factory()->due()->filesOnly()->create([
            'name' => 'Files Backup',
        ]);

        $this->artisan('backup:scheduled')
            ->expectsOutput('Found 2 due schedule(s).')
            ->expectsOutput('Executing scheduled backup: Database Backup')
            ->expectsOutput('Executing scheduled backup: Files Backup')
            ->expectsOutput('Successful: 2, Failed: 0')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_logs_successful_backup_execution()
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

        $schedule = BackupSchedule::factory()->due()->databaseOnly()->create([
            'last_run_at' => null,
        ]);

        $this->artisan('backup:scheduled')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_logs_backup_failures()
    {
        // Mock the backup service to return a failure
        $mockBackupService = \Mockery::mock(BackupService::class);
        $mockBackupService->shouldReceive('createManualBackup')
            ->once()
            ->andReturn(new \App\Services\BackupResult(false, 'Test failure'));

        $this->app->instance(BackupService::class, $mockBackupService);

        Log::shouldReceive('error')
            ->once()
            ->with('Scheduled backup failed', \Mockery::type('array'));

        $schedule = BackupSchedule::factory()->due()->databaseOnly()->create();

        $this->artisan('backup:scheduled')
            ->expectsOutput('✗ Backup failed: Test failure')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_generates_proper_backup_names_for_scheduled_backups()
    {
        // Mock the backup service to capture the backup name
        $mockBackupService = \Mockery::mock(BackupService::class);
        $mockBackupService->shouldReceive('createManualBackup')
            ->once()
            ->with(\Mockery::on(function ($options) {
                return str_contains($options['name'], 'scheduled_My_Test_Backup___');
            }))
            ->andReturn(new \App\Services\BackupResult(true, 'Backup completed successfully', null, [
                'file_path' => 'test-backup.sql',
                'file_size' => 1024
            ]));

        $this->app->instance(BackupService::class, $mockBackupService);

        $schedule = BackupSchedule::factory()->due()->databaseOnly()->create([
            'name' => 'My Test Backup!@#',
        ]);

        $this->artisan('backup:scheduled')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_passes_correct_options_to_backup_service()
    {
        $schedule = BackupSchedule::factory()->due()->create([
            'name' => 'Test Schedule',
            'type' => 'full',
            'retention_days' => 30,
        ]);

        // Mock the backup service to verify the options passed
        $mockBackupService = \Mockery::mock(BackupService::class);
        $mockBackupService->shouldReceive('createManualBackup')
            ->once()
            ->with(\Mockery::on(function ($options) use ($schedule) {
                return $options['type'] === 'full' &&
                       $options['retention_days'] === 30 &&
                       $options['scheduled'] === true &&
                       $options['schedule_id'] === $schedule->id &&
                       str_contains($options['name'], 'scheduled_Test_Schedule');
            }))
            ->andReturn(new \App\Services\BackupResult(true, 'Backup completed successfully', null, [
                'file_path' => 'test-backup.sql',
                'file_size' => 1024
            ]));

        $this->app->instance(BackupService::class, $mockBackupService);

        $this->artisan('backup:scheduled')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_backup_service_exceptions()
    {
        // Mock the backup service to throw an exception
        $mockBackupService = \Mockery::mock(BackupService::class);
        $mockBackupService->shouldReceive('createManualBackup')
            ->once()
            ->andThrow(new \Exception('Service unavailable'));

        $this->app->instance(BackupService::class, $mockBackupService);

        Log::shouldReceive('error')
            ->once()
            ->with('Scheduled backup failed with exception', \Mockery::type('array'));

        $schedule = BackupSchedule::factory()->due()->databaseOnly()->create();

        $this->artisan('backup:scheduled')
            ->expectsOutput('✗ Backup failed with exception: Service unavailable')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_formats_file_sizes_correctly()
    {
        $command = new ScheduledBackupCommand(app(BackupService::class));
        
        // Use reflection to test the protected formatBytes method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        $this->assertEquals('500 bytes', $method->invoke($command, 500));
        $this->assertEquals('1.50 KB', $method->invoke($command, 1536));
        $this->assertEquals('2.00 MB', $method->invoke($command, 2097152));
        $this->assertEquals('1.00 GB', $method->invoke($command, 1073741824));
    }

    /** @test */
    public function it_only_executes_active_schedules()
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

        // Create one active and one inactive schedule, both due
        $activeSchedule = BackupSchedule::factory()->due()->active()->create([
            'name' => 'Active Backup',
            'last_run_at' => null,
        ]);
        
        $inactiveSchedule = BackupSchedule::factory()->create([
            'is_active' => false,
            'next_run_at' => Carbon::now()->subMinutes(5),
            'name' => 'Inactive Backup',
            'last_run_at' => null,
        ]);

        $this->artisan('backup:scheduled')
            ->expectsOutput('Found 1 due schedule(s).')
            ->expectsOutput('Executing scheduled backup: Active Backup')
            ->assertExitCode(0);

        // Verify only the active schedule was marked as run
        $activeSchedule->refresh();
        $inactiveSchedule->refresh();
        
        $this->assertNotNull($activeSchedule->last_run_at);
        $this->assertNull($inactiveSchedule->last_run_at);
    }
}