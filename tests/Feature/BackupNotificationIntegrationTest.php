<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Mail\BackupSuccessNotification;
use App\Mail\BackupFailureNotification;
use App\Mail\BackupSystemHealthAlert;
use App\Services\BackupService;
use App\Services\BackupNotificationService;
use App\Services\BackupMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupNotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Mail::fake();
        Storage::fake('local');
        
        // Set up test configuration
        Config::set('backup.notifications.email', 'admin@shopexpressja.com');
        Config::set('backup.notifications.notify_on_success', true);
        Config::set('backup.notifications.notify_on_failure', true);
        Config::set('backup.notifications.health_alerts', true);
    }

    public function test_backup_service_sends_success_notification_on_completion()
    {
        // Mock the backup handlers to avoid actual backup operations
        $this->mockBackupHandlers();

        $backupService = app(BackupService::class);
        $result = $backupService->createManualBackup(['type' => 'database']);

        $this->assertTrue($result->success);
        
        Mail::assertSent(BackupSuccessNotification::class, function ($mail) use ($result) {
            return $mail->backup->id === $result->backup->id;
        });
    }

    public function test_backup_service_sends_failure_notification_on_error()
    {
        // Mock backup handlers to throw exception
        $this->mockFailingBackupHandlers();

        $backupService = app(BackupService::class);
        $result = $backupService->createManualBackup(['type' => 'database']);

        $this->assertFalse($result->success);
        
        Mail::assertSent(BackupFailureNotification::class, function ($mail) use ($result) {
            return $mail->backup->id === $result->backup->id;
        });
    }

    public function test_monitor_service_integration_with_notifications()
    {
        $monitorService = app(BackupMonitorService::class);
        $notificationService = app(BackupNotificationService::class);

        // Create conditions that should trigger alerts
        Backup::factory()->create([
            'status' => 'failed',
            'created_at' => now()->subHours(6),
            'metadata' => ['error_message' => 'Integration test failure']
        ]);

        $systemHealth = $monitorService->getSystemHealth();
        $this->assertNotEmpty($systemHealth['warnings']);

        $shouldAlert = $monitorService->shouldSendAlert();
        $this->assertTrue($shouldAlert);

        $alertSent = $notificationService->checkAndSendHealthAlerts();
        $this->assertTrue($alertSent);

        Mail::assertSent(BackupSystemHealthAlert::class);
    }

    public function test_health_check_command_integration_with_notifications()
    {
        // Create failed backup to trigger alert
        Backup::factory()->create([
            'status' => 'failed',
            'created_at' => now()->subHours(12),
            'metadata' => ['error_message' => 'Command integration test']
        ]);

        $this->artisan('backup:health-check --send-alerts')
            ->assertExitCode(1); // Warning exit code

        Mail::assertSent(BackupSystemHealthAlert::class);
    }

    public function test_daily_summary_integration()
    {
        Config::set('backup.notifications.daily_summary', true);

        // Create mixed backup history
        Backup::factory()->count(3)->create([
            'status' => 'completed',
            'created_at' => now()->subDays(1)
        ]);
        
        Backup::factory()->create([
            'status' => 'failed',
            'created_at' => now()->subHours(18),
            'metadata' => ['error_message' => 'Daily summary test failure']
        ]);

        $this->artisan('backup:health-check --daily-summary')
            ->assertExitCode(1); // Warning due to failed backup

        Mail::assertSent(BackupSystemHealthAlert::class, function ($mail) {
            return !empty($mail->warnings);
        });
    }

    public function test_notification_preferences_integration()
    {
        $notificationService = app(BackupNotificationService::class);
        $preferences = $notificationService->getNotificationPreferences();

        $this->assertEquals('admin@shopexpressja.com', $preferences['email']);
        $this->assertTrue($preferences['notify_on_success']);
        $this->assertTrue($preferences['notify_on_failure']);
    }

    public function test_notification_system_test_integration()
    {
        $notificationService = app(BackupNotificationService::class);
        $testResults = $notificationService->testNotifications();

        $this->assertArrayHasKey('email_config', $testResults);
        $this->assertArrayHasKey('health_check', $testResults);
        $this->assertEquals('success', $testResults['email_config']['status']);
        $this->assertEquals('success', $testResults['health_check']['status']);
    }

    public function test_storage_warning_notification_integration()
    {
        // Set smaller limits for testing
        Config::set('backup.storage.max_file_size', 1); // 1MB limit
        
        // Create files to trigger storage warnings (800KB each = 80% of 1MB)
        Storage::put('backups/large-backup-1.sql', str_repeat('x', 400 * 1024)); // 400KB
        Storage::put('backups/large-backup-2.sql', str_repeat('x', 400 * 1024)); // 400KB

        $monitorService = app(BackupMonitorService::class);
        $notificationService = app(BackupNotificationService::class);

        $systemHealth = $monitorService->getSystemHealth();
        $storageUsage = $systemHealth['storage_usage'];

        // Should trigger warning if over threshold
        if ($storageUsage['is_warning'] || $storageUsage['is_critical']) {
            $alertSent = $notificationService->notifySystemHealthAlert();
            $this->assertTrue($alertSent);
            Mail::assertSent(BackupSystemHealthAlert::class);
        }
    }

    public function test_schedule_overdue_notification_integration()
    {
        // Create overdue schedule
        BackupSchedule::factory()->create([
            'is_active' => true,
            'name' => 'Overdue Integration Test',
            'last_run_at' => now()->subDays(2),
            'next_run_at' => now()->subHours(5)
        ]);

        $monitorService = app(BackupMonitorService::class);
        $notificationService = app(BackupNotificationService::class);

        $systemHealth = $monitorService->getSystemHealth();
        $scheduleHealth = $systemHealth['schedule_health'];

        $this->assertNotEmpty($scheduleHealth['overdue_schedules']);

        $alertSent = $notificationService->checkAndSendHealthAlerts();
        $this->assertTrue($alertSent);

        Mail::assertSent(BackupSystemHealthAlert::class, function ($mail) {
            $scheduleWarnings = collect($mail->warnings)->where('type', 'schedule_overdue');
            return $scheduleWarnings->isNotEmpty();
        });
    }

    public function test_notification_disabled_integration()
    {
        Config::set('backup.notifications.notify_on_success', false);
        Config::set('backup.notifications.notify_on_failure', false);

        $this->mockBackupHandlers();

        $backupService = app(BackupService::class);
        $result = $backupService->createManualBackup(['type' => 'database']);

        $this->assertTrue($result->success);
        Mail::assertNotSent(BackupSuccessNotification::class);
    }

    public function test_email_template_data_integration()
    {
        $backup = Backup::factory()->create([
            'status' => 'completed',
            'name' => 'Integration Test Backup',
            'type' => 'database',
            'file_size' => 1024 * 1024 * 50, // 50MB
            'created_at' => now()->subMinutes(30),
            'completed_at' => now()->subMinutes(25)
        ]);

        $notificationService = app(BackupNotificationService::class);
        $result = $notificationService->notifyBackupSuccess($backup);

        $this->assertTrue($result);

        Mail::assertSent(BackupSuccessNotification::class, function ($mail) use ($backup) {
            return $mail->backup->id === $backup->id &&
                   $mail->backup->name === 'Integration Test Backup' &&
                   $mail->backup->type === 'database';
        });
    }

    /**
     * Mock backup handlers for successful operations
     */
    protected function mockBackupHandlers(): void
    {
        $this->app->bind(\App\Services\DatabaseBackupHandler::class, function () {
            $mock = $this->createMock(\App\Services\DatabaseBackupHandler::class);
            $mock->method('createDump')->willReturn('test-backup.sql');
            $mock->method('validateDump')->willReturn(true);
            return $mock;
        });

        $this->app->bind(\App\Services\FileBackupHandler::class, function () {
            $mock = $this->createMock(\App\Services\FileBackupHandler::class);
            $mock->method('backupDirectory')->willReturn('test-backup.zip');
            $mock->method('validateArchive')->willReturn(true);
            return $mock;
        });

        // Mock file size
        Storage::put('test-backup.sql', str_repeat('x', 1024));
    }

    /**
     * Mock backup handlers to fail
     */
    protected function mockFailingBackupHandlers(): void
    {
        $this->app->bind(\App\Services\DatabaseBackupHandler::class, function () {
            $mock = $this->createMock(\App\Services\DatabaseBackupHandler::class);
            $mock->method('createDump')->willThrowException(new \Exception('Mock database backup failure'));
            return $mock;
        });
    }
}