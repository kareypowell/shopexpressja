<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Services\BackupService;
use App\Services\BackupStatus;
use App\Services\BackupStorageManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Mockery;

class BackupStatusCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_displays_backup_system_status_successfully()
    {
        // Mock BackupStatus
        $mockStatus = Mockery::mock(BackupStatus::class);
        $mockStatus->shouldReceive('getLastBackupDate')->andReturn(now()->subHours(2));
        $mockStatus->shouldReceive('getLastSuccessfulBackupDate')->andReturn(now()->subHours(2));
        $mockStatus->shouldReceive('getTotalBackups')->andReturn(25);
        $mockStatus->shouldReceive('getRecentFailures')->andReturn(1);
        $mockStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockStatus->shouldReceive('getHealthIssues')->andReturn([]);

        // Mock BackupService
        $mockService = Mockery::mock(BackupService::class);
        $mockService->shouldReceive('getBackupStatus')->andReturn($mockStatus);
        
        // Create mock backup history
        $mockBackup = Mockery::mock(Backup::class);
        $mockBackup->created_at = now()->subHours(2);
        $mockBackup->name = 'test-backup';
        $mockBackup->type = 'database';
        $mockBackup->file_size = 1048576;
        $mockBackup->status = 'completed';
        
        $mockHistory = new Collection([$mockBackup]);
        $mockService->shouldReceive('getBackupHistory')->with(10)->andReturn($mockHistory);

        // Mock BackupStorageManager
        $mockStorageManager = Mockery::mock(BackupStorageManager::class);
        $storageInfo = [
            'path' => '/storage/app/backups',
            'total_files' => 15,
            'total_size' => 52428800, // 50MB
            'available_space' => 1073741824, // 1GB
            'disk_usage_percent' => 75.5,
            'retention' => [
                'database' => 30,
                'files' => 14
            ]
        ];
        $mockStorageManager->shouldReceive('getStorageInfo')->andReturn($storageInfo);

        $this->app->instance(BackupService::class, $mockService);
        $this->app->instance(BackupStorageManager::class, $mockStorageManager);

        // Run the command
        $exitCode = Artisan::call('backup:status');

        // Assert success
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Backup System Status', $output);
        $this->assertStringContainsString('System Status:', $output);
        $this->assertStringContainsString('Last Backup', $output);
        $this->assertStringContainsString('Total Backups', $output);
        $this->assertStringContainsString('25', $output);
        $this->assertStringContainsString('✓ Healthy', $output);
    }

    /** @test */
    public function it_displays_unhealthy_system_status_with_issues()
    {
        // Mock BackupStatus with health issues
        $mockStatus = Mockery::mock(BackupStatus::class);
        $mockStatus->shouldReceive('getLastBackupDate')->andReturn(now()->subDays(3));
        $mockStatus->shouldReceive('getLastSuccessfulBackupDate')->andReturn(now()->subDays(3));
        $mockStatus->shouldReceive('getTotalBackups')->andReturn(10);
        $mockStatus->shouldReceive('getRecentFailures')->andReturn(5);
        $mockStatus->shouldReceive('isHealthy')->andReturn(false);
        $mockStatus->shouldReceive('getHealthIssues')->andReturn([
            'Success rate is low (60%)',
            'Last backup was 3 days ago'
        ]);

        // Mock BackupService
        $mockService = Mockery::mock(BackupService::class);
        $mockService->shouldReceive('getBackupStatus')->andReturn($mockStatus);
        $mockService->shouldReceive('getBackupHistory')->with(10)->andReturn(new Collection());

        // Mock BackupStorageManager
        $mockStorageManager = Mockery::mock(BackupStorageManager::class);
        $storageInfo = [
            'path' => '/storage/app/backups',
            'total_files' => 5,
            'total_size' => 10485760,
            'available_space' => 1073741824,
            'disk_usage_percent' => 85.2,
            'retention' => ['database' => 30, 'files' => 14]
        ];
        $mockStorageManager->shouldReceive('getStorageInfo')->andReturn($storageInfo);

        $this->app->instance(BackupService::class, $mockService);
        $this->app->instance(BackupStorageManager::class, $mockStorageManager);

        // Run the command
        $exitCode = Artisan::call('backup:status');

        // Assert success but with health issues
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('✗ Issues Detected', $output);
        $this->assertStringContainsString('Health Issues:', $output);
        $this->assertStringContainsString('Success rate is low', $output);
        $this->assertStringContainsString('Last backup was 3 days ago', $output);
    }

    /** @test */
    public function it_displays_backup_history_correctly()
    {
        // Mock BackupStatus
        $mockStatus = Mockery::mock(BackupStatus::class);
        $mockStatus->shouldReceive('getLastBackupDate')->andReturn(now());
        $mockStatus->shouldReceive('getLastSuccessfulBackupDate')->andReturn(now());
        $mockStatus->shouldReceive('getTotalBackups')->andReturn(5);
        $mockStatus->shouldReceive('getRecentFailures')->andReturn(0);
        $mockStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockStatus->shouldReceive('getHealthIssues')->andReturn([]);

        // Mock BackupService
        $mockService = Mockery::mock(BackupService::class);
        $mockService->shouldReceive('getBackupStatus')->andReturn($mockStatus);
        
        // Create mock backup history with different statuses
        $mockBackups = collect([
            (object)[
                'created_at' => now()->subHours(1),
                'name' => 'recent-backup',
                'type' => 'database',
                'file_size' => 1048576,
                'status' => 'completed'
            ],
            (object)[
                'created_at' => now()->subHours(6),
                'name' => 'failed-backup',
                'type' => 'files',
                'file_size' => 2097152,
                'status' => 'failed'
            ],
            (object)[
                'created_at' => now()->subHours(12),
                'name' => 'pending-backup',
                'type' => 'full',
                'file_size' => null,
                'status' => 'pending'
            ]
        ]);
        
        $mockService->shouldReceive('getBackupHistory')->with(10)->andReturn($mockBackups);

        // Mock BackupStorageManager
        $mockStorageManager = Mockery::mock(BackupStorageManager::class);
        $storageInfo = [
            'path' => '/storage/app/backups',
            'total_files' => 3,
            'total_size' => 3145728,
            'available_space' => 1073741824,
            'disk_usage_percent' => 50.0,
            'retention' => ['database' => 30, 'files' => 14]
        ];
        $mockStorageManager->shouldReceive('getStorageInfo')->andReturn($storageInfo);

        $this->app->instance(BackupService::class, $mockService);
        $this->app->instance(BackupStorageManager::class, $mockStorageManager);

        // Run the command
        $exitCode = Artisan::call('backup:status');

        // Assert success and history display
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Recent Backup History:', $output);
        $this->assertStringContainsString('recent-backup', $output);
        $this->assertStringContainsString('failed-backup', $output);
        $this->assertStringContainsString('pending-backup', $output);
        $this->assertStringContainsString('✓ Completed', $output);
        $this->assertStringContainsString('✗ Failed', $output);
        $this->assertStringContainsString('⏳ Pending', $output);
        $this->assertStringContainsString('1 MB', $output);
        $this->assertStringContainsString('2 MB', $output);
    }

    /** @test */
    public function it_displays_empty_backup_history_message()
    {
        // Mock BackupStatus
        $mockStatus = Mockery::mock(BackupStatus::class);
        $mockStatus->shouldReceive('getLastBackupDate')->andReturn(null);
        $mockStatus->shouldReceive('getLastSuccessfulBackupDate')->andReturn(null);
        $mockStatus->shouldReceive('getTotalBackups')->andReturn(0);
        $mockStatus->shouldReceive('getRecentFailures')->andReturn(0);
        $mockStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockStatus->shouldReceive('getHealthIssues')->andReturn([]);

        // Mock BackupService
        $mockService = Mockery::mock(BackupService::class);
        $mockService->shouldReceive('getBackupStatus')->andReturn($mockStatus);
        $mockService->shouldReceive('getBackupHistory')->with(10)->andReturn(new Collection());

        // Mock BackupStorageManager
        $mockStorageManager = Mockery::mock(BackupStorageManager::class);
        $storageInfo = [
            'path' => '/storage/app/backups',
            'total_files' => 0,
            'total_size' => 0,
            'available_space' => 1073741824,
            'disk_usage_percent' => 0,
            'retention' => ['database' => 30, 'files' => 14]
        ];
        $mockStorageManager->shouldReceive('getStorageInfo')->andReturn($storageInfo);

        $this->app->instance(BackupService::class, $mockService);
        $this->app->instance(BackupStorageManager::class, $mockStorageManager);

        // Run the command
        $exitCode = Artisan::call('backup:status');

        // Assert success and empty message
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Recent Backup History:', $output);
        $this->assertStringContainsString('No backups found', $output);
        $this->assertStringContainsString('Never', $output); // For last backup dates
    }

    /** @test */
    public function it_displays_storage_information_correctly()
    {
        // Mock BackupStatus
        $mockStatus = Mockery::mock(BackupStatus::class);
        $mockStatus->shouldReceive('getLastBackupDate')->andReturn(now());
        $mockStatus->shouldReceive('getLastSuccessfulBackupDate')->andReturn(now());
        $mockStatus->shouldReceive('getTotalBackups')->andReturn(10);
        $mockStatus->shouldReceive('getRecentFailures')->andReturn(0);
        $mockStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockStatus->shouldReceive('getHealthIssues')->andReturn([]);

        // Mock BackupService
        $mockService = Mockery::mock(BackupService::class);
        $mockService->shouldReceive('getBackupStatus')->andReturn($mockStatus);
        $mockService->shouldReceive('getBackupHistory')->with(10)->andReturn(new Collection());

        // Mock BackupStorageManager
        $mockStorageManager = Mockery::mock(BackupStorageManager::class);
        $storageInfo = [
            'path' => '/storage/app/backups',
            'total_files' => 20,
            'total_size' => 104857600, // 100MB
            'available_space' => 2147483648, // 2GB
            'disk_usage_percent' => 85.5,
            'retention' => [
                'database' => 30,
                'files' => 14
            ]
        ];
        $mockStorageManager->shouldReceive('getStorageInfo')->andReturn($storageInfo);

        $this->app->instance(BackupService::class, $mockService);
        $this->app->instance(BackupStorageManager::class, $mockStorageManager);

        // Run the command
        $exitCode = Artisan::call('backup:status');

        // Assert success and storage info
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Storage Information:', $output);
        $this->assertStringContainsString('Backup Directory', $output);
        $this->assertStringContainsString('/storage/app/backups', $output);
        $this->assertStringContainsString('Total Backup Files', $output);
        $this->assertStringContainsString('20', $output);
        $this->assertStringContainsString('Total Storage Used', $output);
        $this->assertStringContainsString('100 MB', $output);
        $this->assertStringContainsString('Available Disk Space', $output);
        $this->assertStringContainsString('2 GB', $output);
        $this->assertStringContainsString('Disk Usage', $output);
        $this->assertStringContainsString('85.5%', $output);
        $this->assertStringContainsString('⚠ Warning: Disk usage is high', $output);
    }

    /** @test */
    public function it_displays_retention_policy_information()
    {
        // Mock BackupStatus
        $mockStatus = Mockery::mock(BackupStatus::class);
        $mockStatus->shouldReceive('getLastBackupDate')->andReturn(now());
        $mockStatus->shouldReceive('getLastSuccessfulBackupDate')->andReturn(now());
        $mockStatus->shouldReceive('getTotalBackups')->andReturn(5);
        $mockStatus->shouldReceive('getRecentFailures')->andReturn(0);
        $mockStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockStatus->shouldReceive('getHealthIssues')->andReturn([]);

        // Mock BackupService
        $mockService = Mockery::mock(BackupService::class);
        $mockService->shouldReceive('getBackupStatus')->andReturn($mockStatus);
        $mockService->shouldReceive('getBackupHistory')->with(10)->andReturn(new Collection());

        // Mock BackupStorageManager
        $mockStorageManager = Mockery::mock(BackupStorageManager::class);
        $storageInfo = [
            'path' => '/storage/app/backups',
            'total_files' => 5,
            'total_size' => 10485760,
            'available_space' => 1073741824,
            'disk_usage_percent' => 50.0,
            'retention' => [
                'database' => 45,
                'files' => 21
            ]
        ];
        $mockStorageManager->shouldReceive('getStorageInfo')->andReturn($storageInfo);

        $this->app->instance(BackupService::class, $mockService);
        $this->app->instance(BackupStorageManager::class, $mockStorageManager);

        // Run the command
        $exitCode = Artisan::call('backup:status');

        // Assert success and retention policy display
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Retention Policy:', $output);
        $this->assertStringContainsString('Database Backups', $output);
        $this->assertStringContainsString('45 days', $output);
        $this->assertStringContainsString('File Backups', $output);
        $this->assertStringContainsString('21 days', $output);
    }

    /** @test */
    public function it_handles_service_exception()
    {
        // Mock BackupService to throw exception
        $mockService = Mockery::mock(BackupService::class);
        $mockService->shouldReceive('getBackupStatus')
            ->andThrow(new \Exception('Service unavailable'));

        $this->app->instance(BackupService::class, $mockService);

        // Run the command
        $exitCode = Artisan::call('backup:status');

        // Assert failure
        $this->assertEquals(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Failed to retrieve backup status: Service unavailable', $output);
    }

    /** @test */
    public function it_formats_file_sizes_correctly_in_output()
    {
        // Test different file sizes
        $testCases = [
            ['size' => 512, 'expected' => '512 B'],
            ['size' => 1024, 'expected' => '1 KB'],
            ['size' => 1048576, 'expected' => '1 MB'],
            ['size' => 1073741824, 'expected' => '1 GB'],
        ];

        foreach ($testCases as $testCase) {
            // Mock BackupStatus
            $mockStatus = Mockery::mock(BackupStatus::class);
            $mockStatus->shouldReceive('getLastBackupDate')->andReturn(now());
            $mockStatus->shouldReceive('getLastSuccessfulBackupDate')->andReturn(now());
            $mockStatus->shouldReceive('getTotalBackups')->andReturn(1);
            $mockStatus->shouldReceive('getRecentFailures')->andReturn(0);
            $mockStatus->shouldReceive('isHealthy')->andReturn(true);
            $mockStatus->shouldReceive('getHealthIssues')->andReturn([]);

            // Mock BackupService
            $mockService = Mockery::mock(BackupService::class);
            $mockService->shouldReceive('getBackupStatus')->andReturn($mockStatus);
            $mockService->shouldReceive('getBackupHistory')->with(10)->andReturn(new Collection());

            // Mock BackupStorageManager
            $mockStorageManager = Mockery::mock(BackupStorageManager::class);
            $storageInfo = [
                'path' => '/storage/app/backups',
                'total_files' => 1,
                'total_size' => $testCase['size'],
                'available_space' => 1073741824,
                'disk_usage_percent' => 50.0,
                'retention' => ['database' => 30, 'files' => 14]
            ];
            $mockStorageManager->shouldReceive('getStorageInfo')->andReturn($storageInfo);

            $this->app->instance(BackupService::class, $mockService);
            $this->app->instance(BackupStorageManager::class, $mockStorageManager);

            // Run the command
            Artisan::call('backup:status');
            $output = Artisan::output();
            
            // Assert formatted size appears in output
            $this->assertStringContainsString($testCase['expected'], $output);
        }
    }
}