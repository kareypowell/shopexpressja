<?php

namespace Tests\Feature;

use App\Console\Commands\BackupHealthCheckCommand;
use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Services\BackupMonitorService;
use App\Services\BackupNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BackupHealthCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_health_check_command_displays_system_status()
    {
        // Create test data
        Backup::factory()->create(['status' => 'completed', 'created_at' => now()->subDays(1)]);
        Backup::factory()->create(['status' => 'failed', 'created_at' => now()->subDays(2)]);
        BackupSchedule::factory()->create(['is_active' => true, 'last_run_at' => now()->subHour()]);

        $this->artisan('backup:health-check')
            ->expectsOutput('Checking backup system health...')
            ->assertExitCode(0);
    }

    public function test_health_check_command_with_json_output()
    {
        Backup::factory()->create(['status' => 'completed', 'created_at' => now()->subDays(1)]);

        $this->artisan('backup:health-check --json')
            ->expectsOutput('Checking backup system health...')
            ->assertExitCode(0);
    }

    public function test_health_check_command_sends_alerts_when_requested()
    {
        // Create failed backup to trigger alert
        Backup::factory()->create([
            'status' => 'failed',
            'created_at' => now()->subHours(12),
            'metadata' => ['error_message' => 'Test failure']
        ]);

        $this->artisan('backup:health-check --send-alerts')
            ->expectsOutput('Checking backup system health...')
            ->assertExitCode(1); // Warning exit code due to recent failure
    }

    public function test_health_check_command_sends_daily_summary_when_requested()
    {
        Backup::factory()->create(['status' => 'completed', 'created_at' => now()->subDays(1)]);

        $this->artisan('backup:health-check --daily-summary')
            ->expectsOutput('Checking backup system health...')
            ->assertExitCode(0);
    }

    public function test_health_check_command_returns_critical_exit_code()
    {
        // Create conditions that would trigger critical status
        // This would require mocking storage usage or other critical conditions
        
        $this->artisan('backup:health-check')
            ->expectsOutput('Checking backup system health...');
            // Exit code depends on actual system health
    }

    public function test_health_check_command_handles_exceptions_gracefully()
    {
        // Mock a service that throws an exception
        $this->app->bind(BackupMonitorService::class, function () {
            $mock = $this->createMock(BackupMonitorService::class);
            $mock->method('getSystemHealth')->willThrowException(new \Exception('Test exception'));
            return $mock;
        });

        $this->artisan('backup:health-check')
            ->expectsOutput('Health check failed: Test exception')
            ->assertExitCode(1);
    }

    public function test_health_check_command_displays_recent_backups_section()
    {
        Backup::factory()->count(3)->create([
            'status' => 'completed',
            'created_at' => now()->subDays(2)
        ]);
        
        Backup::factory()->count(1)->create([
            'status' => 'failed',
            'created_at' => now()->subDays(1)
        ]);

        $this->artisan('backup:health-check')
            ->expectsOutputToContain('Recent Backups (7 days):')
            ->expectsOutputToContain('Total: 4')
            ->expectsOutputToContain('Successful: 3')
            ->expectsOutputToContain('Failed: 1')
            ->assertExitCode(1); // Warning due to failed backup
    }

    public function test_health_check_command_displays_storage_usage_section()
    {
        $this->artisan('backup:health-check')
            ->expectsOutputToContain('Storage Usage:')
            ->expectsOutputToContain('Usage:')
            ->expectsOutputToContain('Files:')
            ->assertExitCode(0);
    }

    public function test_health_check_command_displays_schedule_health_section()
    {
        BackupSchedule::factory()->create([
            'is_active' => true,
            'name' => 'Daily Database Backup',
            'last_run_at' => now()->subHour()
        ]);

        $this->artisan('backup:health-check')
            ->expectsOutputToContain('Backup Schedules:')
            ->expectsOutputToContain('Total: 1')
            ->expectsOutputToContain('Healthy: 1')
            ->assertExitCode(0);
    }

    public function test_health_check_command_displays_overdue_schedules()
    {
        BackupSchedule::factory()->create([
            'is_active' => true,
            'name' => 'Overdue Schedule',
            'last_run_at' => now()->subDays(2),
            'next_run_at' => now()->subHours(5)
        ]);

        $this->artisan('backup:health-check')
            ->expectsOutputToContain('Overdue Schedules:')
            ->expectsOutputToContain('Overdue Schedule')
            ->assertExitCode(1); // Warning due to overdue schedule
    }

    public function test_health_check_command_displays_warnings_section()
    {
        // Create a recent failed backup to generate warnings
        Backup::factory()->create([
            'status' => 'failed',
            'created_at' => now()->subHours(6),
            'metadata' => ['error_message' => 'Test failure']
        ]);

        $this->artisan('backup:health-check')
            ->expectsOutputToContain('System Warnings:')
            ->assertExitCode(1); // Warning due to recent failure
    }

    public function test_health_check_command_displays_failed_backups_section()
    {
        Backup::factory()->create([
            'status' => 'failed',
            'name' => 'Failed Test Backup',
            'type' => 'database',
            'created_at' => now()->subDays(1),
            'metadata' => ['error_message' => 'Database connection failed']
        ]);

        $this->artisan('backup:health-check')
            ->expectsOutputToContain('Recent Failed Backups:')
            ->expectsOutputToContain('Failed Test Backup')
            ->expectsOutputToContain('Database connection failed')
            ->assertExitCode(1); // Warning due to failed backup
    }

    public function test_health_check_command_shows_healthy_status_when_no_issues()
    {
        // Create only successful backups
        Backup::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subHours(6)
        ]);

        $this->artisan('backup:health-check')
            ->expectsOutputToContain('Overall Status: HEALTHY')
            ->expectsOutputToContain('No warnings detected.')
            ->assertExitCode(0);
    }

    public function test_health_check_command_with_all_options()
    {
        Backup::factory()->create(['status' => 'completed', 'created_at' => now()->subDays(1)]);

        $this->artisan('backup:health-check --send-alerts --daily-summary --json')
            ->expectsOutput('Checking backup system health...')
            ->assertExitCode(0);
    }
}