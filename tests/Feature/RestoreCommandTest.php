<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\User;
use App\Services\RestoreService;
use App\Services\RestoreResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Mockery;

class RestoreCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $restoreService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the RestoreService
        $this->restoreService = Mockery::mock(RestoreService::class);
        $this->app->instance(RestoreService::class, $this->restoreService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_lists_available_backups()
    {
        // Create test backups
        $backup1 = Backup::factory()->create([
            'name' => 'test-backup-1',
            'type' => 'database',
            'status' => 'completed',
            'file_size' => 1024,
            'created_at' => now()->subHours(2)
        ]);

        $backup2 = Backup::factory()->create([
            'name' => 'test-backup-2',
            'type' => 'files',
            'status' => 'completed',
            'file_size' => 2048,
            'created_at' => now()->subHour()
        ]);

        // Create a failed backup that should not appear
        Backup::factory()->create([
            'name' => 'failed-backup',
            'type' => 'database',
            'status' => 'failed'
        ]);

        $this->artisan('backup:restore --list')
            ->expectsOutput('Available backups:')
            ->expectsTable([
                'ID', 'Name', 'Type', 'Size', 'Created', 'Status'
            ], [
                [$backup2->id, 'test-backup-2', 'files', '2.00 KB', $backup2->created_at->format('Y-m-d H:i:s'), 'Missing'],
                [$backup1->id, 'test-backup-1', 'database', '1.00 KB', $backup1->created_at->format('Y-m-d H:i:s'), 'Missing'],
            ])
            ->assertExitCode(0);
    }

    /** @test */
    public function it_shows_warning_when_no_backups_available()
    {
        $this->artisan('backup:restore --list')
            ->expectsOutput('Available backups:')
            ->expectsOutput('No completed backups found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_fails_when_backup_id_not_found()
    {
        $this->artisan('backup:restore 999')
            ->expectsOutput('Backup with ID 999 not found.')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_fails_when_backup_file_not_found()
    {
        $this->artisan('backup:restore /nonexistent/backup.sql')
            ->expectsOutput('Backup file not found: /nonexistent/backup.sql')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_fails_with_both_database_and_files_options()
    {
        $this->artisan('backup:restore --database --files')
            ->expectsOutput('Cannot specify both --database and --files options. Use neither for full restore.')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_restores_database_from_backup_id_with_force_flag()
    {
        // Create a test backup file
        $backupPath = storage_path('app/backups/test-backup.sql');
        Storage::disk('local')->put('backups/test-backup.sql', 'CREATE TABLE test;');

        $backup = Backup::factory()->create([
            'name' => 'test-database-backup',
            'type' => 'database',
            'status' => 'completed',
            'file_path' => $backupPath
        ]);

        // Mock successful database restoration
        $this->restoreService
            ->shouldReceive('restoreDatabase')
            ->once()
            ->with($backupPath)
            ->andReturn(new RestoreResult(true, 'Database restored successfully', [
                'restore_log_id' => 1,
                'pre_restore_backup' => '/path/to/pre-restore-backup.sql'
            ]));

        $this->artisan("backup:restore {$backup->id} --database --force")
            ->expectsOutput('Starting database restoration...')
            ->expectsOutput('Restoring database...')
            ->expectsOutput('✓ Database restored successfully')
            ->expectsOutput('🎉 Restoration completed successfully!')
            ->assertExitCode(0);

        // Clean up
        Storage::disk('local')->delete('backups/test-backup.sql');
    }

    /** @test */
    public function it_restores_files_from_backup_path_with_force_flag()
    {
        // Create a test backup file
        $backupPath = storage_path('app/backups/test-files-backup.zip');
        Storage::disk('local')->put('backups/test-files-backup.zip', 'fake zip content');

        // Mock successful file restoration
        $this->restoreService
            ->shouldReceive('restoreFiles')
            ->once()
            ->with($backupPath, [
                'storage/app/public/pre-alerts',
                'storage/app/public/receipts',
            ])
            ->andReturn(new RestoreResult(true, 'Files restored successfully', [
                'restore_log_id' => 2,
                'pre_restore_backup' => '/path/to/pre-restore-files-backup.zip',
                'restored_directories' => [
                    'storage/app/public/pre-alerts',
                    'storage/app/public/receipts',
                ]
            ]));

        $this->artisan("backup:restore {$backupPath} --files --force")
            ->expectsOutput('Starting files restoration...')
            ->expectsOutput('Restoring files...')
            ->expectsOutput('✓ Files restored successfully')
            ->expectsOutput('🎉 Restoration completed successfully!')
            ->assertExitCode(0);

        // Clean up
        Storage::disk('local')->delete('backups/test-files-backup.zip');
    }

    /** @test */
    public function it_performs_full_restore_when_no_specific_type_specified()
    {
        // Create a test backup file
        $backupPath = storage_path('app/backups/test-full-backup.sql');
        Storage::disk('local')->put('backups/test-full-backup.sql', 'CREATE TABLE test;');

        $backup = Backup::factory()->create([
            'name' => 'test-full-backup',
            'type' => 'full',
            'status' => 'completed',
            'file_path' => $backupPath
        ]);

        // Mock successful database restoration
        $this->restoreService
            ->shouldReceive('restoreDatabase')
            ->once()
            ->with($backupPath)
            ->andReturn(new RestoreResult(true, 'Database restored successfully', [
                'restore_log_id' => 1,
                'pre_restore_backup' => '/path/to/pre-restore-backup.sql'
            ]));

        // Mock successful file restoration
        $this->restoreService
            ->shouldReceive('restoreFiles')
            ->once()
            ->with($backupPath, [
                'storage/app/public/pre-alerts',
                'storage/app/public/receipts',
            ])
            ->andReturn(new RestoreResult(true, 'Files restored successfully', [
                'restore_log_id' => 2,
                'pre_restore_backup' => '/path/to/pre-restore-files-backup.zip'
            ]));

        $this->artisan("backup:restore {$backup->id} --force")
            ->expectsOutput('Starting full restoration...')
            ->expectsOutput('Restoring database...')
            ->expectsOutput('✓ Database restored successfully')
            ->expectsOutput('Restoring files...')
            ->expectsOutput('✓ Files restored successfully')
            ->expectsOutput('🎉 Restoration completed successfully!')
            ->assertExitCode(0);

        // Clean up
        Storage::disk('local')->delete('backups/test-full-backup.sql');
    }

    /** @test */
    public function it_handles_database_restoration_failure()
    {
        // Create a test backup file
        $backupPath = storage_path('app/backups/test-backup.sql');
        Storage::disk('local')->put('backups/test-backup.sql', 'CREATE TABLE test;');

        $backup = Backup::factory()->create([
            'name' => 'test-database-backup',
            'type' => 'database',
            'status' => 'completed',
            'file_path' => $backupPath
        ]);

        // Mock failed database restoration
        $this->restoreService
            ->shouldReceive('restoreDatabase')
            ->once()
            ->with($backupPath)
            ->andReturn(new RestoreResult(false, 'Database restoration failed: Connection error'));

        $this->artisan("backup:restore {$backup->id} --database --force")
            ->expectsOutput('Starting database restoration...')
            ->expectsOutput('Restoring database...')
            ->expectsOutput('Database restoration failed: Database restoration failed: Connection error')
            ->assertExitCode(1);

        // Clean up
        Storage::disk('local')->delete('backups/test-backup.sql');
    }

    /** @test */
    public function it_handles_file_restoration_failure()
    {
        // Create a test backup file
        $backupPath = storage_path('app/backups/test-files-backup.zip');
        Storage::disk('local')->put('backups/test-files-backup.zip', 'fake zip content');

        // Mock failed file restoration
        $this->restoreService
            ->shouldReceive('restoreFiles')
            ->once()
            ->with($backupPath, [
                'storage/app/public/pre-alerts',
                'storage/app/public/receipts',
            ])
            ->andReturn(new RestoreResult(false, 'File restoration failed: Archive corrupted'));

        $this->artisan("backup:restore {$backupPath} --files --force")
            ->expectsOutput('Starting files restoration...')
            ->expectsOutput('Restoring files...')
            ->expectsOutput('File restoration failed: File restoration failed: Archive corrupted')
            ->assertExitCode(1);

        // Clean up
        Storage::disk('local')->delete('backups/test-files-backup.zip');
    }

    /** @test */
    public function it_handles_exceptions_during_restoration()
    {
        // Create a test backup file
        $backupPath = storage_path('app/backups/test-backup.sql');
        Storage::disk('local')->put('backups/test-backup.sql', 'CREATE TABLE test;');

        $backup = Backup::factory()->create([
            'name' => 'test-database-backup',
            'type' => 'database',
            'status' => 'completed',
            'file_path' => $backupPath
        ]);

        // Mock exception during restoration
        $this->restoreService
            ->shouldReceive('restoreDatabase')
            ->once()
            ->with($backupPath)
            ->andThrow(new \Exception('Unexpected error occurred'));

        $this->artisan("backup:restore {$backup->id} --database --force")
            ->expectsOutput('Starting database restoration...')
            ->expectsOutput('Restoring database...')
            ->expectsOutput('Restoration failed with exception: Unexpected error occurred')
            ->expectsOutput('Check the application logs for more details.')
            ->assertExitCode(1);

        // Clean up
        Storage::disk('local')->delete('backups/test-backup.sql');
    }

    /** @test */
    public function it_validates_backup_file_before_restoration()
    {
        // Create an empty backup file
        $backupPath = storage_path('app/backups/empty-backup.sql');
        Storage::disk('local')->put('backups/empty-backup.sql', '');

        $this->artisan("backup:restore {$backupPath} --force")
            ->expectsOutput('Backup file is empty: ' . $backupPath)
            ->assertExitCode(1);

        // Clean up
        Storage::disk('local')->delete('backups/empty-backup.sql');
    }

    /** @test */
    public function it_detects_backup_type_from_file_extension()
    {
        // Create test backup files with different extensions
        $sqlBackupPath = storage_path('app/backups/test-backup.sql');
        $zipBackupPath = storage_path('app/backups/test-backup.zip');

        Storage::disk('local')->put('backups/test-backup.sql', 'CREATE TABLE test;');
        Storage::disk('local')->put('backups/test-backup.zip', 'fake zip content');

        // Mock successful database restoration for SQL file
        $this->restoreService
            ->shouldReceive('restoreDatabase')
            ->once()
            ->with($sqlBackupPath)
            ->andReturn(new RestoreResult(true, 'Database restored successfully'));

        $this->artisan("backup:restore {$sqlBackupPath} --force")
            ->expectsOutput('Type: database')
            ->expectsOutput('Starting database restoration...')
            ->assertExitCode(0);

        // Mock successful file restoration for ZIP file
        $this->restoreService
            ->shouldReceive('restoreFiles')
            ->once()
            ->with($zipBackupPath, [
                'storage/app/public/pre-alerts',
                'storage/app/public/receipts',
            ])
            ->andReturn(new RestoreResult(true, 'Files restored successfully'));

        $this->artisan("backup:restore {$zipBackupPath} --force")
            ->expectsOutput('Type: files')
            ->expectsOutput('Starting files restoration...')
            ->assertExitCode(0);

        // Clean up
        Storage::disk('local')->delete('backups/test-backup.sql');
        Storage::disk('local')->delete('backups/test-backup.zip');
    }

    /** @test */
    public function it_cancels_restoration_when_user_declines_confirmation()
    {
        // Create a test backup file
        $backupPath = storage_path('app/backups/test-backup.sql');
        Storage::disk('local')->put('backups/test-backup.sql', 'CREATE TABLE test;');

        $backup = Backup::factory()->create([
            'name' => 'test-database-backup',
            'type' => 'database',
            'status' => 'completed',
            'file_path' => $backupPath
        ]);

        $this->artisan("backup:restore {$backup->id} --database")
            ->expectsOutput('⚠️  WARNING: This operation will overwrite existing data!')
            ->expectsQuestion('Do you want to proceed with the restoration?', false)
            ->expectsOutput('Restore operation cancelled.')
            ->assertExitCode(0);

        // Clean up
        Storage::disk('local')->delete('backups/test-backup.sql');
    }

    /** @test */
    public function it_displays_restoration_summary_with_pre_restore_backup_info()
    {
        // Create a test backup file
        $backupPath = storage_path('app/backups/test-backup.sql');
        Storage::disk('local')->put('backups/test-backup.sql', 'CREATE TABLE test;');

        $backup = Backup::factory()->create([
            'name' => 'test-database-backup',
            'type' => 'database',
            'status' => 'completed',
            'file_path' => $backupPath
        ]);

        // Mock successful database restoration with pre-restore backup
        $this->restoreService
            ->shouldReceive('restoreDatabase')
            ->once()
            ->with($backupPath)
            ->andReturn(new RestoreResult(true, 'Database restored successfully', [
                'restore_log_id' => 1,
                'pre_restore_backup' => '/path/to/pre-restore-backup_2024-01-01_12-00-00.sql'
            ]));

        $this->artisan("backup:restore {$backup->id} --database --force")
            ->expectsOutput('Restoration Summary:')
            ->expectsTable([
                'Component', 'Status', 'Details'
            ], [
                ['Database', '✓ Success', 'Database restored successfully'],
                ['Pre-restore backup', '📁 Created', 'pre-restore-backup_2024-01-01_12-00-00.sql'],
            ])
            ->expectsOutput('💡 Pre-restore backups have been created and can be used for rollback if needed.')
            ->assertExitCode(0);

        // Clean up
        Storage::disk('local')->delete('backups/test-backup.sql');
    }
}