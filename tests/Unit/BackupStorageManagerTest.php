<?php

namespace Tests\Unit;

use App\Models\Backup;
use App\Services\BackupConfig;
use App\Services\BackupStorageManager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BackupStorageManagerTest extends TestCase
{
    use RefreshDatabase;

    private BackupStorageManager $storageManager;
    private BackupConfig $config;
    private string $testStoragePath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testStoragePath = storage_path('testing/backup-storage-test');
        
        // Clean up any existing test files first
        if (File::exists($this->testStoragePath)) {
            File::deleteDirectory($this->testStoragePath);
        }
        
        // Mock the storage path for testing
        config(['backup.storage.path' => $this->testStoragePath]);
        
        $this->config = new BackupConfig();
        $this->storageManager = new BackupStorageManager($this->config);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->testStoragePath)) {
            File::deleteDirectory($this->testStoragePath);
        }
        
        parent::tearDown();
    }

    public function test_constructor_creates_storage_directories()
    {
        $this->assertTrue(File::exists($this->testStoragePath));
        $this->assertTrue(File::exists($this->testStoragePath . '/database'));
        $this->assertTrue(File::exists($this->testStoragePath . '/files'));
    }

    public function test_organize_backup_file_moves_file_to_organized_structure()
    {
        // Create a test file in the root storage directory
        if (!File::exists($this->testStoragePath)) {
            File::makeDirectory($this->testStoragePath, 0755, true);
        }
        $tempFile = $this->testStoragePath . '/test-backup.sql';
        File::put($tempFile, 'test backup content');
        
        $organizedPath = $this->storageManager->organizeBackupFile($tempFile, 'database');
        
        $expectedDate = Carbon::now()->format('Y/m/d');
        $expectedPath = $this->testStoragePath . "/database/{$expectedDate}/test-backup.sql";
        
        $this->assertEquals($expectedPath, $organizedPath);
        $this->assertTrue(File::exists($organizedPath));
        $this->assertFalse(File::exists($tempFile));
        $this->assertEquals('test backup content', File::get($organizedPath));
    }

    public function test_organize_backup_file_handles_nonexistent_file()
    {
        $nonexistentFile = $this->testStoragePath . '/nonexistent.sql';
        
        $organizedPath = $this->storageManager->organizeBackupFile($nonexistentFile, 'database');
        
        $expectedDate = Carbon::now()->format('Y/m/d');
        $expectedPath = $this->testStoragePath . "/database/{$expectedDate}/nonexistent.sql";
        
        $this->assertEquals($expectedPath, $organizedPath);
        $this->assertFalse(File::exists($organizedPath));
    }

    public function test_enforce_retention_policy_removes_old_backups()
    {
        // Set short retention period for testing
        config(['backup.retention.database_days' => 5]);
        config(['backup.retention.min_backups_to_keep' => 1]); // Keep at least 1
        
        // Create old backup record and file
        $oldDate = Carbon::now()->subDays(10);
        $oldBackupPath = $this->testStoragePath . '/database/old-backup.sql';
        if (!File::exists(dirname($oldBackupPath))) {
            File::makeDirectory(dirname($oldBackupPath), 0755, true);
        }
        File::put($oldBackupPath, 'old backup content');
        
        $oldBackup = new Backup([
            'name' => 'old-backup',
            'type' => 'database',
            'file_path' => $oldBackupPath,
            'file_size' => strlen('old backup content'),
            'status' => 'completed',
        ]);
        $oldBackup->timestamps = false;
        $oldBackup->created_at = $oldDate;
        $oldBackup->updated_at = $oldDate;
        $oldBackup->save();
        
        // Create recent backup record and file
        $recentDate = Carbon::now()->subDays(2);
        $recentBackupPath = $this->testStoragePath . '/database/recent-backup.sql';
        File::put($recentBackupPath, 'recent backup content');
        
        $recentBackup = new Backup([
            'name' => 'recent-backup',
            'type' => 'database',
            'file_path' => $recentBackupPath,
            'file_size' => strlen('recent backup content'),
            'status' => 'completed',
        ]);
        $recentBackup->timestamps = false;
        $recentBackup->created_at = $recentDate;
        $recentBackup->updated_at = $recentDate;
        $recentBackup->save();
        
        $results = $this->storageManager->enforceRetentionPolicy();
        
        // Check results
        $this->assertEquals(1, $results['database']['removed']);
        $this->assertEquals(strlen('old backup content'), $results['database']['freed_space']);
        $this->assertEmpty($results['database']['errors']);
        
        // Check files
        $this->assertFalse(File::exists($oldBackupPath));
        $this->assertTrue(File::exists($recentBackupPath));
        
        // Check database records
        $oldBackup->refresh();
        $recentBackup->refresh();
        $this->assertEquals('cleaned_up', $oldBackup->status);
        $this->assertEquals('completed', $recentBackup->status);
    }

    public function test_enforce_retention_policy_handles_missing_files()
    {
        config(['backup.retention.database_days' => 5]);
        config(['backup.retention.min_backups_to_keep' => 0]); // Allow all to be cleaned up
        
        // Create old backup record without file
        $oldDate = Carbon::now()->subDays(10);
        $oldBackup = new Backup([
            'name' => 'missing-file-backup',
            'type' => 'database',
            'file_path' => $this->testStoragePath . '/database/missing.sql',
            'file_size' => 1000,
            'status' => 'completed',
        ]);
        $oldBackup->timestamps = false;
        $oldBackup->created_at = $oldDate;
        $oldBackup->updated_at = $oldDate;
        $oldBackup->save();
        
        $results = $this->storageManager->enforceRetentionPolicy();
        
        // Should mark as cleaned up even though file doesn't exist
        $oldBackup->refresh();
        $this->assertEquals('cleaned_up', $oldBackup->status);
        $this->assertEquals(1, $results['database']['removed']);
        $this->assertEquals(0, $results['database']['freed_space']);
    }

    public function test_get_storage_usage_calculates_correctly()
    {
        // Create test files
        $dbFile1 = $this->testStoragePath . '/database/backup1.sql';
        $dbFile2 = $this->testStoragePath . '/database/backup2.sql';
        $fileBackup = $this->testStoragePath . '/files/files-backup.zip';
        
        if (!File::exists(dirname($dbFile1))) {
            File::makeDirectory(dirname($dbFile1), 0755, true);
        }
        if (!File::exists(dirname($fileBackup))) {
            File::makeDirectory(dirname($fileBackup), 0755, true);
        }
        
        File::put($dbFile1, str_repeat('a', 1000)); // 1KB
        File::put($dbFile2, str_repeat('b', 2000)); // 2KB
        File::put($fileBackup, str_repeat('c', 3000)); // 3KB
        
        $usage = $this->storageManager->getStorageUsage();
        
        $this->assertEquals(6000, $usage['total_size']);
        $this->assertEquals(3, $usage['total_files']);
        $this->assertEquals('5.86 KB', $usage['formatted_total_size']);
        
        $this->assertEquals(3000, $usage['breakdown']['database']['size']);
        $this->assertEquals(2, $usage['breakdown']['database']['count']);
        $this->assertEquals('2.93 KB', $usage['breakdown']['database']['formatted_size']);
        
        $this->assertEquals(3000, $usage['breakdown']['files']['size']);
        $this->assertEquals(1, $usage['breakdown']['files']['count']);
        $this->assertEquals('2.93 KB', $usage['breakdown']['files']['formatted_size']);
    }

    public function test_get_storage_usage_handles_empty_directories()
    {
        $usage = $this->storageManager->getStorageUsage();
        
        $this->assertEquals(0, $usage['total_size']);
        $this->assertEquals(0, $usage['total_files']);
        $this->assertEquals('0 B', $usage['formatted_total_size']);
        
        $this->assertEquals(0, $usage['breakdown']['database']['size']);
        $this->assertEquals(0, $usage['breakdown']['database']['count']);
        
        $this->assertEquals(0, $usage['breakdown']['files']['size']);
        $this->assertEquals(0, $usage['breakdown']['files']['count']);
    }

    public function test_check_storage_warnings_detects_exceeded_storage()
    {
        config(['backup.storage.max_storage_size' => 1]); // 1MB limit
        
        // Create file larger than limit
        $largeFile = $this->testStoragePath . '/database/large-backup.sql';
        if (!File::exists(dirname($largeFile))) {
            File::makeDirectory(dirname($largeFile), 0755, true);
        }
        File::put($largeFile, str_repeat('x', 2 * 1024 * 1024)); // 2MB
        
        $warnings = $this->storageManager->checkStorageWarnings();
        
        $this->assertCount(1, $warnings);
        $this->assertEquals('storage_exceeded', $warnings[0]['type']);
        $this->assertStringContainsString('exceeds maximum allowed size', $warnings[0]['message']);
    }

    public function test_check_storage_warnings_detects_warning_threshold()
    {
        config(['backup.storage.max_storage_size' => 10]); // 10MB limit
        
        // Create file at 85% of limit (above 80% warning threshold)
        $largeFile = $this->testStoragePath . '/database/large-backup.sql';
        if (!File::exists(dirname($largeFile))) {
            File::makeDirectory(dirname($largeFile), 0755, true);
        }
        File::put($largeFile, str_repeat('x', 9 * 1024 * 1024)); // 9MB (90% of 10MB)
        
        $warnings = $this->storageManager->checkStorageWarnings();
        
        $this->assertCount(1, $warnings);
        $this->assertEquals('storage_warning', $warnings[0]['type']);
        $this->assertStringContainsString('approaching maximum capacity', $warnings[0]['message']);
    }

    public function test_check_storage_warnings_returns_empty_when_no_limit()
    {
        config(['backup.storage.max_storage_size' => 0]); // No limit
        
        // Create any size file
        $file = $this->testStoragePath . '/database/backup.sql';
        if (!File::exists(dirname($file))) {
            File::makeDirectory(dirname($file), 0755, true);
        }
        File::put($file, str_repeat('x', 1024 * 1024)); // 1MB
        
        $warnings = $this->storageManager->checkStorageWarnings();
        
        $this->assertEmpty($warnings);
    }

    public function test_has_enough_space_checks_available_disk_space()
    {
        // This test depends on actual disk space, so we'll mock it
        $requiredBytes = 1000;
        
        // Should return true for small requirements on most systems
        $hasSpace = $this->storageManager->hasEnoughSpace($requiredBytes);
        $this->assertTrue($hasSpace);
    }

    public function test_cleanup_orphaned_files_removes_files_without_database_records()
    {
        // Create orphaned file (no database record)
        $orphanedFile = $this->testStoragePath . '/database/orphaned-backup.sql';
        if (!File::exists(dirname($orphanedFile))) {
            File::makeDirectory(dirname($orphanedFile), 0755, true);
        }
        File::put($orphanedFile, 'orphaned content');
        
        // Create file with database record
        $validFile = $this->testStoragePath . '/database/valid-backup.sql';
        File::put($validFile, 'valid content');
        
        Backup::create([
            'name' => 'valid-backup',
            'type' => 'database',
            'file_path' => $validFile,
            'file_size' => strlen('valid content'),
            'status' => 'completed',
        ]);
        
        $results = $this->storageManager->cleanupOrphanedFiles();
        
        $this->assertEquals(1, $results['removed']);
        $this->assertEquals(strlen('orphaned content'), $results['freed_space']);
        $this->assertEmpty($results['errors']);
        
        $this->assertFalse(File::exists($orphanedFile));
        $this->assertTrue(File::exists($validFile));
    }

    public function test_cleanup_orphaned_files_handles_errors_gracefully()
    {
        Log::shouldReceive('info')->andReturn(true);
        Log::shouldReceive('error')->andReturn(true);
        
        // Create a file in a directory that we'll make read-only
        $protectedDir = $this->testStoragePath . '/database/protected';
        File::makeDirectory($protectedDir, 0755, true);
        $protectedFile = $protectedDir . '/protected-file.sql';
        File::put($protectedFile, 'protected content');
        
        // Make directory read-only (this might not work on all systems)
        chmod($protectedDir, 0444);
        
        $results = $this->storageManager->cleanupOrphanedFiles();
        
        // Restore permissions for cleanup
        chmod($protectedDir, 0755);
        
        // The test might succeed or fail depending on the system
        // We mainly want to ensure it doesn't crash
        $this->assertIsArray($results);
        $this->assertArrayHasKey('removed', $results);
        $this->assertArrayHasKey('freed_space', $results);
        $this->assertArrayHasKey('errors', $results);
    }

    public function test_format_bytes_converts_correctly()
    {
        $reflection = new \ReflectionClass($this->storageManager);
        $method = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);
        
        $this->assertEquals('0 B', $method->invoke($this->storageManager, 0));
        $this->assertEquals('1 B', $method->invoke($this->storageManager, 1));
        $this->assertEquals('1 KB', $method->invoke($this->storageManager, 1024));
        $this->assertEquals('1 MB', $method->invoke($this->storageManager, 1024 * 1024));
        $this->assertEquals('1 GB', $method->invoke($this->storageManager, 1024 * 1024 * 1024));
        $this->assertEquals('1.5 KB', $method->invoke($this->storageManager, 1536));
    }
}