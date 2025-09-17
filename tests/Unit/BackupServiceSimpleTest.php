<?php

namespace Tests\Unit;

use App\Models\Backup;
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

class BackupServiceSimpleTest extends TestCase
{
    use RefreshDatabase;

    private BackupService $backupService;
    private DatabaseBackupHandler $mockDbHandler;
    private FileBackupHandler $mockFileHandler;
    private BackupConfig $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user for foreign key constraint
        $user = new \App\Models\User([
            'first_name' => 'Test',
            'last_name' => 'User', 
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id' => 1
        ]);
        
        // Create role if it doesn't exist
        if (!\App\Models\Role::find(1)) {
            \App\Models\Role::create([
                'id' => 1,
                'name' => 'test_role',
                'description' => 'Test role'
            ]);
        }
        
        $user->save();

        // Mock Auth to return the user ID
        Auth::shouldReceive('id')->andReturn($user->id);

        // Create mocks
        $this->mockDbHandler = Mockery::mock(DatabaseBackupHandler::class);
        $this->mockFileHandler = Mockery::mock(FileBackupHandler::class);
        $this->mockConfig = Mockery::mock(BackupConfig::class);

        // Set up default mock expectations
        $this->mockConfig->shouldReceive('getRetryAttempts')->andReturn(1);
        $this->mockConfig->shouldReceive('getRetryDelay')->andReturn(1);
        $this->mockConfig->shouldReceive('getBackupDirectories')->andReturn(['/test/dir1']);
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
        // Mock successful database backup
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

        $this->assertInstanceOf(BackupResult::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('Backup completed successfully', $result->getMessage());
    }

    public function test_create_manual_backup_with_invalid_type()
    {
        $result = $this->backupService->createManualBackup([
            'type' => 'invalid'
        ]);

        $this->assertInstanceOf(BackupResult::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertStringContainsString('Invalid backup type', $result->getMessage());
    }

    public function test_get_backup_history_returns_collection()
    {
        // Create test backups
        Backup::factory()->count(3)->create([
            'file_path' => '/test/backup.sql',
            'created_by' => 1
        ]);

        $history = $this->backupService->getBackupHistory(5);

        $this->assertInstanceOf(Collection::class, $history);
        $this->assertCount(3, $history);
    }

    public function test_get_backup_status_returns_backup_status()
    {
        // Create test backups
        Backup::factory()->count(2)->create([
            'status' => 'completed',
            'file_path' => '/test/backup.sql',
            'created_at' => now()->subDays(1),
            'created_by' => 1
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
        $this->assertEquals(2, $status->getTotalBackups());
        $this->assertEquals(2, $status->getRecentBackups());
        $this->assertEquals(2, $status->getSuccessfulBackups());
    }

    public function test_validate_backup_integrity_sql_file()
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

    public function test_validate_backup_integrity_zip_file()
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

    public function test_validate_backup_integrity_nonexistent_file()
    {
        File::shouldReceive('exists')
            ->with('/nonexistent/backup.sql')
            ->once()
            ->andReturn(false);

        $result = $this->backupService->validateBackupIntegrity('/nonexistent/backup.sql');

        $this->assertFalse($result);
    }
}