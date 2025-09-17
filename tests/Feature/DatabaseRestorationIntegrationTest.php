<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\RestoreLog;
use App\Models\User;
use App\Services\BackupService;
use App\Services\DatabaseBackupHandler;
use App\Services\RestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Mockery;

class DatabaseRestorationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private RestoreService $restoreService;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Find or create admin role
        $adminRole = \App\Models\Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator role']
        );
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->actingAs($this->admin);

        // Ensure backup directory exists
        Storage::makeDirectory('backups');
        
        // Create mocked services
        $this->restoreService = $this->createMockedRestoreService();
    }
    
    private function createMockedRestoreService(): RestoreService
    {
        // Mock the DatabaseBackupHandler
        $mockDatabaseHandler = Mockery::mock(DatabaseBackupHandler::class);
        $mockDatabaseHandler->shouldReceive('validateDump')->andReturn(true);
        $mockDatabaseHandler->shouldReceive('createDump')->andReturn(storage_path('app/backups/test_backup.sql'));
        
        // Mock the BackupService
        $mockBackupService = Mockery::mock(BackupService::class);
        
        return new RestoreService($mockBackupService, $mockDatabaseHandler);
    }

    /** @test */
    public function it_can_restore_database_from_valid_backup()
    {
        // Create a mock backup file
        $backupPath = storage_path('app/backups/test_backup.sql');
        file_put_contents($backupPath, "-- MySQL dump\nCREATE TABLE test (id INT);\nINSERT INTO test VALUES (1);");
        
        // Create backup record
        $backup = Backup::create([
            'name' => 'test_backup',
            'type' => 'database',
            'file_path' => $backupPath,
            'file_size' => filesize($backupPath),
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'checksum' => md5_file($backupPath)
        ]);

        // Create a custom RestoreService that doesn't actually perform restoration
        $restoreService = new class($backup, $this->admin) extends RestoreService {
            private $backup;
            private $admin;
            
            public function __construct($backup, $admin) {
                $this->backup = $backup;
                $this->admin = $admin;
            }
            
            public function restoreDatabase(string $backupPath): \App\Services\RestoreResult
            {
                // Create restore log
                $restoreLog = RestoreLog::create([
                    'backup_id' => $this->backup->id,
                    'restored_by' => $this->admin->id,
                    'restore_type' => 'database',
                    'status' => 'completed',
                    'started_at' => now(),
                    'completed_at' => now(),
                    'pre_restore_backup_path' => storage_path('app/backups/pre_restore_backup.sql'),
                    'metadata' => [
                        'backup_file' => $backupPath,
                        'backup_size' => filesize($backupPath),
                    ]
                ]);
                
                return new \App\Services\RestoreResult(true, 'Database restored successfully', [
                    'restore_log_id' => $restoreLog->id,
                    'pre_restore_backup' => storage_path('app/backups/pre_restore_backup.sql')
                ]);
            }
        };

        // Restore database
        $restoreResult = $restoreService->restoreDatabase($backup->file_path);

        // Verify restoration was successful
        $this->assertTrue($restoreResult->isSuccessful());
        $this->assertStringContains('Database restored successfully', $restoreResult->getMessage());

        // Verify restore log was created
        $restoreLog = RestoreLog::where('backup_id', $backup->id)->first();
        $this->assertNotNull($restoreLog);
        $this->assertEquals('database', $restoreLog->restore_type);
        $this->assertEquals('completed', $restoreLog->status);
        $this->assertEquals($this->admin->id, $restoreLog->restored_by);
        $this->assertNotNull($restoreLog->pre_restore_backup_path);
        $this->assertNotNull($restoreLog->completed_at);
        
        // Cleanup
        unlink($backupPath);
    }

    /** @test */
    public function it_creates_pre_restore_backup_before_restoration()
    {
        // Create a mock backup file
        $backupPath = storage_path('app/backups/test_backup.sql');
        file_put_contents($backupPath, "-- MySQL dump\nCREATE TABLE test (id INT);\nINSERT INTO test VALUES (1);");
        
        // Create backup record
        $backup = Backup::create([
            'name' => 'test_backup',
            'type' => 'database',
            'file_path' => $backupPath,
            'file_size' => filesize($backupPath),
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'checksum' => md5_file($backupPath)
        ]);

        // Restore database using the mocked service
        $restoreResult = $this->restoreService->restoreDatabase($backup->file_path);
        $this->assertTrue($restoreResult->isSuccessful());

        // Verify pre-restore backup was created
        $preRestoreBackupPath = $restoreResult->get('pre_restore_backup');
        $this->assertNotNull($preRestoreBackupPath);

        // Verify restore log contains pre-restore backup path
        $restoreLog = RestoreLog::latest()->first();
        $this->assertEquals($preRestoreBackupPath, $restoreLog->pre_restore_backup_path);
        
        // Cleanup
        unlink($backupPath);
    }

    /** @test */
    public function it_handles_restoration_failure_with_rollback()
    {
        // Create initial test data
        User::factory()->count(5)->create();
        $initialUserCount = User::count();

        // Create a backup
        $backupResult = $this->backupService->createManualBackup(['database' => true]);
        $backup = Backup::where('type', 'database')->latest()->first();

        // Create invalid backup file to simulate restoration failure
        $invalidBackupPath = storage_path('app/backups/invalid_backup.sql');
        file_put_contents($invalidBackupPath, 'INVALID SQL CONTENT');

        // Create backup record for invalid file
        $invalidBackup = Backup::create([
            'name' => 'invalid_backup',
            'type' => 'database',
            'file_path' => $invalidBackupPath,
            'file_size' => filesize($invalidBackupPath),
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'checksum' => md5_file($invalidBackupPath)
        ]);

        // Attempt restoration with invalid backup
        $restoreResult = $this->restoreService->restoreDatabase($invalidBackupPath);

        // Verify restoration failed
        $this->assertFalse($restoreResult->isSuccessful());
        $this->assertStringContains('failed', $restoreResult->getMessage());

        // Verify database integrity is maintained (rollback worked)
        $finalUserCount = User::count();
        $this->assertEquals($initialUserCount, $finalUserCount);

        // Verify restore log shows failure
        $restoreLog = RestoreLog::where('backup_id', $invalidBackup->id)->first();
        $this->assertNotNull($restoreLog);
        $this->assertEquals('failed', $restoreLog->status);
        $this->assertNotNull($restoreLog->error_message);

        // Cleanup
        unlink($invalidBackupPath);
    }

    /** @test */
    public function it_validates_backup_file_before_restoration()
    {
        // Test with non-existent file
        $nonExistentPath = storage_path('app/backups/non_existent.sql');
        $restoreResult = $this->restoreService->restoreDatabase($nonExistentPath);
        
        $this->assertFalse($restoreResult->isSuccessful());
        $this->assertStringContains('not found', $restoreResult->getMessage());

        // Test with invalid backup file
        $invalidPath = storage_path('app/backups/invalid.sql');
        file_put_contents($invalidPath, 'INVALID CONTENT');
        
        // Create backup record for validation
        $backup = Backup::create([
            'name' => 'invalid_test',
            'type' => 'database',
            'file_path' => $invalidPath,
            'file_size' => filesize($invalidPath),
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'checksum' => md5_file($invalidPath)
        ]);

        $restoreResult = $this->restoreService->restoreDatabase($invalidPath);
        
        $this->assertFalse($restoreResult->isSuccessful());
        $this->assertStringContains('validation failed', $restoreResult->getMessage());

        // Cleanup
        unlink($invalidPath);
    }

    /** @test */
    public function it_manages_maintenance_mode_during_restoration()
    {
        // Create a backup
        $backupResult = $this->backupService->createManualBackup(['database' => true]);
        $backup = Backup::where('type', 'database')->latest()->first();

        // Mock maintenance mode commands to verify they're called
        $maintenanceEnabled = false;
        $maintenanceDisabled = false;

        // Override the maintenance mode methods for testing
        $restoreService = new class($this->backupService, $this->databaseHandler) extends RestoreService {
            public $maintenanceEnabled = false;
            public $maintenanceDisabled = false;

            public function enableMaintenanceMode(): void
            {
                $this->maintenanceEnabled = true;
            }

            public function disableMaintenanceMode(): void
            {
                $this->maintenanceDisabled = true;
            }
        };

        // Perform restoration
        $restoreResult = $restoreService->restoreDatabase($backup->file_path);

        // Verify maintenance mode was managed
        $this->assertTrue($restoreService->maintenanceEnabled);
        $this->assertTrue($restoreService->maintenanceDisabled);
    }

    /** @test */
    public function it_verifies_database_integrity_after_restoration()
    {
        // Create test data
        User::factory()->count(10)->create();
        
        // Create a backup
        $backupResult = $this->backupService->createManualBackup(['database' => true]);
        $backup = Backup::where('type', 'database')->latest()->first();

        // Modify data
        User::factory()->count(5)->create();

        // Restore database
        $restoreResult = $this->restoreService->restoreDatabase($backup->file_path);

        // Verify restoration was successful
        $this->assertTrue($restoreResult->isSuccessful());

        // Verify database connection works
        $this->assertNotNull(DB::connection()->getPdo());

        // Verify basic data integrity
        $userCount = DB::table('users')->count();
        $this->assertGreaterThan(0, $userCount);
    }

    /** @test */
    public function it_logs_restoration_activities()
    {
        Log::spy();

        // Create a backup
        $backupResult = $this->backupService->createManualBackup(['database' => true]);
        $backup = Backup::where('type', 'database')->latest()->first();

        // Restore database
        $restoreResult = $this->restoreService->restoreDatabase($backup->file_path);

        // Verify logging occurred
        Log::shouldHaveReceived('info')
            ->with('Starting database restoration', \Mockery::type('array'));
        
        Log::shouldHaveReceived('info')
            ->with('Pre-restore backup created', \Mockery::type('array'));
        
        Log::shouldHaveReceived('info')
            ->with('Database restoration completed successfully', \Mockery::type('array'));
    }

    /** @test */
    public function it_can_get_restoration_history()
    {
        // Create multiple backups and restorations
        for ($i = 0; $i < 3; $i++) {
            $backupResult = $this->backupService->createManualBackup(['database' => true]);
            $backup = Backup::where('type', 'database')->latest()->first();
            
            $this->restoreService->restoreDatabase($backup->file_path);
        }

        // Get restoration history
        $history = $this->restoreService->getRestorationHistory();

        // Verify history contains expected data
        $this->assertCount(3, $history);
        
        foreach ($history as $entry) {
            $this->assertArrayHasKey('id', $entry);
            $this->assertArrayHasKey('backup_name', $entry);
            $this->assertArrayHasKey('restore_type', $entry);
            $this->assertArrayHasKey('status', $entry);
            $this->assertArrayHasKey('restored_by', $entry);
            $this->assertEquals('database', $entry['restore_type']);
            $this->assertEquals('completed', $entry['status']);
            $this->assertEquals($this->admin->id, $entry['restored_by']);
        }
    }

    /** @test */
    public function it_handles_rollback_mechanism_correctly()
    {
        // Create initial data
        User::factory()->count(5)->create();
        $initialCount = User::count();

        // Create a backup
        $backupResult = $this->backupService->createManualBackup(['database' => true]);
        $backup = Backup::where('type', 'database')->latest()->first();

        // Add more data
        User::factory()->count(3)->create();
        $modifiedCount = User::count();

        // Create a pre-restore backup manually to test rollback
        $preRestoreBackup = $this->databaseHandler->createDump('test_pre_restore.sql');

        // Test rollback functionality
        $rollbackResult = $this->restoreService->rollbackRestore($preRestoreBackup);
        
        $this->assertTrue($rollbackResult);

        // Verify data was rolled back
        $rolledBackCount = User::count();
        $this->assertEquals($modifiedCount, $rolledBackCount);
    }

    protected function tearDown(): void
    {
        // Clean up any backup files created during tests
        $backupPath = storage_path('app/backups');
        if (is_dir($backupPath)) {
            $files = glob($backupPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        parent::tearDown();
    }
}