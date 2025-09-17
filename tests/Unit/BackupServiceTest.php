<?php

namespace Tests\Unit;

use App\Models\Backup;
use App\Models\User;
use App\Services\BackupConfig;
use App\Services\BackupResult;
use App\Services\BackupService;
use App\Services\BackupStatus;
use App\Services\DatabaseBackupHandler;
use App\Services\FileBackupHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

class BackupServiceTest extends TestCase
{
    use RefreshDatabase;

    private BackupService $backupService;
    private DatabaseBackupHandler $mockDbHandler;
    private FileBackupHandler $mockFileHandler;
    private BackupConfig $mockConfig;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Auth to return a user ID without creating a real user
        Auth::shouldReceive('id')->andReturn(1);

        // Create mocks
        $this->mockDbHandler = Mockery::mock(DatabaseBackupHandler::class);
        $this->mockFileHandler = Mockery::mock(FileBackupHandler::class);
        $this->mockConfig = Mockery::mock(BackupConfig::class);

        // Set up default mock expectations
        $this->mockConfig->shouldReceive('getRetryAttempts')->andReturn(1);
        $this->mockConfig->shouldReceive('getRetryDelay')->andReturn(1);
        $this->mockConfig->shouldReceive('getBackupDirectories')->andReturn(['/test/dir1', '/test/dir2']);
        $this->mockConfig->shouldReceive('getStoragePath')->andReturn('/test/storage');
        $this->mockConfig->shouldReceive('getDatabaseRetentionDays')->andReturn(30);
        $this->mockConfig->shouldReceive('getFilesRetentionDays')->andReturn(14);

        $this->backupService = new BackupService(
            $this->mockDbHandler,
            $this->mockFileHandler,
            $this->mockConfig
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_manual_backup_returns_backup_result()
    {
        // Mock Backup model creation to avoid database interactions
        $mockBackup = Mockery::mock(Backup::class);
        $mockBackup->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockBackup->shouldReceive('getAttribute')->with('type')->andReturn('full');
        $mockBackup->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $mockBackup->shouldReceive('getAttribute')->with('metadata')->andReturn([]);
        $mockBackup->shouldReceive('update')->andReturn(true);
        
        Backup::shouldReceive('create')->andReturn($mockBackup);

        $result = $this->backupService->createManualBackup();

        $this->assertInstanceOf(BackupResult::class, $result);
    }

    public function test_create_manual_database_backup_success()
    {
        $this->mockDbHandler->shouldReceive('createDump')
            ->once()
            ->andReturn('/test/backup.sql');
        
        $this->mockDbHandler->shouldReceive('validateDump')
            ->with('/test/backup.sql')
            ->once()
            ->andReturn(true);

        File::shouldReceive('size')
            ->with('/test/backup.sql')
            ->once()
            ->andReturn(1024);

        $result = $this->backupService->createManualBackup([
            'type' => 'database'
        ]);

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContains('Backup completed successfully', $result->getMessage());
        $this->assertNotNull($result->getBackup());
        $this->assertEquals('database', $result->getBackup()->type);
        $this->assertEquals('completed', $result->getBackup()->status);
    }

    public function test_create_manual_files_backup_success()
    {
        File::shouldReceive('exists')
            ->with('/test/dir1')
            ->once()
            ->andReturn(true);
        
        File::shouldReceive('exists')
            ->with('/test/dir2')
            ->once()
            ->andReturn(true);

        $this->mockFileHandler->shouldReceive('backupDirectory')
            ->with('/test/dir1')
            ->once()
            ->andReturn('/test/dir1_backup.zip');
        
        $this->mockFileHandler->shouldReceive('backupDirectory')
            ->with('/test/dir2')
            ->once()
            ->andReturn('/test/dir2_backup.zip');

        $this->mockFileHandler->shouldReceive('validateArchive')
            ->with('/test/dir1_backup.zip')
            ->once()
            ->andReturn(true);
        
        $this->mockFileHandler->shouldReceive('validateArchive')
            ->with('/test/dir2_backup.zip')
            ->once()
            ->andReturn(true);

        File::shouldReceive('size')
            ->with('/test/dir1_backup.zip')
            ->once()
            ->andReturn(512);
        
        File::shouldReceive('size')
            ->with('/test/dir2_backup.zip')
            ->once()
            ->andReturn(256);

        $result = $this->backupService->createManualBackup([
            'type' => 'files'
        ]);

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('files', $result->getBackup()->type);
        $this->assertEquals(768, $result->getBackup()->file_size); // 512 + 256
    }

    public function test_create_manual_full_backup_success()
    {
        // Mock database backup
        $this->mockDbHandler->shouldReceive('createDump')
            ->once()
            ->andReturn('/test/backup.sql');
        
        $this->mockDbHandler->shouldReceive('validateDump')
            ->with('/test/backup.sql')
            ->once()
            ->andReturn(true);

        // Mock file backups
        File::shouldReceive('exists')
            ->with('/test/dir1')
            ->once()
            ->andReturn(true);
        
        File::shouldReceive('exists')
            ->with('/test/dir2')
            ->once()
            ->andReturn(true);

        $this->mockFileHandler->shouldReceive('backupDirectory')
            ->with('/test/dir1')
            ->once()
            ->andReturn('/test/dir1_backup.zip');
        
        $this->mockFileHandler->shouldReceive('backupDirectory')
            ->with('/test/dir2')
            ->once()
            ->andReturn('/test/dir2_backup.zip');

        $this->mockFileHandler->shouldReceive('validateArchive')
            ->twice()
            ->andReturn(true);

        File::shouldReceive('size')
            ->times(3)
            ->andReturn(1024, 512, 256); // db, file1, file2

        $result = $this->backupService->createManualBackup([
            'type' => 'full'
        ]);

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('full', $result->getBackup()->type);
        $this->assertEquals(1792, $result->getBackup()->file_size); // 1024 + 512 + 256
    }

    public function test_create_manual_backup_with_invalid_type()
    {
        $result = $this->backupService->createManualBackup([
            'type' => 'invalid'
        ]);

        $this->assertFalse($result->isSuccessful());
        $this->assertStringContains('Invalid backup type', $result->getMessage());
    }

    public function test_create_manual_backup_database_failure()
    {
        $this->mockDbHandler->shouldReceive('createDump')
            ->twice() // Initial attempt + 1 retry
            ->andThrow(new \Exception('Database connection failed'));

        $result = $this->backupService->createManualBackup([
            'type' => 'database'
        ]);

        $this->assertFalse($result->isSuccessful());
        $this->assertStringContains('Database backup failed after 2 attempts', $result->getMessage());
        $this->assertEquals('failed', $result->getBackup()->status);
    }

    public function test_create_manual_backup_file_failure()
    {
        File::shouldReceive('exists')
            ->with('/test/dir1')
            ->once()
            ->andReturn(true);

        $this->mockFileHandler->shouldReceive('backupDirectory')
            ->with('/test/dir1')
            ->twice() // Initial attempt + 1 retry
            ->andThrow(new \Exception('File backup failed'));

        $result = $this->backupService->createManualBackup([
            'type' => 'files'
        ]);

        $this->assertFalse($result->isSuccessful());
        $this->assertStringContains('File backup failed for directory /test/dir1 after 2 attempts', $result->getMessage());
        $this->assertEquals('failed', $result->getBackup()->status);
    }

    public function test_get_backup_history()
    {
        // Create test backups
        Backup::factory()->count(5)->create();

        $history = $this->backupService->getBackupHistory(3);

        $this->assertInstanceOf(Collection::class, $history);
        $this->assertCount(3, $history);
    }

    public function test_get_backup_status()
    {
        // Create test backups
        Backup::factory()->count(2)->create([
            'status' => 'completed',
            'created_at' => now()->subDays(1)
        ]);
        
        Backup::factory()->count(1)->create([
            'status' => 'failed',
            'created_at' => now()->subDays(2)
        ]);
        
        Backup::factory()->count(1)->create([
            'status' => 'pending'
        ]);

        File::shouldReceive('exists')
            ->with('/test/storage')
            ->once()
            ->andReturn(true);
        
        File::shouldReceive('allFiles')
            ->with('/test/storage')
            ->once()
            ->andReturn([]);

        $status = $this->backupService->getBackupStatus();

        $this->assertInstanceOf(BackupStatus::class, $status);
        $this->assertEquals(4, $status->getTotalBackups());
        $this->assertEquals(3, $status->getRecentBackups());
        $this->assertEquals(2, $status->getSuccessfulBackups());
        $this->assertEquals(1, $status->getFailedBackups());
        $this->assertEquals(1, $status->getPendingBackups());
    }

    public function test_validate_backup_integrity_single_sql_file()
    {
        $this->mockDbHandler->shouldReceive('validateDump')
            ->with('/test/backup.sql')
            ->once()
            ->andReturn(true);

        File::shouldReceive('exists')
            ->with('/test/backup.sql')
            ->once()
            ->andReturn(true);

        $result = $this->backupService->validateBackupIntegrity('/test/backup.sql');

        $this->assertTrue($result);
    }

    public function test_validate_backup_integrity_single_zip_file()
    {
        $this->mockFileHandler->shouldReceive('validateArchive')
            ->with('/test/backup.zip')
            ->once()
            ->andReturn(true);

        File::shouldReceive('exists')
            ->with('/test/backup.zip')
            ->once()
            ->andReturn(true);

        $result = $this->backupService->validateBackupIntegrity('/test/backup.zip');

        $this->assertTrue($result);
    }

    public function test_validate_backup_integrity_multiple_files()
    {
        $paths = [
            'database' => '/test/backup.sql',
            'files' => ['/test/file1.zip', '/test/file2.zip']
        ];

        $this->mockDbHandler->shouldReceive('validateDump')
            ->with('/test/backup.sql')
            ->once()
            ->andReturn(true);

        $this->mockFileHandler->shouldReceive('validateArchive')
            ->with('/test/file1.zip')
            ->once()
            ->andReturn(true);
        
        $this->mockFileHandler->shouldReceive('validateArchive')
            ->with('/test/file2.zip')
            ->once()
            ->andReturn(true);

        File::shouldReceive('exists')
            ->times(3)
            ->andReturn(true);

        $result = $this->backupService->validateBackupIntegrity(json_encode($paths));

        $this->assertTrue($result);
    }

    public function test_validate_backup_integrity_nonexistent_file()
    {
        File::shouldReceive('exists')
            ->with('/nonexistent/backup.sql')
            ->once()
            ->andReturn(false);

        $result = $this->backupService->validateBackupIntegrity('/nonexistent/backup.sql');

        $this->assertFalse($result);
    }

    public function test_validate_backup_integrity_invalid_file_type()
    {
        File::shouldReceive('exists')
            ->with('/test/backup.txt')
            ->once()
            ->andReturn(true);

        $result = $this->backupService->validateBackupIntegrity('/test/backup.txt');

        $this->assertFalse($result);
    }

    public function test_backup_metadata_is_properly_set()
    {
        $this->mockDbHandler->shouldReceive('createDump')
            ->once()
            ->andReturn('/test/backup.sql');
        
        $this->mockDbHandler->shouldReceive('validateDump')
            ->once()
            ->andReturn(true);

        File::shouldReceive('size')
            ->once()
            ->andReturn(1024);

        $customOptions = [
            'type' => 'database',
            'name' => 'test_backup',
            'custom_field' => 'custom_value'
        ];

        $result = $this->backupService->createManualBackup($customOptions);

        $this->assertTrue($result->isSuccessful());
        
        $backup = $result->getBackup();
        $metadata = $backup->metadata;

        $this->assertTrue($metadata['manual']);
        $this->assertTrue($metadata['include_database']);
        $this->assertFalse($metadata['include_files']);
        $this->assertEquals($customOptions, $metadata['options']);
        $this->assertArrayHasKey('backup_paths', $metadata);
        $this->assertArrayHasKey('total_size', $metadata);
        $this->assertArrayHasKey('completed_at', $metadata);
    }

    public function test_backup_skips_nonexistent_directories()
    {
        File::shouldReceive('exists')
            ->with('/test/dir1')
            ->once()
            ->andReturn(false);
        
        File::shouldReceive('exists')
            ->with('/test/dir2')
            ->once()
            ->andReturn(true);

        $this->mockFileHandler->shouldReceive('backupDirectory')
            ->with('/test/dir2')
            ->once()
            ->andReturn('/test/dir2_backup.zip');

        $this->mockFileHandler->shouldReceive('validateArchive')
            ->with('/test/dir2_backup.zip')
            ->once()
            ->andReturn(true);

        File::shouldReceive('size')
            ->with('/test/dir2_backup.zip')
            ->once()
            ->andReturn(256);

        $result = $this->backupService->createManualBackup([
            'type' => 'files'
        ]);

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(256, $result->getBackup()->file_size);
    }

    public function test_backup_retry_logic_eventually_succeeds()
    {
        $this->mockConfig->shouldReceive('getRetryAttempts')->andReturn(2);

        $this->mockDbHandler->shouldReceive('createDump')
            ->twice()
            ->andThrow(new \Exception('Temporary failure'));
        
        $this->mockDbHandler->shouldReceive('createDump')
            ->once()
            ->andReturn('/test/backup.sql');
        
        $this->mockDbHandler->shouldReceive('validateDump')
            ->with('/test/backup.sql')
            ->once()
            ->andReturn(true);

        File::shouldReceive('size')
            ->with('/test/backup.sql')
            ->once()
            ->andReturn(1024);

        $result = $this->backupService->createManualBackup([
            'type' => 'database'
        ]);

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('completed', $result->getBackup()->status);
    }
}