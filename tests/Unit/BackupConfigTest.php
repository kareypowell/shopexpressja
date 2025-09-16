<?php

namespace Tests\Unit;

use App\Services\BackupConfig;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Tests\TestCase;

class BackupConfigTest extends TestCase
{
    private BackupConfig $backupConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupConfig = new BackupConfig();
    }

    /** @test */
    public function it_can_get_storage_configuration()
    {
        Config::set('backup.storage', [
            'path' => '/test/path',
            'max_file_size' => 1024,
            'disk' => 'test'
        ]);

        $config = $this->backupConfig->getStorageConfig();

        $this->assertEquals([
            'path' => '/test/path',
            'max_file_size' => 1024,
            'disk' => 'test'
        ], $config);
    }

    /** @test */
    public function it_can_get_storage_path_with_default()
    {
        Config::set('backup.storage.path', null);
        
        $path = $this->backupConfig->getStoragePath();
        
        $this->assertEquals(storage_path('app/backups'), $path);
    }

    /** @test */
    public function it_can_get_custom_storage_path()
    {
        Config::set('backup.storage.path', '/custom/backup/path');
        
        $path = $this->backupConfig->getStoragePath();
        
        $this->assertEquals('/custom/backup/path', $path);
    }

    /** @test */
    public function it_can_get_max_file_size_with_default()
    {
        Config::set('backup.storage.max_file_size', null);
        
        $size = $this->backupConfig->getMaxFileSize();
        
        $this->assertEquals(2048, $size);
    }

    /** @test */
    public function it_can_get_custom_max_file_size()
    {
        Config::set('backup.storage.max_file_size', 4096);
        
        $size = $this->backupConfig->getMaxFileSize();
        
        $this->assertEquals(4096, $size);
    }

    /** @test */
    public function it_can_get_storage_disk_with_default()
    {
        Config::set('backup.storage.disk', null);
        
        $disk = $this->backupConfig->getStorageDisk();
        
        $this->assertEquals('local', $disk);
    }

    /** @test */
    public function it_can_get_database_configuration()
    {
        Config::set('backup.database', [
            'timeout' => 600,
            'single_transaction' => false,
            'routines' => false,
            'triggers' => false
        ]);

        $config = $this->backupConfig->getDatabaseConfig();

        $this->assertEquals([
            'timeout' => 600,
            'single_transaction' => false,
            'routines' => false,
            'triggers' => false
        ], $config);
    }

    /** @test */
    public function it_can_get_database_timeout_with_default()
    {
        Config::set('backup.database.timeout', null);
        
        $timeout = $this->backupConfig->getDatabaseTimeout();
        
        $this->assertEquals(300, $timeout);
    }

    /** @test */
    public function it_can_check_database_single_transaction_setting()
    {
        Config::set('backup.database.single_transaction', false);
        
        $result = $this->backupConfig->isDatabaseSingleTransaction();
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_check_database_routines_setting()
    {
        Config::set('backup.database.routines', false);
        
        $result = $this->backupConfig->includeDatabaseRoutines();
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_check_database_triggers_setting()
    {
        Config::set('backup.database.triggers', false);
        
        $result = $this->backupConfig->includeDatabaseTriggers();
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_backup_directories()
    {
        Config::set('backup.files.directories', [
            'storage/app/public/test1',
            'storage/app/public/test2'
        ]);

        $directories = $this->backupConfig->getBackupDirectories();

        $this->assertEquals([
            'storage/app/public/test1',
            'storage/app/public/test2'
        ], $directories);
    }

    /** @test */
    public function it_can_get_compression_level_with_default()
    {
        Config::set('backup.files.compression_level', null);
        
        $level = $this->backupConfig->getCompressionLevel();
        
        $this->assertEquals(6, $level);
    }

    /** @test */
    public function it_validates_compression_level_range()
    {
        Config::set('backup.files.compression_level', 10);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Compression level must be between 0 and 9');
        
        $this->backupConfig->getCompressionLevel();
    }

    /** @test */
    public function it_validates_negative_compression_level()
    {
        Config::set('backup.files.compression_level', -1);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Compression level must be between 0 and 9');
        
        $this->backupConfig->getCompressionLevel();
    }

    /** @test */
    public function it_can_get_exclude_patterns()
    {
        Config::set('backup.files.exclude_patterns', ['*.tmp', '*.log']);

        $patterns = $this->backupConfig->getExcludePatterns();

        $this->assertEquals(['*.tmp', '*.log'], $patterns);
    }

    /** @test */
    public function it_can_get_retention_configuration()
    {
        Config::set('backup.retention', [
            'database_days' => 60,
            'files_days' => 30,
            'cleanup_enabled' => false,
            'min_backups_to_keep' => 5
        ]);

        $config = $this->backupConfig->getRetentionConfig();

        $this->assertEquals([
            'database_days' => 60,
            'files_days' => 30,
            'cleanup_enabled' => false,
            'min_backups_to_keep' => 5
        ], $config);
    }

    /** @test */
    public function it_can_get_database_retention_days_with_default()
    {
        Config::set('backup.retention.database_days', null);
        
        $days = $this->backupConfig->getDatabaseRetentionDays();
        
        $this->assertEquals(30, $days);
    }

    /** @test */
    public function it_can_get_files_retention_days_with_default()
    {
        Config::set('backup.retention.files_days', null);
        
        $days = $this->backupConfig->getFilesRetentionDays();
        
        $this->assertEquals(14, $days);
    }

    /** @test */
    public function it_can_check_cleanup_enabled_setting()
    {
        Config::set('backup.retention.cleanup_enabled', false);
        
        $result = $this->backupConfig->isCleanupEnabled();
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_min_backups_to_keep_with_default()
    {
        Config::set('backup.retention.min_backups_to_keep', null);
        
        $min = $this->backupConfig->getMinBackupsToKeep();
        
        $this->assertEquals(3, $min);
    }

    /** @test */
    public function it_can_get_notification_email()
    {
        Config::set('backup.notifications.email', 'test@example.com');
        
        $email = $this->backupConfig->getNotificationEmail();
        
        $this->assertEquals('test@example.com', $email);
    }

    /** @test */
    public function it_can_check_notification_settings()
    {
        Config::set('backup.notifications.notify_on_success', true);
        Config::set('backup.notifications.notify_on_failure', false);
        Config::set('backup.notifications.notify_on_cleanup', true);
        
        $this->assertTrue($this->backupConfig->shouldNotifyOnSuccess());
        $this->assertFalse($this->backupConfig->shouldNotifyOnFailure());
        $this->assertTrue($this->backupConfig->shouldNotifyOnCleanup());
    }

    /** @test */
    public function it_can_get_notification_channels()
    {
        Config::set('backup.notifications.channels', ['mail', 'slack']);

        $channels = $this->backupConfig->getNotificationChannels();

        $this->assertEquals(['mail', 'slack'], $channels);
    }

    /** @test */
    public function it_can_check_monitoring_enabled_setting()
    {
        Config::set('backup.monitoring.enabled', false);
        
        $result = $this->backupConfig->isMonitoringEnabled();
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_storage_warning_threshold()
    {
        Config::set('backup.monitoring.storage_warning_threshold', 90);
        
        $threshold = $this->backupConfig->getStorageWarningThreshold();
        
        $this->assertEquals(90, $threshold);
    }

    /** @test */
    public function it_can_get_max_backup_age_hours()
    {
        Config::set('backup.monitoring.max_backup_age_hours', 72);
        
        $hours = $this->backupConfig->getMaxBackupAgeHours();
        
        $this->assertEquals(72, $hours);
    }

    /** @test */
    public function it_can_check_health_check_enabled_setting()
    {
        Config::set('backup.monitoring.health_check_enabled', false);
        
        $result = $this->backupConfig->isHealthCheckEnabled();
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_check_encryption_enabled_setting()
    {
        Config::set('backup.security.encrypt_backups', true);
        
        $result = $this->backupConfig->isEncryptionEnabled();
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_get_encryption_key()
    {
        Config::set('backup.security.encryption_key', 'test-key-123');
        
        $key = $this->backupConfig->getEncryptionKey();
        
        $this->assertEquals('test-key-123', $key);
    }

    /** @test */
    public function it_can_get_file_permissions()
    {
        Config::set('backup.security.file_permissions', 0644);
        
        $permissions = $this->backupConfig->getFilePermissions();
        
        $this->assertEquals(0644, $permissions);
    }

    /** @test */
    public function it_can_get_directory_permissions()
    {
        Config::set('backup.security.directory_permissions', 0755);
        
        $permissions = $this->backupConfig->getDirectoryPermissions();
        
        $this->assertEquals(0755, $permissions);
    }

    /** @test */
    public function it_can_get_download_link_ttl()
    {
        Config::set('backup.security.download_link_ttl', 7200);
        
        $ttl = $this->backupConfig->getDownloadLinkTtl();
        
        $this->assertEquals(7200, $ttl);
    }

    /** @test */
    public function it_can_get_schedule_configuration()
    {
        Config::set('backup.schedule', [
            'default_frequency' => 'weekly',
            'default_time' => '03:00',
            'max_concurrent_backups' => 2,
            'retry_attempts' => 3,
            'retry_delay' => 600
        ]);

        $config = $this->backupConfig->getScheduleConfig();

        $this->assertEquals([
            'default_frequency' => 'weekly',
            'default_time' => '03:00',
            'max_concurrent_backups' => 2,
            'retry_attempts' => 3,
            'retry_delay' => 600
        ], $config);
    }

    /** @test */
    public function it_can_get_default_frequency()
    {
        Config::set('backup.schedule.default_frequency', 'weekly');
        
        $frequency = $this->backupConfig->getDefaultFrequency();
        
        $this->assertEquals('weekly', $frequency);
    }

    /** @test */
    public function it_can_get_default_time()
    {
        Config::set('backup.schedule.default_time', '03:30');
        
        $time = $this->backupConfig->getDefaultTime();
        
        $this->assertEquals('03:30', $time);
    }

    /** @test */
    public function it_can_get_max_concurrent_backups()
    {
        Config::set('backup.schedule.max_concurrent_backups', 3);
        
        $max = $this->backupConfig->getMaxConcurrentBackups();
        
        $this->assertEquals(3, $max);
    }

    /** @test */
    public function it_can_get_retry_attempts()
    {
        Config::set('backup.schedule.retry_attempts', 5);
        
        $attempts = $this->backupConfig->getRetryAttempts();
        
        $this->assertEquals(5, $attempts);
    }

    /** @test */
    public function it_can_get_retry_delay()
    {
        Config::set('backup.schedule.retry_delay', 900);
        
        $delay = $this->backupConfig->getRetryDelay();
        
        $this->assertEquals(900, $delay);
    }

    /** @test */
    public function it_validates_configuration_successfully()
    {
        Config::set('backup', [
            'storage' => ['path' => '/valid/path'],
            'files' => ['compression_level' => 5],
            'retention' => [
                'database_days' => 30,
                'files_days' => 14,
                'min_backups_to_keep' => 3
            ],
            'notifications' => [
                'email' => 'test@example.com',
                'notify_on_success' => false,
                'notify_on_failure' => false,
                'notify_on_cleanup' => false
            ],
            'monitoring' => ['storage_warning_threshold' => 80]
        ]);

        $errors = $this->backupConfig->validateConfig();

        $this->assertEmpty($errors);
    }

    /** @test */
    public function it_validates_storage_path_is_configured()
    {
        Config::set('backup.storage.path', '');

        $errors = $this->backupConfig->validateConfig();

        $this->assertContains('Backup storage path is not configured', $errors);
    }

    /** @test */
    public function it_validates_compression_level_in_config_validation()
    {
        Config::set('backup.files.compression_level', 15);

        $errors = $this->backupConfig->validateConfig();

        $this->assertContains('Compression level must be between 0 and 9', $errors);
    }

    /** @test */
    public function it_validates_retention_days_are_positive()
    {
        Config::set('backup.retention.database_days', 0);
        Config::set('backup.retention.files_days', -1);

        $errors = $this->backupConfig->validateConfig();

        $this->assertContains('Database retention days must be at least 1', $errors);
        $this->assertContains('Files retention days must be at least 1', $errors);
    }

    /** @test */
    public function it_validates_min_backups_to_keep_is_positive()
    {
        Config::set('backup.retention.min_backups_to_keep', 0);

        $errors = $this->backupConfig->validateConfig();

        $this->assertContains('Minimum backups to keep must be at least 1', $errors);
    }

    /** @test */
    public function it_validates_notification_email_when_notifications_enabled()
    {
        Config::set('backup.notifications', [
            'email' => '',
            'notify_on_success' => true,
            'notify_on_failure' => false,
            'notify_on_cleanup' => false
        ]);

        $errors = $this->backupConfig->validateConfig();

        $this->assertContains('Notification email is required when notifications are enabled', $errors);
    }

    /** @test */
    public function it_validates_storage_warning_threshold_range()
    {
        Config::set('backup.monitoring.storage_warning_threshold', 150);

        $errors = $this->backupConfig->validateConfig();

        $this->assertContains('Storage warning threshold must be between 1 and 100', $errors);
    }

    /** @test */
    public function it_can_get_all_configuration()
    {
        $testConfig = [
            'storage' => ['path' => '/test'],
            'database' => ['timeout' => 300],
            'files' => ['compression_level' => 6]
        ];
        
        Config::set('backup', $testConfig);

        $config = $this->backupConfig->getAllConfig();

        $this->assertEquals($testConfig, $config);
    }
}