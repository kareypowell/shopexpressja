<?php

namespace Tests\Feature;

use App\Services\BackupStorageManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Mockery;

class BackupCleanupCommandTest extends TestCase
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
    public function it_can_cleanup_old_backups_successfully()
    {
        // Mock the BackupStorageManager
        $mockManager = Mockery::mock(BackupStorageManager::class);
        
        $cleanupResult = [
            'success' => true,
            'deleted_files' => [
                [
                    'name' => 'old-backup-1.sql',
                    'type' => 'database',
                    'size' => 1048576,
                    'age_days' => 35
                ],
                [
                    'name' => 'old-files-1.zip',
                    'type' => 'files',
                    'size' => 2097152,
                    'age_days' => 20
                ]
            ],
            'errors' => [],
            'retention' => [
                'database' => 30,
                'files' => 14
            ]
        ];
        
        $mockManager->shouldReceive('cleanupOldBackups')
            ->with(false)
            ->andReturn($cleanupResult);
        
        $this->app->instance(BackupStorageManager::class, $mockManager);

        // Run the command
        $exitCode = Artisan::call('backup:cleanup');

        // Assert success
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Starting backup cleanup process', $output);
        $this->assertStringContainsString('Cleanup completed successfully', $output);
        $this->assertStringContainsString('Deleted files:', $output);
        $this->assertStringContainsString('old-backup-1.sql', $output);
        $this->assertStringContainsString('old-files-1.zip', $output);
        $this->assertStringContainsString('Total space freed:', $output);
    }

    /** @test */
    public function it_can_run_in_dry_run_mode()
    {
        // Mock the BackupStorageManager
        $mockManager = Mockery::mock(BackupStorageManager::class);
        
        $cleanupResult = [
            'success' => true,
            'deleted_files' => [
                [
                    'name' => 'would-delete.sql',
                    'type' => 'database',
                    'size' => 1048576,
                    'age_days' => 35
                ]
            ],
            'errors' => [],
            'retention' => [
                'database' => 30,
                'files' => 14
            ]
        ];
        
        $mockManager->shouldReceive('cleanupOldBackups')
            ->with(true)
            ->andReturn($cleanupResult);
        
        $this->app->instance(BackupStorageManager::class, $mockManager);

        // Run the command with dry-run option
        $exitCode = Artisan::call('backup:cleanup', ['--dry-run' => true]);

        // Assert success
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Running in dry-run mode', $output);
        $this->assertStringContainsString('no files will be deleted', $output);
        $this->assertStringContainsString('Would delete files:', $output);
        $this->assertStringContainsString('would-delete.sql', $output);
        $this->assertStringContainsString('Total space that would be freed:', $output);
    }

    /** @test */
    public function it_handles_no_files_to_cleanup()
    {
        // Mock the BackupStorageManager
        $mockManager = Mockery::mock(BackupStorageManager::class);
        
        $cleanupResult = [
            'success' => true,
            'deleted_files' => [],
            'errors' => [],
            'retention' => [
                'database' => 30,
                'files' => 14
            ]
        ];
        
        $mockManager->shouldReceive('cleanupOldBackups')
            ->with(false)
            ->andReturn($cleanupResult);
        
        $this->app->instance(BackupStorageManager::class, $mockManager);

        // Run the command
        $exitCode = Artisan::call('backup:cleanup');

        // Assert success
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Cleanup completed successfully', $output);
        $this->assertStringContainsString('No files found for cleanup', $output);
        $this->assertStringContainsString('Current retention policy:', $output);
        $this->assertStringContainsString('Database backups: 30 days', $output);
        $this->assertStringContainsString('File backups: 14 days', $output);
    }

    /** @test */
    public function it_displays_errors_when_cleanup_has_issues()
    {
        // Mock the BackupStorageManager
        $mockManager = Mockery::mock(BackupStorageManager::class);
        
        $cleanupResult = [
            'success' => true,
            'deleted_files' => [
                [
                    'name' => 'deleted-backup.sql',
                    'type' => 'database',
                    'size' => 1048576,
                    'age_days' => 35
                ]
            ],
            'errors' => [
                'Failed to delete /path/to/locked-file.sql: Permission denied',
                'Failed to delete /path/to/missing-file.zip: File not found'
            ],
            'retention' => [
                'database' => 30,
                'files' => 14
            ]
        ];
        
        $mockManager->shouldReceive('cleanupOldBackups')
            ->with(false)
            ->andReturn($cleanupResult);
        
        $this->app->instance(BackupStorageManager::class, $mockManager);

        // Run the command
        $exitCode = Artisan::call('backup:cleanup');

        // Assert success but with warnings
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Cleanup completed successfully', $output);
        $this->assertStringContainsString('Errors encountered:', $output);
        $this->assertStringContainsString('Permission denied', $output);
        $this->assertStringContainsString('File not found', $output);
    }

    /** @test */
    public function it_handles_cleanup_failure()
    {
        // Mock the BackupStorageManager to return failure
        $mockManager = Mockery::mock(BackupStorageManager::class);
        
        $cleanupResult = [
            'success' => false,
            'error' => 'Storage directory not accessible'
        ];
        
        $mockManager->shouldReceive('cleanupOldBackups')
            ->with(false)
            ->andReturn($cleanupResult);
        
        $this->app->instance(BackupStorageManager::class, $mockManager);

        // Run the command
        $exitCode = Artisan::call('backup:cleanup');

        // Assert failure
        $this->assertEquals(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Cleanup failed: Storage directory not accessible', $output);
    }

    /** @test */
    public function it_handles_cleanup_exception()
    {
        // Mock the BackupStorageManager to throw exception
        $mockManager = Mockery::mock(BackupStorageManager::class);
        
        $mockManager->shouldReceive('cleanupOldBackups')
            ->with(false)
            ->andThrow(new \Exception('Unexpected cleanup error'));
        
        $this->app->instance(BackupStorageManager::class, $mockManager);

        // Run the command
        $exitCode = Artisan::call('backup:cleanup');

        // Assert failure
        $this->assertEquals(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Cleanup failed with exception: Unexpected cleanup error', $output);
    }

    /** @test */
    public function it_displays_file_information_in_table_format()
    {
        // Mock the BackupStorageManager
        $mockManager = Mockery::mock(BackupStorageManager::class);
        
        $cleanupResult = [
            'success' => true,
            'deleted_files' => [
                [
                    'name' => 'backup-2023-01-01.sql',
                    'type' => 'database',
                    'size' => 1048576, // 1MB
                    'age_days' => 35
                ],
                [
                    'name' => 'files-2023-01-01.zip',
                    'type' => 'files',
                    'size' => 2097152, // 2MB
                    'age_days' => 20
                ]
            ],
            'errors' => [],
            'retention' => [
                'database' => 30,
                'files' => 14
            ]
        ];
        
        $mockManager->shouldReceive('cleanupOldBackups')
            ->with(false)
            ->andReturn($cleanupResult);
        
        $this->app->instance(BackupStorageManager::class, $mockManager);

        // Run the command
        $exitCode = Artisan::call('backup:cleanup');

        // Assert success and table format
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('File Name', $output);
        $this->assertStringContainsString('Type', $output);
        $this->assertStringContainsString('Size', $output);
        $this->assertStringContainsString('Age', $output);
        $this->assertStringContainsString('backup-2023-01-01.sql', $output);
        $this->assertStringContainsString('files-2023-01-01.zip', $output);
        $this->assertStringContainsString('1 MB', $output);
        $this->assertStringContainsString('2 MB', $output);
        $this->assertStringContainsString('35 days old', $output);
        $this->assertStringContainsString('20 days old', $output);
    }

    /** @test */
    public function it_formats_file_sizes_correctly()
    {
        // Test different file sizes
        $testCases = [
            ['size' => 512, 'expected' => '512 B'],
            ['size' => 1024, 'expected' => '1 KB'],
            ['size' => 1048576, 'expected' => '1 MB'],
            ['size' => 1073741824, 'expected' => '1 GB'],
        ];

        foreach ($testCases as $testCase) {
            $mockManager = Mockery::mock(BackupStorageManager::class);
            
            $cleanupResult = [
                'success' => true,
                'deleted_files' => [
                    [
                        'name' => 'test-backup.sql',
                        'type' => 'database',
                        'size' => $testCase['size'],
                        'age_days' => 35
                    ]
                ],
                'errors' => [],
                'retention' => ['database' => 30, 'files' => 14]
            ];
            
            $mockManager->shouldReceive('cleanupOldBackups')->andReturn($cleanupResult);
            $this->app->instance(BackupStorageManager::class, $mockManager);

            // Run the command
            Artisan::call('backup:cleanup');
            $output = Artisan::output();
            
            // Assert formatted size appears in output
            $this->assertStringContainsString($testCase['expected'], $output);
        }
    }
}