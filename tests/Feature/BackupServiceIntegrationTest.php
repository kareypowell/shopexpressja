<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\User;
use App\Services\BackupConfig;
use App\Services\BackupService;
use App\Services\DatabaseBackupHandler;
use App\Services\FileBackupHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BackupServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private BackupService $backupService;
    private string $testBackupPath;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user without using factory to avoid role constraint issues
        $this->testUser = new User([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id' => 1 // Assume role with ID 1 exists or will be created
        ]);
        
        // Create a basic role if it doesn't exist
        if (!\App\Models\Role::find(1)) {
            \App\Models\Role::create([
                'id' => 1,
                'name' => 'test_role',
                'description' => 'Test role for backup tests'
            ]);
        }
        
        $this->testUser->save();
        Auth::login($this->testUser);

        // Set up test backup path
        $this->testBackupPath = storage_path('testing/backups');
        Config::set('backup.storage.path', $this->testBackupPath);
        
        // Ensure test backup directory exists
        if (!File::exists($this->testBackupPath)) {
            File::makeDirectory($this->testBackupPath, 0755, true);
        }

        // Set up test directories for file backups
        $testDir1 = storage_path('testing/test-dir-1');
        $testDir2 = storage_path('testing/test-dir-2');
        
        File::makeDirectory($testDir1, 0755, true);
        File::makeDirectory($testDir2, 0755, true);
        
        // Create test files
        File::put($testDir1 . '/test1.txt', 'Test content 1');
        File::put($testDir2 . '/test2.txt', 'Test content 2');
        
        Config::set('backup.files.directories', [$testDir1, $testDir2]);
        Config::set('backup.schedule.retry_attempts', 1);
        Config::set('backup.schedule.retry_delay', 1);

        // Initialize service
        $this->backupService = app(BackupService::class);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->testBackupPath)) {
            File::deleteDirectory($this->testBackupPath);
        }
        
        $testDirs = [
            storage_path('testing/test-dir-1'),
            storage_path('testing/test-dir-2')
        ];
        
        foreach ($testDirs as $dir) {
            if (File::exists($dir)) {
                File::deleteDirectory($dir);
            }
        }

        parent::tearDown();
    }

    public function test_create_manual_database_backup_successfully()
    {
        $result = $this->backupService->createManualBackup([
            'type' => 'database',
            'name' => 'test_db_backup'
        ]);

        if (!$result->isSuccessful()) {
            $this->fail('Backup failed: ' . $result->getMessage());
        }
        
        $this->assertTrue($result->isSuccessful());
        $this->assertStringContains('Backup completed successfully', $result->getMessage());
        $this->assertNotNull($result->getBackup());
        
        $backup = $result->getBackup();
        $this->assertEquals('database', $backup->type);
        $this->assertEquals('completed', $backup->status);
        $this->assertEquals($this->testUser->id, $backup->created_by);
        $this->assertNotNull($backup->completed_at);
        $this->assertGreaterThan(0, $backup->file_size);
    }

    public function test_create_manual_files_backup_successfully()
    {
        $result = $this->backupService->createManualBackup([
            'type' => 'files',
            'name' => 'test_files_backup'
        ]);

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContains('Backup completed successfully', $result->getMessage());
        $this->assertNotNull($result->getBackup());
        
        $backup = $result->getBackup();
        $this->assertEquals('files', $backup->type);
        $this->assertEquals('completed', $backup->status);
        $this->assertEquals($this->testUser->id, $backup->created_by);
        $this->assertNotNull($backup->completed_at);
        $this->assertGreaterThan(0, $backup->file_size);
    }

    public function test_create_manual_full_backup_successfully()
    {
        $result = $this->backupService->createManualBackup([
            'type' => 'full',
            'name' => 'test_full_backup'
        ]);

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContains('Backup completed successfully', $result->getMessage());
        $this->assertNotNull($result->getBackup());
        
        $backup = $result->getBackup();
        $this->assertEquals('full', $backup->type);
        $this->assertEquals('completed', $backup->status);
        $this->assertEquals($this->testUser->id, $backup->created_by);
        $this->assertNotNull($backup->completed_at);
        $this->assertGreaterThan(0, $backup->file_size);
        
        // Verify both database and file backups were created
        $backupPaths = json_decode($backup->file_path, true);
        $this->assertArrayHasKey('database', $backupPaths);
        $this->assertArrayHasKey('files', $backupPaths);
    }

    public function test_create_backup_with_invalid_type_fails()
    {
        $result = $this->backupService->createManualBackup([
            'type' => 'invalid_type'
        ]);

        $this->assertFalse($result->isSuccessful());
        $this->assertStringContains('Invalid backup type', $result->getMessage());
    }

    public function test_create_backup_with_custom_options()
    {
        $result = $this->backupService->createManualBackup([
            'type' => 'database',
            'name' => 'custom_backup_name',
            'database' => true,
            'files' => false
        ]);

        $this->assertTrue($result->isSuccessful());
        
        $backup = $result->getBackup();
        $this->assertEquals('custom_backup_name', $backup->name);
        $this->assertTrue($backup->metadata['include_database']);
        $this->assertFalse($backup->metadata['include_files']);
        $this->assertTrue($backup->metadata['manual']);
    }

    public function test_backup_status_tracking_during_creation()
    {
        // Start backup creation
        $result = $this->backupService->createManualBackup([
            'type' => 'database'
        ]);

        $this->assertTrue($result->isSuccessful());
        
        $backup = $result->getBackup();
        
        // Verify backup was tracked in database
        $this->assertDatabaseHas('backups', [
            'id' => $backup->id,
            'status' => 'completed',
            'type' => 'database',
            'created_by' => $this->testUser->id
        ]);
    }

    public function test_get_backup_history()
    {
        // Create multiple backups
        Backup::factory()->count(3)->create([
            'status' => 'completed',
            'created_by' => $this->testUser->id
        ]);
        
        Backup::factory()->count(2)->create([
            'status' => 'failed',
            'created_by' => $this->testUser->id
        ]);

        $history = $this->backupService->getBackupHistory(10);

        $this->assertCount(5, $history);
        $this->assertTrue($history->first()->created_at >= $history->last()->created_at);
    }

    public function test_get_backup_history_with_limit()
    {
        // Create more backups than the limit
        Backup::factory()->count(10)->create([
            'created_by' => $this->testUser->id
        ]);

        $history = $this->backupService->getBackupHistory(5);

        $this->assertCount(5, $history);
    }

    public function test_get_backup_status()
    {
        // Create test backups
        Backup::factory()->count(3)->create([
            'status' => 'completed',
            'created_at' => now()->subDays(2),
            'created_by' => $this->testUser->id
        ]);
        
        Backup::factory()->count(1)->create([
            'status' => 'failed',
            'created_at' => now()->subDays(1),
            'created_by' => $this->testUser->id
        ]);
        
        Backup::factory()->count(1)->create([
            'status' => 'pending',
            'created_by' => $this->testUser->id
        ]);

        $status = $this->backupService->getBackupStatus();

        $this->assertEquals(5, $status->getTotalBackups());
        $this->assertEquals(4, $status->getRecentBackups()); // Last 7 days
        $this->assertEquals(3, $status->getSuccessfulBackups());
        $this->assertEquals(1, $status->getFailedBackups());
        $this->assertEquals(1, $status->getPendingBackups());
        $this->assertEquals(75.0, $status->getSuccessRate()); // 3/4 * 100
        $this->assertNotNull($status->getLastBackup());
    }

    public function test_validate_backup_integrity_single_file()
    {
        // Create a test backup
        $result = $this->backupService->createManualBackup([
            'type' => 'database'
        ]);

        $this->assertTrue($result->isSuccessful());
        
        $backup = $result->getBackup();
        $backupPaths = json_decode($backup->file_path, true);
        $dbBackupPath = $backupPaths['database'];

        $isValid = $this->backupService->validateBackupIntegrity($dbBackupPath);
        $this->assertTrue($isValid);
    }

    public function test_validate_backup_integrity_multiple_files()
    {
        // Create a full backup
        $result = $this->backupService->createManualBackup([
            'type' => 'full'
        ]);

        $this->assertTrue($result->isSuccessful());
        
        $backup = $result->getBackup();
        
        $isValid = $this->backupService->validateBackupIntegrity($backup->file_path);
        $this->assertTrue($isValid);
    }

    public function test_validate_backup_integrity_invalid_file()
    {
        $invalidPath = $this->testBackupPath . '/nonexistent.sql';
        
        $isValid = $this->backupService->validateBackupIntegrity($invalidPath);
        $this->assertFalse($isValid);
    }

    public function test_backup_error_handling_and_logging()
    {
        Log::spy();

        // Mock a failure scenario by setting invalid backup directories
        Config::set('backup.files.directories', ['/nonexistent/directory']);

        $result = $this->backupService->createManualBackup([
            'type' => 'files'
        ]);

        $this->assertFalse($result->isSuccessful());
        $this->assertStringContains('Backup failed', $result->getMessage());
        
        $backup = $result->getBackup();
        $this->assertEquals('failed', $backup->status);
        $this->assertArrayHasKey('error', $backup->metadata);

        // Verify error logging
        Log::shouldHaveReceived('error')
            ->with('Manual backup failed', \Mockery::type('array'))
            ->once();
    }

    public function test_backup_retry_logic_on_failure()
    {
        Log::spy();

        // Set retry attempts to 2 for this test
        Config::set('backup.schedule.retry_attempts', 2);
        Config::set('backup.schedule.retry_delay', 1);

        // Mock database handler to fail initially
        $mockDbHandler = $this->createMock(DatabaseBackupHandler::class);
        $mockDbHandler->expects($this->exactly(3)) // Initial + 2 retries
            ->method('createDump')
            ->willThrowException(new \Exception('Database connection failed'));

        $this->app->instance(DatabaseBackupHandler::class, $mockDbHandler);
        
        // Recreate service with mocked handler
        $this->backupService = app(BackupService::class);

        $result = $this->backupService->createManualBackup([
            'type' => 'database'
        ]);

        $this->assertFalse($result->isSuccessful());
        $this->assertStringContains('failed after 3 attempts', $result->getMessage());

        // Verify retry logging
        Log::shouldHaveReceived('warning')
            ->with('Database backup attempt failed', \Mockery::type('array'))
            ->times(3);
            
        Log::shouldHaveReceived('info')
            ->with('Retrying database backup', \Mockery::type('array'))
            ->times(2);
    }

    public function test_backup_metadata_tracking()
    {
        $customOptions = [
            'type' => 'full',
            'name' => 'metadata_test',
            'custom_field' => 'custom_value'
        ];

        $result = $this->backupService->createManualBackup($customOptions);

        $this->assertTrue($result->isSuccessful());
        
        $backup = $result->getBackup();
        $metadata = $backup->metadata;

        $this->assertTrue($metadata['manual']);
        $this->assertTrue($metadata['include_database']);
        $this->assertTrue($metadata['include_files']);
        $this->assertEquals($customOptions, $metadata['options']);
        $this->assertArrayHasKey('backup_paths', $metadata);
        $this->assertArrayHasKey('total_size', $metadata);
        $this->assertArrayHasKey('completed_at', $metadata);
    }

    public function test_storage_usage_calculation()
    {
        // Create some backup files
        $this->backupService->createManualBackup(['type' => 'database']);
        $this->backupService->createManualBackup(['type' => 'files']);

        $status = $this->backupService->getBackupStatus();
        $storageUsage = $status->getStorageUsage();

        $this->assertArrayHasKey('total_size', $storageUsage);
        $this->assertArrayHasKey('formatted_size', $storageUsage);
        $this->assertArrayHasKey('file_count', $storageUsage);
        $this->assertArrayHasKey('storage_path', $storageUsage);
        
        $this->assertGreaterThan(0, $storageUsage['total_size']);
        $this->assertGreaterThan(0, $storageUsage['file_count']);
        $this->assertEquals($this->testBackupPath, $storageUsage['storage_path']);
    }

    public function test_backup_service_integration_with_config()
    {
        // Test that service properly uses configuration
        Config::set('backup.schedule.retry_attempts', 3);
        Config::set('backup.schedule.retry_delay', 2);
        Config::set('backup.retention.database_days', 15);
        Config::set('backup.retention.files_days', 7);

        $status = $this->backupService->getBackupStatus();
        $retentionPolicy = $status->getRetentionPolicy();

        $this->assertEquals(15, $retentionPolicy['database_days']);
        $this->assertEquals(7, $retentionPolicy['files_days']);
    }

    public function test_concurrent_backup_handling()
    {
        // Create multiple backups simultaneously to test concurrency
        $results = [];
        
        for ($i = 0; $i < 3; $i++) {
            $results[] = $this->backupService->createManualBackup([
                'type' => 'database',
                'name' => "concurrent_backup_{$i}"
            ]);
        }

        // All backups should succeed
        foreach ($results as $result) {
            $this->assertTrue($result->isSuccessful());
        }

        // Verify all backups were created
        $this->assertEquals(3, Backup::where('name', 'like', 'concurrent_backup_%')->count());
    }
}