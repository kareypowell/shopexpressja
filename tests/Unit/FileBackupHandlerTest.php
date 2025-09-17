<?php

namespace Tests\Unit;

use App\Services\BackupConfig;
use App\Services\FileBackupHandler;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use ZipArchive;

class FileBackupHandlerTest extends TestCase
{
    use RefreshDatabase;

    private FileBackupHandler $handler;
    private BackupConfig $config;
    private string $testDirectory;
    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(BackupConfig::class);
        $this->handler = new FileBackupHandler($this->config);

        // Set up test directories
        $this->testDirectory = storage_path('testing-unit/file-backup-test');
        $this->backupPath = storage_path('testing-unit/backups');

        // Mock config methods
        $this->config->method('getStoragePath')->willReturn($this->backupPath);
        $this->config->method('getCompressionLevel')->willReturn(6);

        // Clean up any existing test directories first
        if (File::exists(storage_path('testing-unit'))) {
            File::deleteDirectory(storage_path('testing-unit'));
        }

        // Create test directories
        File::makeDirectory($this->testDirectory, 0755, true);
        File::makeDirectory($this->backupPath, 0755, true);

        // Create test files
        File::put($this->testDirectory . '/test1.txt', 'Test file content 1');
        File::put($this->testDirectory . '/test2.txt', 'Test file content 2');
        File::makeDirectory($this->testDirectory . '/subdir', 0755, true);
        File::put($this->testDirectory . '/subdir/test3.txt', 'Test file content 3');
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        if (File::exists(storage_path('testing-unit'))) {
            File::deleteDirectory(storage_path('testing-unit'));
        }

        parent::tearDown();
    }

    public function test_backup_directory_creates_valid_archive()
    {
        Log::shouldReceive('info')->once();

        $archivePath = $this->handler->backupDirectory($this->testDirectory);

        $this->assertFileExists($archivePath);
        $this->assertStringContainsString('file-backup-test_backup_', $archivePath);
        $this->assertStringEndsWith('.zip', $archivePath);
        $this->assertGreaterThan(0, File::size($archivePath));
    }

    public function test_backup_directory_with_custom_name()
    {
        Log::shouldReceive('info')->once();

        $customName = 'custom_backup_name';
        $archivePath = $this->handler->backupDirectory($this->testDirectory, $customName);

        $this->assertFileExists($archivePath);
        $this->assertStringContainsString($customName, $archivePath);
        $this->assertStringEndsWith('.zip', $archivePath);
    }

    public function test_backup_directory_adds_zip_extension_if_missing()
    {
        Log::shouldReceive('info')->once();

        $customName = 'custom_backup_name';
        $archivePath = $this->handler->backupDirectory($this->testDirectory, $customName);

        $this->assertStringEndsWith('.zip', $archivePath);
    }

    public function test_backup_directory_throws_exception_for_nonexistent_directory()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Directory does not exist');

        $this->handler->backupDirectory('/nonexistent/directory');
    }

    public function test_backup_directory_throws_exception_for_file_instead_of_directory()
    {
        $filePath = $this->testDirectory . '/not_a_directory.txt';
        File::put($filePath, 'This is a file, not a directory');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Path is not a directory');

        $this->handler->backupDirectory($filePath);
    }

    public function test_validate_archive_returns_true_for_valid_archive()
    {
        Log::shouldReceive('info')->twice(); // Once for backup creation, once for validation

        $archivePath = $this->handler->backupDirectory($this->testDirectory);
        $isValid = $this->handler->validateArchive($archivePath);

        $this->assertTrue($isValid);
    }

    public function test_validate_archive_returns_false_for_nonexistent_file()
    {
        Log::shouldReceive('warning')->once();

        $isValid = $this->handler->validateArchive('/nonexistent/archive.zip');

        $this->assertFalse($isValid);
    }

    public function test_validate_archive_returns_false_for_empty_file()
    {
        Log::shouldReceive('warning')->once();

        $emptyArchivePath = $this->backupPath . '/empty.zip';
        File::put($emptyArchivePath, '');

        $isValid = $this->handler->validateArchive($emptyArchivePath);

        $this->assertFalse($isValid);
    }

    public function test_validate_archive_returns_false_for_corrupted_archive()
    {
        Log::shouldReceive('warning')->once();

        $corruptedArchivePath = $this->backupPath . '/corrupted.zip';
        File::put($corruptedArchivePath, 'This is not a valid ZIP file');

        $isValid = $this->handler->validateArchive($corruptedArchivePath);

        $this->assertFalse($isValid);
    }

    public function test_extract_archive_successfully_extracts_files()
    {
        Log::shouldReceive('info')->atLeast()->once();

        $archivePath = $this->handler->backupDirectory($this->testDirectory);
        $extractionPath = storage_path('testing-unit/extraction');

        $result = $this->handler->extractArchive($archivePath, $extractionPath);

        $this->assertTrue($result);
        $this->assertDirectoryExists($extractionPath);
        $this->assertFileExists($extractionPath . '/file-backup-test/test1.txt');
        $this->assertFileExists($extractionPath . '/file-backup-test/test2.txt');
        $this->assertFileExists($extractionPath . '/file-backup-test/subdir/test3.txt');
    }

    public function test_extract_archive_fails_for_invalid_archive()
    {
        Log::shouldReceive('warning')->once();
        Log::shouldReceive('error')->once();

        $extractionPath = storage_path('testing-unit/extraction');
        $result = $this->handler->extractArchive('/nonexistent/archive.zip', $extractionPath);

        $this->assertFalse($result);
    }

    public function test_get_archive_contents_returns_file_list()
    {
        Log::shouldReceive('info')->twice(); // Once for backup creation, once for validation

        $archivePath = $this->handler->backupDirectory($this->testDirectory);
        $contents = $this->handler->getArchiveContents($archivePath);

        $this->assertIsArray($contents);
        $this->assertNotEmpty($contents);
        
        $fileNames = array_column($contents, 'name');
        $this->assertContains('file-backup-test/test1.txt', $fileNames);
        $this->assertContains('file-backup-test/test2.txt', $fileNames);
        $this->assertContains('file-backup-test/subdir/test3.txt', $fileNames);

        // Check that each file entry has required fields
        foreach ($contents as $file) {
            $this->assertArrayHasKey('name', $file);
            $this->assertArrayHasKey('size', $file);
            $this->assertArrayHasKey('compressed_size', $file);
            $this->assertArrayHasKey('mtime', $file);
        }
    }

    public function test_get_archive_contents_returns_empty_array_for_invalid_archive()
    {
        Log::shouldReceive('warning')->once();

        $contents = $this->handler->getArchiveContents('/nonexistent/archive.zip');

        $this->assertIsArray($contents);
        $this->assertEmpty($contents);
    }

    public function test_create_pre_restore_backup_creates_archive_with_multiple_directories()
    {
        Log::shouldReceive('info')->atLeast()->once();

        // Create additional test directory
        $testDirectory2 = storage_path('testing-unit/file-backup-test2');
        File::makeDirectory($testDirectory2, 0755, true);
        File::put($testDirectory2 . '/test4.txt', 'Test file content 4');

        $directories = [$this->testDirectory, $testDirectory2];
        $archivePath = $this->handler->createPreRestoreBackup($directories);

        $this->assertFileExists($archivePath);
        $this->assertStringContainsString('pre_restore_backup_', $archivePath);
        $this->assertStringEndsWith('.zip', $archivePath);
        $this->assertGreaterThan(0, File::size($archivePath));

        // Verify contents include files from both directories
        $contents = $this->handler->getArchiveContents($archivePath);
        $fileNames = array_column($contents, 'name');
        
        $this->assertContains('file-backup-test/test1.txt', $fileNames);
        $this->assertContains('file-backup-test2/test4.txt', $fileNames);
    }

    public function test_create_pre_restore_backup_handles_nonexistent_directories()
    {
        Log::shouldReceive('info')->atLeast()->once();

        $directories = [$this->testDirectory, '/nonexistent/directory'];
        $archivePath = $this->handler->createPreRestoreBackup($directories);

        $this->assertFileExists($archivePath);
        
        // Should only contain files from existing directory
        $contents = $this->handler->getArchiveContents($archivePath);
        $fileNames = array_column($contents, 'name');
        
        $this->assertContains('file-backup-test/test1.txt', $fileNames);
    }

    public function test_get_archive_size_returns_correct_size()
    {
        Log::shouldReceive('info')->once();

        $archivePath = $this->handler->backupDirectory($this->testDirectory);
        $size = $this->handler->getArchiveSize($archivePath);

        $this->assertGreaterThan(0, $size);
        $this->assertEquals(File::size($archivePath), $size);
    }

    public function test_get_archive_size_returns_zero_for_nonexistent_file()
    {
        $size = $this->handler->getArchiveSize('/nonexistent/archive.zip');

        $this->assertEquals(0, $size);
    }

    public function test_delete_archive_successfully_deletes_file()
    {
        Log::shouldReceive('info')->twice(); // Once for backup creation, once for deletion

        $archivePath = $this->handler->backupDirectory($this->testDirectory);
        $this->assertFileExists($archivePath);

        $result = $this->handler->deleteArchive($archivePath);

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($archivePath);
    }

    public function test_delete_archive_returns_true_for_nonexistent_file()
    {
        $result = $this->handler->deleteArchive('/nonexistent/archive.zip');

        $this->assertTrue($result);
    }

    public function test_backup_directory_creates_backup_directory_if_not_exists()
    {
        Log::shouldReceive('info')->once();

        // Remove backup directory
        File::deleteDirectory($this->backupPath);
        $this->assertDirectoryDoesNotExist($this->backupPath);

        $archivePath = $this->handler->backupDirectory($this->testDirectory);

        $this->assertDirectoryExists($this->backupPath);
        $this->assertFileExists($archivePath);
    }

    public function test_backup_directory_includes_subdirectories_and_files()
    {
        Log::shouldReceive('info')->twice(); // Once for backup creation, once for validation

        // Create nested directory structure
        File::makeDirectory($this->testDirectory . '/level1/level2', 0755, true);
        File::put($this->testDirectory . '/level1/level2/deep_file.txt', 'Deep file content');

        $archivePath = $this->handler->backupDirectory($this->testDirectory);
        $contents = $this->handler->getArchiveContents($archivePath);
        $fileNames = array_column($contents, 'name');

        $this->assertContains('file-backup-test/level1/level2/deep_file.txt', $fileNames);
    }

    public function test_backup_directory_handles_empty_directory()
    {
        Log::shouldReceive('info')->once();

        $emptyDirectory = storage_path('testing-unit/empty-directory');
        File::makeDirectory($emptyDirectory, 0755, true);

        $archivePath = $this->handler->backupDirectory($emptyDirectory);

        $this->assertFileExists($archivePath);
        $this->assertGreaterThan(0, File::size($archivePath)); // ZIP file has header even if empty
    }

    public function test_backup_directory_uses_configured_compression_level()
    {
        // Mock different compression levels
        $this->config = $this->createMock(BackupConfig::class);
        $this->config->method('getStoragePath')->willReturn($this->backupPath);
        $this->config->method('getCompressionLevel')->willReturn(9); // Maximum compression

        $handler = new FileBackupHandler($this->config);

        Log::shouldReceive('info')->once();

        $archivePath = $handler->backupDirectory($this->testDirectory);

        $this->assertFileExists($archivePath);
        $this->assertGreaterThan(0, File::size($archivePath));
    }
}