<?php

namespace Tests\Unit;

use App\Services\BackupConfig;
use App\Services\FileBackupHandler;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FileBackupHandlerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private FileBackupHandler $handler;
    private BackupConfig $config;
    private string $preAlertsDirectory;
    private string $receiptsDirectory;
    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = app(BackupConfig::class);
        $this->handler = new FileBackupHandler($this->config);

        // Set up test directories that mirror the actual application structure
        $this->preAlertsDirectory = storage_path('testing-integration/app/public/pre-alerts');
        $this->receiptsDirectory = storage_path('testing-integration/app/public/receipts');
        $this->backupPath = storage_path('testing-integration/backups');

        // Clean up any existing test directories first
        if (File::exists(storage_path('testing-integration'))) {
            File::deleteDirectory(storage_path('testing-integration'));
        }

        // Create test directories
        File::makeDirectory($this->preAlertsDirectory, 0755, true);
        File::makeDirectory($this->receiptsDirectory, 0755, true);
        File::makeDirectory($this->backupPath, 0755, true);

        // Create test files in pre-alerts directory
        File::put($this->preAlertsDirectory . '/pre_alert_001.pdf', 'Pre-alert document content 1');
        File::put($this->preAlertsDirectory . '/pre_alert_002.pdf', 'Pre-alert document content 2');
        File::makeDirectory($this->preAlertsDirectory . '/2024', 0755, true);
        File::put($this->preAlertsDirectory . '/2024/pre_alert_003.pdf', 'Pre-alert document content 3');

        // Create test files in receipts directory
        File::put($this->receiptsDirectory . '/receipt_001.pdf', 'Receipt document content 1');
        File::put($this->receiptsDirectory . '/receipt_002.pdf', 'Receipt document content 2');
        File::makeDirectory($this->receiptsDirectory . '/2024', 0755, true);
        File::put($this->receiptsDirectory . '/2024/receipt_003.pdf', 'Receipt document content 3');

        // Mock config to use test paths
        config(['backup.storage.path' => $this->backupPath]);
        config(['backup.files.compression_level' => 6]);
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        if (File::exists(storage_path('testing-integration'))) {
            File::deleteDirectory(storage_path('testing-integration'));
        }

        parent::tearDown();
    }

    public function test_backup_pre_alerts_directory()
    {
        Log::shouldReceive('info')->atLeast()->once();

        $archivePath = $this->handler->backupDirectory($this->preAlertsDirectory, 'pre_alerts_backup');

        $this->assertFileExists($archivePath);
        $this->assertStringContainsString('pre_alerts_backup', $archivePath);
        $this->assertStringEndsWith('.zip', $archivePath);
        $this->assertGreaterThan(0, File::size($archivePath));

        // Verify archive contents
        $this->assertTrue($this->handler->validateArchive($archivePath));
        
        $contents = $this->handler->getArchiveContents($archivePath);
        $fileNames = array_column($contents, 'name');
        
        $this->assertContains('pre-alerts/pre_alert_001.pdf', $fileNames);
        $this->assertContains('pre-alerts/pre_alert_002.pdf', $fileNames);
        $this->assertContains('pre-alerts/2024/pre_alert_003.pdf', $fileNames);
    }

    public function test_backup_receipts_directory()
    {
        Log::shouldReceive('info')->atLeast()->once();

        $archivePath = $this->handler->backupDirectory($this->receiptsDirectory, 'receipts_backup');

        $this->assertFileExists($archivePath);
        $this->assertStringContainsString('receipts_backup', $archivePath);
        $this->assertStringEndsWith('.zip', $archivePath);
        $this->assertGreaterThan(0, File::size($archivePath));

        // Verify archive contents
        $this->assertTrue($this->handler->validateArchive($archivePath));
        
        $contents = $this->handler->getArchiveContents($archivePath);
        $fileNames = array_column($contents, 'name');
        
        $this->assertContains('receipts/receipt_001.pdf', $fileNames);
        $this->assertContains('receipts/receipt_002.pdf', $fileNames);
        $this->assertContains('receipts/2024/receipt_003.pdf', $fileNames);
    }

    public function test_backup_multiple_directories_for_pre_restore()
    {
        Log::shouldReceive('info')->atLeast()->once();

        $directories = [$this->preAlertsDirectory, $this->receiptsDirectory];
        $archivePath = $this->handler->createPreRestoreBackup($directories);

        $this->assertFileExists($archivePath);
        $this->assertStringContainsString('pre_restore_backup_', $archivePath);
        $this->assertStringEndsWith('.zip', $archivePath);
        $this->assertGreaterThan(0, File::size($archivePath));

        // Verify archive contains files from both directories
        $contents = $this->handler->getArchiveContents($archivePath);
        $fileNames = array_column($contents, 'name');
        
        // Check pre-alerts files
        $this->assertContains('pre-alerts/pre_alert_001.pdf', $fileNames);
        $this->assertContains('pre-alerts/2024/pre_alert_003.pdf', $fileNames);
        
        // Check receipts files
        $this->assertContains('receipts/receipt_001.pdf', $fileNames);
        $this->assertContains('receipts/2024/receipt_003.pdf', $fileNames);
    }

    public function test_extract_and_restore_pre_alerts_directory()
    {
        Log::shouldReceive('info')->atLeast()->once();

        // Create backup
        $archivePath = $this->handler->backupDirectory($this->preAlertsDirectory);
        
        // Remove original directory
        File::deleteDirectory($this->preAlertsDirectory);
        $this->assertDirectoryDoesNotExist($this->preAlertsDirectory);

        // Extract to restore
        $extractionPath = storage_path('testing-integration/restored');
        $result = $this->handler->extractArchive($archivePath, $extractionPath);

        $this->assertTrue($result);
        $this->assertDirectoryExists($extractionPath);
        $this->assertFileExists($extractionPath . '/pre-alerts/pre_alert_001.pdf');
        $this->assertFileExists($extractionPath . '/pre-alerts/pre_alert_002.pdf');
        $this->assertFileExists($extractionPath . '/pre-alerts/2024/pre_alert_003.pdf');

        // Verify file contents
        $this->assertEquals(
            'Pre-alert document content 1',
            File::get($extractionPath . '/pre-alerts/pre_alert_001.pdf')
        );
    }

    public function test_backup_with_configured_compression_level()
    {
        Log::shouldReceive('info')->atLeast()->once();

        // Test with different compression levels
        config(['backup.files.compression_level' => 9]); // Maximum compression
        
        $archivePath = $this->handler->backupDirectory($this->preAlertsDirectory);
        
        $this->assertFileExists($archivePath);
        $this->assertTrue($this->handler->validateArchive($archivePath));
        $this->assertGreaterThan(0, File::size($archivePath));
    }

    public function test_backup_handles_large_files()
    {
        Log::shouldReceive('info')->atLeast()->once();

        // Create a larger test file
        $largeFileContent = str_repeat('This is test content for a large file. ', 10000);
        File::put($this->preAlertsDirectory . '/large_pre_alert.pdf', $largeFileContent);

        $archivePath = $this->handler->backupDirectory($this->preAlertsDirectory);

        $this->assertFileExists($archivePath);
        $this->assertTrue($this->handler->validateArchive($archivePath));
        
        $contents = $this->handler->getArchiveContents($archivePath);
        $fileNames = array_column($contents, 'name');
        
        $this->assertContains('pre-alerts/large_pre_alert.pdf', $fileNames);
        
        // Find the large file in contents and verify its size
        $largeFileEntry = collect($contents)->firstWhere('name', 'pre-alerts/large_pre_alert.pdf');
        $this->assertNotNull($largeFileEntry);
        $this->assertGreaterThan(100000, $largeFileEntry['size']); // Should be > 100KB
    }

    public function test_backup_preserves_directory_structure()
    {
        Log::shouldReceive('info')->atLeast()->once();

        // Create nested directory structure
        File::makeDirectory($this->preAlertsDirectory . '/2024/january', 0755, true);
        File::makeDirectory($this->preAlertsDirectory . '/2024/february', 0755, true);
        File::put($this->preAlertsDirectory . '/2024/january/pre_alert_jan_001.pdf', 'January pre-alert');
        File::put($this->preAlertsDirectory . '/2024/february/pre_alert_feb_001.pdf', 'February pre-alert');

        $archivePath = $this->handler->backupDirectory($this->preAlertsDirectory);

        $this->assertFileExists($archivePath);
        
        $contents = $this->handler->getArchiveContents($archivePath);
        $fileNames = array_column($contents, 'name');
        
        $this->assertContains('pre-alerts/2024/january/pre_alert_jan_001.pdf', $fileNames);
        $this->assertContains('pre-alerts/2024/february/pre_alert_feb_001.pdf', $fileNames);

        // Test extraction preserves structure
        $extractionPath = storage_path('testing-integration/structure_test');
        $result = $this->handler->extractArchive($archivePath, $extractionPath);

        $this->assertTrue($result);
        $this->assertFileExists($extractionPath . '/pre-alerts/2024/january/pre_alert_jan_001.pdf');
        $this->assertFileExists($extractionPath . '/pre-alerts/2024/february/pre_alert_feb_001.pdf');
    }

    public function test_backup_error_handling_with_permission_issues()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Directory does not exist');

        // This should fail because the directory doesn't exist
        $this->handler->backupDirectory('/nonexistent/directory/path');
    }
}