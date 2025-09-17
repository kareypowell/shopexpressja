<?php

namespace Tests\Unit;

use App\Models\Backup;
use App\Mail\BackupSuccessNotification;
use App\Mail\BackupFailureNotification;
use App\Mail\BackupSystemHealthAlert;
use App\Services\BackupMonitorService;
use App\Services\BackupNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BackupNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BackupNotificationService $notificationService;
    protected BackupMonitorService $monitorService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->monitorService = $this->createMock(BackupMonitorService::class);
        $this->notificationService = new BackupNotificationService($this->monitorService);
        
        // Set up test configuration
        Config::set('backup.notifications.email', 'admin@test.com');
        Config::set('backup.notifications.notify_on_success', true);
        Config::set('backup.notifications.notify_on_failure', true);
        
        Mail::fake();
    }

    public function test_notify_backup_success_sends_email_when_enabled()
    {
        $backup = Backup::factory()->create(['status' => 'completed']);
        $systemHealth = ['overall_status' => 'healthy'];
        
        $this->monitorService
            ->expects($this->once())
            ->method('getSystemHealth')
            ->willReturn($systemHealth);

        $result = $this->notificationService->notifyBackupSuccess($backup);

        $this->assertTrue($result);
        Mail::assertSent(BackupSuccessNotification::class, function ($mail) use ($backup) {
            return $mail->backup->id === $backup->id;
        });
    }

    public function test_notify_backup_success_skips_when_disabled()
    {
        Config::set('backup.notifications.notify_on_success', false);
        
        $backup = Backup::factory()->create(['status' => 'completed']);

        $result = $this->notificationService->notifyBackupSuccess($backup);

        $this->assertFalse($result);
        Mail::assertNotSent(BackupSuccessNotification::class);
    }

    public function test_notify_backup_failure_sends_email_when_enabled()
    {
        $backup = Backup::factory()->create(['status' => 'failed']);
        $errorMessage = 'Test error message';
        $systemHealth = ['overall_status' => 'warning'];
        
        $this->monitorService
            ->expects($this->once())
            ->method('getSystemHealth')
            ->willReturn($systemHealth);

        $result = $this->notificationService->notifyBackupFailure($backup, $errorMessage);

        $this->assertTrue($result);
        Mail::assertSent(BackupFailureNotification::class, function ($mail) use ($backup, $errorMessage) {
            return $mail->backup->id === $backup->id && $mail->errorMessage === $errorMessage;
        });
    }

    public function test_notify_backup_failure_skips_when_disabled()
    {
        Config::set('backup.notifications.notify_on_failure', false);
        
        $backup = Backup::factory()->create(['status' => 'failed']);

        $result = $this->notificationService->notifyBackupFailure($backup);

        $this->assertFalse($result);
        Mail::assertNotSent(BackupFailureNotification::class);
    }

    public function test_notify_system_health_alert_sends_email_with_warnings()
    {
        $systemHealth = [
            'overall_status' => 'warning',
            'warnings' => [
                ['type' => 'storage_warning', 'message' => 'Storage is 80% full', 'severity' => 'warning']
            ]
        ];
        
        $this->monitorService
            ->expects($this->once())
            ->method('getSystemHealth')
            ->willReturn($systemHealth);

        $result = $this->notificationService->notifySystemHealthAlert();

        $this->assertTrue($result);
        Mail::assertSent(BackupSystemHealthAlert::class, function ($mail) {
            return count($mail->warnings) === 1;
        });
    }

    public function test_notify_system_health_alert_skips_when_no_warnings()
    {
        $systemHealth = [
            'overall_status' => 'healthy',
            'warnings' => []
        ];
        
        $this->monitorService
            ->expects($this->once())
            ->method('getSystemHealth')
            ->willReturn($systemHealth);

        $result = $this->notificationService->notifySystemHealthAlert();

        $this->assertFalse($result);
        Mail::assertNotSent(BackupSystemHealthAlert::class);
    }

    public function test_check_and_send_health_alerts_sends_when_needed()
    {
        $systemHealth = [
            'overall_status' => 'critical',
            'warnings' => [
                ['type' => 'storage_critical', 'message' => 'Storage is 95% full', 'severity' => 'critical']
            ]
        ];
        
        $this->monitorService
            ->expects($this->once())
            ->method('shouldSendAlert')
            ->willReturn(true);
            
        $this->monitorService
            ->expects($this->once())
            ->method('getSystemHealth')
            ->willReturn($systemHealth);

        $result = $this->notificationService->checkAndSendHealthAlerts();

        $this->assertTrue($result);
        Mail::assertSent(BackupSystemHealthAlert::class);
    }

    public function test_check_and_send_health_alerts_skips_when_not_needed()
    {
        $this->monitorService
            ->expects($this->once())
            ->method('shouldSendAlert')
            ->willReturn(false);

        $result = $this->notificationService->checkAndSendHealthAlerts();

        $this->assertFalse($result);
        Mail::assertNotSent(BackupSystemHealthAlert::class);
    }

    public function test_send_daily_health_summary_sends_with_warnings()
    {
        Config::set('backup.notifications.daily_summary', true);
        
        $systemHealth = [
            'overall_status' => 'warning',
            'warnings' => [
                ['type' => 'schedule_overdue', 'message' => '1 schedule is overdue', 'severity' => 'warning']
            ]
        ];
        
        $this->monitorService
            ->expects($this->once())
            ->method('getSystemHealth')
            ->willReturn($systemHealth);

        $result = $this->notificationService->sendDailyHealthSummary();

        $this->assertTrue($result);
        Mail::assertSent(BackupSystemHealthAlert::class);
    }

    public function test_send_daily_health_summary_skips_when_healthy_and_disabled()
    {
        Config::set('backup.notifications.daily_summary', false);
        
        $systemHealth = [
            'overall_status' => 'healthy',
            'warnings' => []
        ];
        
        $this->monitorService
            ->expects($this->once())
            ->method('getSystemHealth')
            ->willReturn($systemHealth);

        $result = $this->notificationService->sendDailyHealthSummary();

        $this->assertFalse($result);
        Mail::assertNotSent(BackupSystemHealthAlert::class);
    }

    public function test_get_notification_preferences_returns_correct_settings()
    {
        $preferences = $this->notificationService->getNotificationPreferences();

        $this->assertArrayHasKey('email', $preferences);
        $this->assertArrayHasKey('notify_on_success', $preferences);
        $this->assertArrayHasKey('notify_on_failure', $preferences);
        $this->assertArrayHasKey('daily_summary', $preferences);
        
        $this->assertEquals('admin@test.com', $preferences['email']);
        $this->assertTrue($preferences['notify_on_success']);
        $this->assertTrue($preferences['notify_on_failure']);
    }

    public function test_test_notifications_returns_status_array()
    {
        $this->monitorService
            ->expects($this->once())
            ->method('getSystemHealth')
            ->willReturn(['overall_status' => 'healthy']);

        $results = $this->notificationService->testNotifications();

        $this->assertArrayHasKey('email_config', $results);
        $this->assertArrayHasKey('health_check', $results);
        $this->assertEquals('success', $results['email_config']['status']);
        $this->assertEquals('success', $results['health_check']['status']);
    }

    public function test_notification_uses_fallback_email_when_not_configured()
    {
        Config::set('backup.notifications.email', null);
        Config::set('mail.from.address', 'fallback@test.com');
        
        $backup = Backup::factory()->create(['status' => 'completed']);
        $this->monitorService
            ->expects($this->once())
            ->method('getSystemHealth')
            ->willReturn(['overall_status' => 'healthy']);

        $this->notificationService->notifyBackupSuccess($backup);

        Mail::assertSent(BackupSuccessNotification::class, function ($mail) {
            return $mail->hasTo('fallback@test.com');
        });
    }
}