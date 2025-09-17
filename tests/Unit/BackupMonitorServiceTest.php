<?php

namespace Tests\Unit;

use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Services\BackupMonitorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupMonitorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BackupMonitorService $monitorService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitorService = new BackupMonitorService();
        
        // Set up test configuration
        Config::set('backup.storage.path', 'test-backups');
        Config::set('backup.storage.max_file_size', 1024);
    }

    public function test_get_system_health_returns_complete_status()
    {
        // Create test data
        Backup::factory()->create(['status' => 'completed', 'created_at' => now()->subDays(1)]);
        Backup::factory()->create(['status' => 'failed', 'created_at' => now()->subDays(2)]);
        BackupSchedule::factory()->create(['is_active' => true, 'last_run_at' => now()->subHour()]);

        $health = $this->monitorService->getSystemHealth();

        $this->assertArrayHasKey('overall_status', $health);
        $this->assertArrayHasKey('recent_backups', $health);
        $this->assertArrayHasKey('storage_usage', $health);
        $this->assertArrayHasKey('schedule_health', $health);
        $this->assertArrayHasKey('failed_backups', $health);
        $this->assertArrayHasKey('warnings', $health);
    }

    public function test_get_recent_backup_status_calculates_correctly()
    {
        // Create test backups
        Backup::factory()->count(3)->create([
            'status' => 'completed',
            'created_at' => now()->subDays(2)
        ]);
        
        Backup::factory()->count(2)->create([
            'status' => 'failed',
            'created_at' => now()->subDays(1)
        ]);

        $status = $this->monitorService->getRecentBackupStatus(7);

        $this->assertEquals(5, $status['total']);
        $this->assertEquals(3, $status['successful']);
        $this->assertEquals(2, $status['failed']);
        $this->assertEquals(60.0, $status['success_rate']);
        $this->assertNotNull($status['last_successful']);
        $this->assertNotNull($status['last_failed']);
    }

    public function test_get_storage_usage_calculates_correctly()
    {
        // Mock storage files
        Storage::fake('local');
        Storage::put('test-backups/backup1.sql', str_repeat('x', 1024 * 1024)); // 1MB
        Storage::put('test-backups/backup2.zip', str_repeat('x', 512 * 1024)); // 0.5MB

        $usage = $this->monitorService->getStorageUsage();

        $this->assertArrayHasKey('total_size_bytes', $usage);
        $this->assertArrayHasKey('total_size_mb', $usage);
        $this->assertArrayHasKey('file_count', $usage);
        $this->assertArrayHasKey('usage_percentage', $usage);
        $this->assertArrayHasKey('is_critical', $usage);
        $this->assertArrayHasKey('is_warning', $usage);
    }

    public function test_get_schedule_health_identifies_overdue_schedules()
    {
        // Create healthy schedule
        BackupSchedule::factory()->create([
            'is_active' => true,
            'last_run_at' => now()->subHour(),
            'next_run_at' => now()->addHour(),
            'name' => 'Healthy Schedule'
        ]);

        // Create overdue schedule
        BackupSchedule::factory()->create([
            'is_active' => true,
            'last_run_at' => now()->subDays(2),
            'next_run_at' => now()->subHours(5),
            'name' => 'Overdue Schedule'
        ]);

        $health = $this->monitorService->getScheduleHealth();

        $this->assertEquals(2, $health['total_schedules']);
        $this->assertEquals(1, $health['healthy_schedules']);
        $this->assertCount(1, $health['overdue_schedules']);
        $this->assertEquals(50.0, $health['health_percentage']);
        $this->assertEquals('Overdue Schedule', $health['overdue_schedules'][0]['name']);
    }

    public function test_get_failed_backups_returns_recent_failures()
    {
        // Create old failed backup (should not be included)
        Backup::factory()->create([
            'status' => 'failed',
            'created_at' => now()->subDays(10),
            'metadata' => ['error_message' => 'Old error']
        ]);

        // Create recent failed backups
        Backup::factory()->count(2)->create([
            'status' => 'failed',
            'created_at' => now()->subDays(2),
            'metadata' => ['error_message' => 'Recent error']
        ]);

        $failedBackups = $this->monitorService->getFailedBackups(7);

        $this->assertCount(2, $failedBackups);
        $this->assertEquals('Recent error', $failedBackups->first()['error_message']);
    }

    public function test_get_system_warnings_detects_storage_issues()
    {
        // Mock high storage usage by creating smaller files but adjusting config
        Storage::fake('local');
        Config::set('backup.storage.max_file_size', 1); // 1MB limit for testing
        
        // Create file that's 90% of the 1MB limit
        $largeContent = str_repeat('x', 900 * 1024); // 900KB
        Storage::put('test-backups/large-backup.sql', $largeContent);

        $warnings = $this->monitorService->getSystemWarnings();

        $storageWarnings = collect($warnings)->where('type', 'storage_critical');
        $this->assertGreaterThan(0, $storageWarnings->count());
    }

    public function test_get_system_warnings_detects_recent_failures()
    {
        // Create recent failed backup
        Backup::factory()->create([
            'status' => 'failed',
            'created_at' => now()->subHours(12),
            'metadata' => ['error_message' => 'Test failure']
        ]);

        $warnings = $this->monitorService->getSystemWarnings();

        $failureWarnings = collect($warnings)->where('type', 'recent_failures');
        $this->assertGreaterThan(0, $failureWarnings->count());
    }

    public function test_should_send_alert_returns_true_for_critical_issues()
    {
        // Create critical storage situation with smaller test files
        Storage::fake('local');
        Config::set('backup.storage.max_file_size', 1); // 1MB limit for testing
        
        $criticalContent = str_repeat('x', 950 * 1024); // 950KB (95% of 1MB)
        Storage::put('test-backups/critical-backup.sql', $criticalContent);

        $shouldAlert = $this->monitorService->shouldSendAlert();

        $this->assertTrue($shouldAlert);
    }

    public function test_should_send_alert_returns_true_for_recent_failures()
    {
        // Create recent failure
        Backup::factory()->create([
            'status' => 'failed',
            'created_at' => now()->subHours(6)
        ]);

        $shouldAlert = $this->monitorService->shouldSendAlert();

        $this->assertTrue($shouldAlert);
    }

    public function test_should_send_alert_returns_false_for_healthy_system()
    {
        // Create successful backup
        Backup::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subHours(6)
        ]);

        $shouldAlert = $this->monitorService->shouldSendAlert();

        $this->assertFalse($shouldAlert);
    }

    public function test_overall_status_reflects_system_health()
    {
        // Test healthy system
        Backup::factory()->create(['status' => 'completed', 'created_at' => now()->subHour()]);
        $health = $this->monitorService->getSystemHealth();
        $this->assertEquals('healthy', $health['overall_status']);

        // Test system with warnings
        Backup::factory()->create(['status' => 'failed', 'created_at' => now()->subHours(12)]);
        $health = $this->monitorService->getSystemHealth();
        $this->assertContains($health['overall_status'], ['warning', 'critical']);
    }
}