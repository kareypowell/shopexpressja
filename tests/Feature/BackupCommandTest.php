<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Services\BackupService;
use App\Services\BackupResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Mockery;

class BackupCommandTest extends TestCase
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
    public function it_can_create_a_full_backup_successfully()
    {
        // Mock the BackupService
        $mockService = Mockery::mock(BackupService::class);
        $mockResult = Mockery::mock(BackupResult::class);
        
        $mockResult->shouldReceive('isSuccess')->andReturn(true);
        $mockResult->shouldReceive('getType')->andReturn('full');
        $mockResult->shouldReceive('getFilePath')->andReturn('/path/to/backup.sql');
        $mockResult->shouldReceive('getFileSize')->andReturn(1024000);
        $mockResult->shouldReceive('getDuration')->andReturn(30);
        $mockResult->shouldReceive('getCreatedAt')->andReturn(now());
        
        $mockService->shouldReceive('createManualBackup')
            ->with(['type' => 'full'])
            ->andReturn($mockResult);
        
        $this->app->bind(BackupService::class, function () use ($mockService) {
            return $mockService;
        });

        // Run the command
        $exitCode = Artisan::call('backup:create');

        // Assert success
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Starting backup process', $output);
        $this->assertStringContainsString('Backup completed successfully', $output);
        $this->assertStringContainsString('full', $output);
    }

    /** @test */
    public function it_can_create_database_only_backup()
    {
        // Mock the BackupService
        $mockService = Mockery::mock(BackupService::class);
        $mockResult = Mockery::mock(BackupResult::class);
        
        $mockResult->shouldReceive('isSuccess')->andReturn(true);
        $mockResult->shouldReceive('getType')->andReturn('database');
        $mockResult->shouldReceive('getFilePath')->andReturn('/path/to/database.sql');
        $mockResult->shouldReceive('getFileSize')->andReturn(512000);
        $mockResult->shouldReceive('getDuration')->andReturn(15);
        $mockResult->shouldReceive('getCreatedAt')->andReturn(now());
        
        $mockService->shouldReceive('createManualBackup')
            ->with(['type' => 'database'])
            ->andReturn($mockResult);
        
        $this->app->bind(BackupService::class, function () use ($mockService) {
            return $mockService;
        });

        // Run the command with database option
        $exitCode = Artisan::call('backup:create', ['--database' => true]);

        // Assert success
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Backup completed successfully', $output);
        $this->assertStringContainsString('database', $output);
    }

    /** @test */
    public function it_can_create_files_only_backup()
    {
        // Mock the BackupService
        $mockService = Mockery::mock(BackupService::class);
        $mockResult = Mockery::mock(BackupResult::class);
        
        $mockResult->shouldReceive('isSuccess')->andReturn(true);
        $mockResult->shouldReceive('getType')->andReturn('files');
        $mockResult->shouldReceive('getFilePath')->andReturn('/path/to/files.zip');
        $mockResult->shouldReceive('getFileSize')->andReturn(256000);
        $mockResult->shouldReceive('getDuration')->andReturn(10);
        $mockResult->shouldReceive('getCreatedAt')->andReturn(now());
        
        $mockService->shouldReceive('createManualBackup')
            ->with(['type' => 'files'])
            ->andReturn($mockResult);
        
        $this->app->bind(BackupService::class, function () use ($mockService) {
            return $mockService;
        });

        // Run the command with files option
        $exitCode = Artisan::call('backup:create', ['--files' => true]);

        // Assert success
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Backup completed successfully', $output);
        $this->assertStringContainsString('files', $output);
    }

    /** @test */
    public function it_can_create_backup_with_custom_name()
    {
        // Mock the BackupService
        $mockService = Mockery::mock(BackupService::class);
        $mockResult = Mockery::mock(BackupResult::class);
        
        $mockResult->shouldReceive('isSuccess')->andReturn(true);
        $mockResult->shouldReceive('getType')->andReturn('full');
        $mockResult->shouldReceive('getFilePath')->andReturn('/path/to/custom-backup.sql');
        $mockResult->shouldReceive('getFileSize')->andReturn(1024000);
        $mockResult->shouldReceive('getDuration')->andReturn(30);
        $mockResult->shouldReceive('getCreatedAt')->andReturn(now());
        
        $mockService->shouldReceive('createManualBackup')
            ->with(['type' => 'full', 'name' => 'custom-backup'])
            ->andReturn($mockResult);
        
        $this->app->bind(BackupService::class, function () use ($mockService) {
            return $mockService;
        });

        // Run the command with custom name
        $exitCode = Artisan::call('backup:create', ['--name' => 'custom-backup']);

        // Assert success
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Backup completed successfully', $output);
    }

    /** @test */
    public function it_fails_when_both_database_and_files_options_are_specified()
    {
        // Run the command with both options
        $exitCode = Artisan::call('backup:create', [
            '--database' => true,
            '--files' => true
        ]);

        // Assert failure
        $this->assertEquals(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Cannot specify both --database and --files options', $output);
    }

    /** @test */
    public function it_handles_backup_service_failure()
    {
        // Mock the BackupService to return failure
        $mockService = Mockery::mock(BackupService::class);
        $mockResult = Mockery::mock(BackupResult::class);
        
        $mockResult->shouldReceive('isSuccess')->andReturn(false);
        $mockResult->shouldReceive('getErrorMessage')->andReturn('Database connection failed');
        
        $mockService->shouldReceive('createManualBackup')
            ->with(['type' => 'full'])
            ->andReturn($mockResult);
        
        $this->app->bind(BackupService::class, function () use ($mockService) {
            return $mockService;
        });

        // Run the command
        $exitCode = Artisan::call('backup:create');

        // Assert failure
        $this->assertEquals(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Backup failed: Database connection failed', $output);
    }

    /** @test */
    public function it_handles_backup_service_exception()
    {
        // Mock the BackupService to throw exception
        $mockService = Mockery::mock(BackupService::class);
        
        $mockService->shouldReceive('createManualBackup')
            ->with(['type' => 'full'])
            ->andThrow(new \Exception('Unexpected error occurred'));
        
        $this->app->bind(BackupService::class, function () use ($mockService) {
            return $mockService;
        });

        // Run the command
        $exitCode = Artisan::call('backup:create');

        // Assert failure
        $this->assertEquals(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Backup failed with exception: Unexpected error occurred', $output);
    }

    /** @test */
    public function it_displays_backup_details_in_table_format()
    {
        // Mock the BackupService
        $mockService = Mockery::mock(BackupService::class);
        $mockResult = Mockery::mock(BackupResult::class);
        
        $mockResult->shouldReceive('isSuccess')->andReturn(true);
        $mockResult->shouldReceive('getType')->andReturn('database');
        $mockResult->shouldReceive('getFilePath')->andReturn('/path/to/backup.sql');
        $mockResult->shouldReceive('getFileSize')->andReturn(1048576); // 1MB
        $mockResult->shouldReceive('getDuration')->andReturn(45);
        $mockResult->shouldReceive('getCreatedAt')->andReturn(now());
        
        $mockService->shouldReceive('createManualBackup')
            ->andReturn($mockResult);
        
        $this->app->bind(BackupService::class, function () use ($mockService) {
            return $mockService;
        });

        // Run the command
        $exitCode = Artisan::call('backup:create');

        // Assert success and table output
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Backup Type', $output);
        $this->assertStringContainsString('File Path', $output);
        $this->assertStringContainsString('File Size', $output);
        $this->assertStringContainsString('Duration', $output);
        $this->assertStringContainsString('1 MB', $output); // Formatted file size
    }

    /** @test */
    public function it_formats_file_sizes_correctly()
    {
        // Mock the BackupService with different file sizes
        $testCases = [
            ['size' => 512, 'expected' => '512 B'],
            ['size' => 1024, 'expected' => '1 KB'],
            ['size' => 1048576, 'expected' => '1 MB'],
            ['size' => 1073741824, 'expected' => '1 GB'],
        ];

        foreach ($testCases as $testCase) {
            $mockService = Mockery::mock(BackupService::class);
            $mockResult = Mockery::mock(BackupResult::class);
            
            $mockResult->shouldReceive('isSuccess')->andReturn(true);
            $mockResult->shouldReceive('getType')->andReturn('database');
            $mockResult->shouldReceive('getFilePath')->andReturn('/path/to/backup.sql');
            $mockResult->shouldReceive('getFileSize')->andReturn($testCase['size']);
            $mockResult->shouldReceive('getDuration')->andReturn(30);
            $mockResult->shouldReceive('getCreatedAt')->andReturn(now());
            
            $mockService->shouldReceive('createManualBackup')->andReturn($mockResult);
            $this->app->bind(BackupService::class, function () use ($mockService) {
                return $mockService;
            });

            // Run the command
            Artisan::call('backup:create');
            $output = Artisan::output();
            
            // Assert formatted size appears in output
            $this->assertStringContainsString($testCase['expected'], $output);
        }
    }
}