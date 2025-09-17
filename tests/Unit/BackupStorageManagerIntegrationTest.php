<?php

namespace Tests\Unit;

use App\Models\Backup;
use App\Services\BackupConfig;
use App\Services\BackupService;
use App\Services\BackupStorageManager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupStorageManagerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private BackupStorageManager $storageManager;
    private BackupService $backupService;
    private string $testStoragePath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testStoragePath = storage_path('testing/backup-integration-test');
        
        // Configure test environment
        config([
            'backup.storage.path' => $this->testStoragePath,
            'backup.retention.database_days' => 7,
            'backup.retention.files_days' => 3,
            'backup.storage.max_storage_size' => 50, // 50MB for testing
        ]);
        
        $config = new BackupConfig();
        $this->storageManager = new BackupStorageManager($config);
        $this->backupService = app(BackupService::class);
        
        // Clean up any existing test files
        if (File::exists($this->testStoragePath)) {
            File::deleteDirectory($this->testStoragePath);
        }
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testStoragePath)) {
            File::deleteDirectory($this->testStoragePath);
        }
        
        parent::tearDown();
    }

    public function test_storage_manager_integrates_with_backup_workflow()
    {
        // Set minimum backups to keep to 0 for this test
        config(['backup.retention.min_backups_to_keep' => 0]);
        
        // Create some test backup records with files
        $this->createTestBackups();
        
        // Check initial storage usage
        $initialUsage = $this->storageManager->getStorageUsage();
        $this->assertGreaterThan(0, $initialUsage['total_size']);
        $this->assertGreaterThan(0, $initialUsage['total_files']);
        
        // Test retention policy enforcement
        $retentionResults = $this->storageManager->enforceRetentionPolicy();
        
        // Should have removed old backups (database retention is 7 days, files is 3 days)
        // We created backups 10 days old (database) and 5 days old (files), so both should be removed
        $this->assertGreaterThan(0, $retentionResults['database']['removed']);
        $this->assertGreaterThan(0, $retentionResults['files']['removed']);
        
        // Check storage usage after cleanup
        $finalUsage = $this->storageManager->getStorageUsage();
        $this->assertLessThan($initialUsage['total_size'], $finalUsage['total_size']);
    }

    public function test_storage_warnings_work_with_real_backups()
    {
        // Set a very low storage limit
        config(['backup.storage.max_storage_size' => 1]); // 1MB
        
        // Create backups that exceed the limit
        $this->createLargeTestBackups();
        
        $warnings = $this->storageManager->checkStorageWarnings();
        
        $this->assertNotEmpty($warnings);
        $this->assertEquals('storage_exceeded', $warnings[0]['type']);
    }

    public function test_orphaned_file_cleanup_with_mixed_scenarios()
    {
        // Create valid backup with database record
        $validBackup = $this->createBackupWithFile('valid-backup.sql', 'database', 'valid content');
        
        // Create orphaned file (no database record)
        $orphanedFile = $this->testStoragePath . '/database/orphaned.sql';
        File::put($orphanedFile, 'orphaned content');
        
        // Create backup record without file
        Backup::create([
            'name' => 'missing-file-backup',
            'type' => 'database',
            'file_path' => $this->testStoragePath . '/database/missing.sql',
            'file_size' => 1000,
            'status' => 'completed',
        ]);
        
        $results = $this->storageManager->cleanupOrphanedFiles();
        
        // Should remove orphaned file but keep valid file
        $this->assertEquals(1, $results['removed']);
        $this->assertFalse(File::exists($orphanedFile));
        $this->assertTrue(File::exists($validBackup->file_path));
    }

    public function test_storage_organization_maintains_structure()
    {
        // Create a backup file in root storage
        $tempFile = $this->testStoragePath . '/temp-backup.sql';
        if (!File::exists($this->testStoragePath)) {
            File::makeDirectory($this->testStoragePath, 0755, true);
        }
        File::put($tempFile, 'test content');
        
        // Organize it
        $organizedPath = $this->storageManager->organizeBackupFile($tempFile, 'database');
        
        // Verify it's in the correct date-based structure
        $expectedDate = Carbon::now()->format('Y/m/d');
        $this->assertStringContainsString("/database/{$expectedDate}/", $organizedPath);
        $this->assertTrue(File::exists($organizedPath));
        
        // Test storage usage calculation includes organized files
        $usage = $this->storageManager->getStorageUsage();
        $this->assertGreaterThan(0, $usage['breakdown']['database']['size']);
    }

    public function test_retention_policy_respects_minimum_backups()
    {
        config(['backup.retention.min_backups_to_keep' => 2]);
        config(['backup.retention.database_days' => 1]); // Very short retention
        
        // Create 3 old backups (all older than retention period)
        for ($i = 1; $i <= 3; $i++) {
            $this->createBackupWithFile(
                "old-backup-{$i}.sql",
                'database',
                "content {$i}",
                Carbon::now()->subDays(5)
            );
        }
        
        $retentionResults = $this->storageManager->enforceRetentionPolicy();
        
        // Should only remove 1 backup (keeping minimum of 2)
        $this->assertEquals(1, $retentionResults['database']['removed']);
        
        // Verify 2 backups still exist
        $remainingBackups = Backup::where('type', 'database')
            ->where('status', 'completed')
            ->count();
        $this->assertEquals(2, $remainingBackups);
    }

    public function test_concurrent_storage_operations()
    {
        // Create multiple backup files simultaneously
        $files = [];
        if (!File::exists($this->testStoragePath)) {
            File::makeDirectory($this->testStoragePath, 0755, true);
        }
        for ($i = 1; $i <= 5; $i++) {
            $file = $this->testStoragePath . "/concurrent-backup-{$i}.sql";
            File::put($file, "concurrent content {$i}");
            $files[] = $file;
        }
        
        // Organize all files
        $organizedPaths = [];
        foreach ($files as $file) {
            $organizedPaths[] = $this->storageManager->organizeBackupFile($file, 'database');
        }
        
        // Verify all files were organized correctly
        foreach ($organizedPaths as $path) {
            $this->assertTrue(File::exists($path));
        }
        
        // Check storage usage reflects all files
        $usage = $this->storageManager->getStorageUsage();
        $this->assertEquals(5, $usage['breakdown']['database']['count']);
    }

    private function createTestBackups(): void
    {
        // Create recent database backup
        $this->createBackupWithFile(
            'recent-db-backup.sql',
            'database',
            'recent database content',
            Carbon::now()->subDays(2)
        );
        
        // Create old database backup (should be cleaned up)
        $this->createBackupWithFile(
            'old-db-backup.sql',
            'database',
            'old database content',
            Carbon::now()->subDays(10)
        );
        
        // Create recent file backup
        $this->createBackupWithFile(
            'recent-files-backup.zip',
            'files',
            'recent files content',
            Carbon::now()->subDays(1)
        );
        
        // Create old file backup (should be cleaned up)
        $this->createBackupWithFile(
            'old-files-backup.zip',
            'files',
            'old files content',
            Carbon::now()->subDays(5)
        );
    }

    private function createLargeTestBackups(): void
    {
        // Create backups that total more than 1MB
        $this->createBackupWithFile(
            'large-backup-1.sql',
            'database',
            str_repeat('x', 600 * 1024), // 600KB
            Carbon::now()->subDays(1)
        );
        
        $this->createBackupWithFile(
            'large-backup-2.sql',
            'database',
            str_repeat('y', 600 * 1024), // 600KB
            Carbon::now()->subDays(2)
        );
    }

    private function createBackupWithFile(string $filename, string $type, string $content, Carbon $createdAt = null): Backup
    {
        $filePath = $this->testStoragePath . "/{$type}/{$filename}";
        
        // Ensure directory exists
        if (!File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }
        File::put($filePath, $content);
        
        $backup = new Backup([
            'name' => pathinfo($filename, PATHINFO_FILENAME),
            'type' => $type,
            'file_path' => $filePath,
            'file_size' => strlen($content),
            'status' => 'completed',
        ]);
        
        if ($createdAt) {
            $backup->timestamps = false;
            $backup->created_at = $createdAt;
            $backup->updated_at = $createdAt;
        }
        
        $backup->save();
        return $backup;
    }
}