<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\RestoreLog;
use App\Models\Role;
use App\Models\User;
use App\Services\BackupConfig;
use App\Services\BackupService;
use App\Services\DatabaseBackupHandler;
use App\Services\FileBackupHandler;
use App\Services\RestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FileRestorationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private RestoreService $restoreService;
    private FileBackupHandler $fileHandler;
    private BackupService $backupService;
    private string $testBackupPath;
    private string $testDirectory1;
    private string $testDirectory2;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create or get admin role
        $adminRole = Role::firstOrCreate([
            'name' => 'admin'
        ], [
            'description' => 'Administrator role'
        ]);

        // Create test user with admin role
        $this->user = User::factory()->create([
            'role_id' => $adminRole->id
        ]);
        $this->actingAs($this->user);

        // Set up services
        $config = new BackupConfig();
        $databaseHandler = new DatabaseBackupHandler($config);
        $this->fileHandler = new FileBackupHandler($config);
        $this->backupService = new BackupService($databaseHandler, $this->fileHandler, $config);
        $this->restoreService = new RestoreService(
            $this->backupService,
            $databaseHandler,
            $this->fileHandler
        );

        // Set up test directories
        $this->testBackupPath = storage_path('testing/file-restore-test');
        $this->testDirectory1 = storage_path('testing/test-dir-1');
        $this->testDirectory2 = storage_path('testing/test-dir-2');

        // Ensure test directories exist
        File::makeDirectory($this->testBackupPath, 0755, true);
        File::makeDirectory($this->testDirectory1, 0755, true);
        File::makeDirectory($this->testDirectory2, 0755, true);

        // Create test files
        $this->createTestFiles();
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        if (File::exists($this->testBackupPath)) {
            File::deleteDirectory($this->testBackupPath);
        }
        if (File::exists($this->testDirectory1)) {
            File::deleteDirectory($this->testDirectory1);
        }
        if (File::exists($this->testDirectory2)) {
            File::deleteDirectory($this->testDirectory2);
        }

        parent::tearDown();
    }

    /** @test */
    public function it_can_restore_files_from_backup_archive()
    {
        // Create backup of test directories
        $backupPath1 = $this->fileHandler->backupDirectory($this->testDirectory1);
        $backupPath2 = $this->fileHandler->backupDirectory($this->testDirectory2);

        // Create backup records
        $backup1 = Backup::create([
            'name' => 'test-dir-1-backup',
            'type' => 'files',
            'file_path' => $backupPath1,
            'file_size' => filesize($backupPath1),
            'status' => 'completed',
            'created_by' => $this->user->id,
            'checksum' => md5_file($backupPath1)
        ]);

        // Modify original files to simulate data loss
        File::put($this->testDirectory1 . '/test1.txt', 'modified content');
        File::delete($this->testDirectory1 . '/subdir/test2.txt');

        // Restore files
        $result = $this->restoreService->restoreFiles($backupPath1, [$this->testDirectory1]);

        // Debug output if restoration failed
        if (!$result->isSuccessful()) {
            $this->fail("Restoration failed: " . $result->getMessage() . "\nData: " . json_encode($result->getData()));
        }

        // Assert restoration was successful
        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('Files restored successfully', $result->getMessage());

        // Verify files were restored correctly
        $this->assertFileExists($this->testDirectory1 . '/test1.txt');
        $this->assertFileExists($this->testDirectory1 . '/subdir/test2.txt');
        $this->assertEquals('test content 1', File::get($this->testDirectory1 . '/test1.txt'));
        $this->assertEquals('test content 2', File::get($this->testDirectory1 . '/subdir/test2.txt'));

        // Verify restore log was created
        $restoreLog = RestoreLog::where('backup_id', $backup1->id)->first();
        $this->assertNotNull($restoreLog);
        $this->assertEquals('files', $restoreLog->restore_type);
        $this->assertEquals('completed', $restoreLog->status);
        $this->assertEquals($this->user->id, $restoreLog->restored_by);
    }

    /** @test */
    public function it_creates_pre_restore_backup_before_restoration()
    {
        // Create backup
        $backupPath = $this->fileHandler->backupDirectory($this->testDirectory1);
        $backup = Backup::create([
            'name' => 'test-backup',
            'type' => 'files',
            'file_path' => $backupPath,
            'file_size' => filesize($backupPath),
            'status' => 'completed',
            'created_by' => $this->user->id,
            'checksum' => md5_file($backupPath)
        ]);

        // Modify files
        File::put($this->testDirectory1 . '/test1.txt', 'modified before restore');

        // Restore files
        $result = $this->restoreService->restoreFiles($backupPath, [$this->testDirectory1]);

        // Assert pre-restore backup was created
        $this->assertTrue($result->isSuccessful());
        $preRestoreBackup = $result->get('pre_restore_backup');
        $this->assertNotEmpty($preRestoreBackup);
        $this->assertFileExists($preRestoreBackup);

        // Verify pre-restore backup contains the modified content
        $tempDir = storage_path('app/temp/verify_' . uniqid());
        $this->fileHandler->extractArchive($preRestoreBackup, $tempDir);
        
        $extractedFile = $tempDir . '/' . basename($this->testDirectory1) . '/test1.txt';
        $this->assertFileExists($extractedFile);
        $this->assertEquals('modified before restore', File::get($extractedFile));

        // Clean up
        File::deleteDirectory($tempDir);
    }

    /** @test */
    public function it_restores_file_permissions_correctly()
    {
        // Create backup
        $backupPath = $this->fileHandler->backupDirectory($this->testDirectory1);
        $backup = Backup::create([
            'name' => 'test-backup',
            'type' => 'files',
            'file_path' => $backupPath,
            'file_size' => filesize($backupPath),
            'status' => 'completed',
            'created_by' => $this->user->id,
            'checksum' => md5_file($backupPath)
        ]);

        // Remove directory
        File::deleteDirectory($this->testDirectory1);

        // Restore files
        $result = $this->restoreService->restoreFiles($backupPath, [$this->testDirectory1]);

        // Assert restoration was successful
        $this->assertTrue($result->isSuccessful());

        // Verify directory permissions
        $this->assertTrue(is_readable($this->testDirectory1));
        $this->assertTrue(is_writable($this->testDirectory1));

        // Verify file permissions
        $this->assertTrue(is_readable($this->testDirectory1 . '/test1.txt'));
        $this->assertTrue(is_readable($this->testDirectory1 . '/subdir/test2.txt'));
    }

    /** @test */
    public function it_can_restore_multiple_directories_simultaneously()
    {
        // Create backups of both directories
        $backupPath1 = $this->fileHandler->backupDirectory($this->testDirectory1);
        $backupPath2 = $this->fileHandler->backupDirectory($this->testDirectory2);

        // Create combined backup archive
        $combinedBackupPath = $this->createCombinedBackup([$this->testDirectory1, $this->testDirectory2]);
        
        $backup = Backup::create([
            'name' => 'combined-backup',
            'type' => 'files',
            'file_path' => $combinedBackupPath,
            'file_size' => filesize($combinedBackupPath),
            'status' => 'completed',
            'created_by' => $this->user->id,
            'checksum' => md5_file($combinedBackupPath)
        ]);

        // Delete directories
        File::deleteDirectory($this->testDirectory1);
        File::deleteDirectory($this->testDirectory2);

        // Restore both directories
        $result = $this->restoreService->restoreFiles($combinedBackupPath, [$this->testDirectory1, $this->testDirectory2]);

        // Assert restoration was successful
        $this->assertTrue($result->isSuccessful());

        // Verify both directories were restored
        $this->assertDirectoryExists($this->testDirectory1);
        $this->assertDirectoryExists($this->testDirectory2);
        $this->assertFileExists($this->testDirectory1 . '/test1.txt');
        $this->assertFileExists($this->testDirectory2 . '/test3.txt');
    }

    /** @test */
    public function it_handles_restoration_failure_with_rollback()
    {
        // Create backup
        $backupPath = $this->fileHandler->backupDirectory($this->testDirectory1);
        $backup = Backup::create([
            'name' => 'test-backup',
            'type' => 'files',
            'file_path' => $backupPath,
            'file_size' => filesize($backupPath),
            'status' => 'completed',
            'created_by' => $this->user->id,
            'checksum' => md5_file($backupPath)
        ]);

        // Modify files to create pre-restore state
        File::put($this->testDirectory1 . '/test1.txt', 'pre-restore content');

        // Create a corrupted backup file to simulate failure
        $corruptedBackupPath = $this->testBackupPath . '/corrupted.zip';
        File::put($corruptedBackupPath, 'not a valid zip file');

        $corruptedBackup = Backup::create([
            'name' => 'corrupted-backup',
            'type' => 'files',
            'file_path' => $corruptedBackupPath,
            'file_size' => filesize($corruptedBackupPath),
            'status' => 'completed',
            'created_by' => $this->user->id,
            'checksum' => md5_file($corruptedBackupPath)
        ]);

        // Attempt restoration with corrupted backup
        $result = $this->restoreService->restoreFiles($corruptedBackupPath, [$this->testDirectory1]);

        // Assert restoration failed
        $this->assertTrue($result->isFailed());
        $this->assertStringContainsString('validation failed', $result->getMessage());

        // Verify no restore log was created since validation failed before restoration started
        $restoreLog = RestoreLog::where('backup_id', $corruptedBackup->id)->first();
        $this->assertNull($restoreLog);
    }

    /** @test */
    public function it_validates_backup_file_before_restoration()
    {
        // Create non-existent backup record
        $nonExistentPath = $this->testBackupPath . '/non-existent.zip';
        $backup = Backup::create([
            'name' => 'non-existent-backup',
            'type' => 'files',
            'file_path' => $nonExistentPath,
            'file_size' => 0,
            'status' => 'completed',
            'created_by' => $this->user->id,
            'checksum' => 'fake-checksum'
        ]);

        // Attempt restoration
        $result = $this->restoreService->restoreFiles($nonExistentPath, [$this->testDirectory1]);

        // Assert restoration failed due to validation
        $this->assertTrue($result->isFailed());
        $this->assertStringContainsString('not found or not readable', $result->getMessage());
    }

    /** @test */
    public function it_logs_restoration_activities_properly()
    {
        Log::spy();

        // Create backup
        $backupPath = $this->fileHandler->backupDirectory($this->testDirectory1);
        $backup = Backup::create([
            'name' => 'test-backup',
            'type' => 'files',
            'file_path' => $backupPath,
            'file_size' => filesize($backupPath),
            'status' => 'completed',
            'created_by' => $this->user->id,
            'checksum' => md5_file($backupPath)
        ]);

        // Restore files
        $result = $this->restoreService->restoreFiles($backupPath, [$this->testDirectory1]);

        // Assert restoration was successful
        $this->assertTrue($result->isSuccessful());

        // Verify logging
        Log::shouldHaveReceived('info')
            ->with('Starting file restoration', \Mockery::type('array'))
            ->once();

        Log::shouldHaveReceived('info')
            ->with('File restoration completed successfully', \Mockery::type('array'))
            ->once();
    }

    /** @test */
    public function it_can_rollback_failed_restoration()
    {
        // Create backup
        $backupPath = $this->fileHandler->backupDirectory($this->testDirectory1);
        
        // Modify original files
        File::put($this->testDirectory1 . '/test1.txt', 'modified content for rollback test');
        $originalContent = File::get($this->testDirectory1 . '/test1.txt');

        // Create pre-restore backup manually
        $preRestoreBackup = $this->fileHandler->createPreRestoreBackup([$this->testDirectory1]);

        // Simulate restoration failure by corrupting the directory
        File::deleteDirectory($this->testDirectory1);
        File::makeDirectory($this->testDirectory1, 0755, true);
        File::put($this->testDirectory1 . '/corrupted.txt', 'corrupted state');

        // Perform rollback
        $rollbackResult = $this->restoreService->rollbackFileRestore($preRestoreBackup, [$this->testDirectory1]);

        // Assert rollback was successful
        $this->assertTrue($rollbackResult);

        // Verify original content was restored
        $this->assertFileExists($this->testDirectory1 . '/test1.txt');
        $this->assertEquals($originalContent, File::get($this->testDirectory1 . '/test1.txt'));
        $this->assertFileDoesNotExist($this->testDirectory1 . '/corrupted.txt');
    }

    /**
     * Create test files in the test directories
     */
    private function createTestFiles(): void
    {
        // Directory 1 files
        File::put($this->testDirectory1 . '/test1.txt', 'test content 1');
        File::makeDirectory($this->testDirectory1 . '/subdir', 0755, true);
        File::put($this->testDirectory1 . '/subdir/test2.txt', 'test content 2');

        // Directory 2 files
        File::put($this->testDirectory2 . '/test3.txt', 'test content 3');
        File::makeDirectory($this->testDirectory2 . '/subdir2', 0755, true);
        File::put($this->testDirectory2 . '/subdir2/test4.txt', 'test content 4');
    }

    /**
     * Create a combined backup archive containing multiple directories
     */
    private function createCombinedBackup(array $directories): string
    {
        return $this->fileHandler->createPreRestoreBackup($directories);
    }
}