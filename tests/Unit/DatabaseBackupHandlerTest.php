<?php

namespace Tests\Unit;

use App\Services\BackupConfig;
use App\Services\DatabaseBackupHandler;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class DatabaseBackupHandlerTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseBackupHandler $handler;
    private BackupConfig $mockConfig;
    private string $testBackupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testBackupPath = storage_path('app/test-backups');
        
        // Create test backup directory
        if (!is_dir($this->testBackupPath)) {
            mkdir($this->testBackupPath, 0755, true);
        }

        $this->mockConfig = Mockery::mock(BackupConfig::class);
        $this->mockConfig->shouldReceive('getStoragePath')
            ->andReturn($this->testBackupPath);
        $this->mockConfig->shouldReceive('isDatabaseSingleTransaction')
            ->andReturn(true);
        $this->mockConfig->shouldReceive('includeDatabaseRoutines')
            ->andReturn(true);
        $this->mockConfig->shouldReceive('includeDatabaseTriggers')
            ->andReturn(true);

        $this->handler = new DatabaseBackupHandler($this->mockConfig);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (is_dir($this->testBackupPath)) {
            $files = glob($this->testBackupPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testBackupPath);
        }

        Mockery::close();
        parent::tearDown();
    }

    public function test_create_dump_generates_timestamped_filename()
    {
        // Create a testable handler that mocks command execution
        $handler = new class($this->mockConfig) extends DatabaseBackupHandler {
            protected function executeCommand(string $command): array
            {
                // Simulate successful mysqldump execution
                $outputPath = $this->extractOutputPath($command);
                file_put_contents($outputPath, $this->getValidDumpContent());
                return ['output' => [], 'return_code' => 0];
            }
            
            private function extractOutputPath(string $command): string
            {
                preg_match('/> (.+)$/', $command, $matches);
                return trim($matches[1], "'\"");
            }
            
            private function getValidDumpContent(): string
            {
                return "-- MySQL dump 10.13\nCREATE TABLE `test` (`id` int);\nINSERT INTO `test` VALUES (1);\n";
            }
        };

        $filename = $handler->createDump();

        $this->assertStringContainsString('database_backup_', basename($filename));
        $this->assertStringContainsString(date('Y-m-d'), basename($filename));
        $this->assertStringEndsWith('.sql', $filename);
        $this->assertFileExists($filename);
    }

    public function test_create_dump_with_custom_filename()
    {
        $customFilename = 'custom_backup_test.sql';
        
        // Create a testable handler that mocks command execution
        $handler = new class($this->mockConfig) extends DatabaseBackupHandler {
            protected function executeCommand(string $command): array
            {
                // Simulate successful mysqldump execution
                $outputPath = $this->extractOutputPath($command);
                file_put_contents($outputPath, $this->getValidDumpContent());
                return ['output' => [], 'return_code' => 0];
            }
            
            private function extractOutputPath(string $command): string
            {
                preg_match('/> (.+)$/', $command, $matches);
                return trim($matches[1], "'\"");
            }
            
            private function getValidDumpContent(): string
            {
                return "-- MySQL dump 10.13\nCREATE TABLE `test` (`id` int);\nINSERT INTO `test` VALUES (1);\n";
            }
        };

        $result = $handler->createDump($customFilename);
        $expectedPath = $this->testBackupPath . '/' . $customFilename;

        $this->assertEquals($expectedPath, $result);
        $this->assertFileExists($result);
    }

    public function test_validate_dump_returns_true_for_valid_dump()
    {
        $dumpPath = $this->testBackupPath . '/valid_dump.sql';
        $this->createValidMockDumpFile($dumpPath);

        $result = $this->handler->validateDump($dumpPath);

        $this->assertTrue($result);
    }

    public function test_validate_dump_returns_false_for_nonexistent_file()
    {
        $result = $this->handler->validateDump('/nonexistent/file.sql');

        $this->assertFalse($result);
    }

    public function test_validate_dump_returns_false_for_empty_file()
    {
        $dumpPath = $this->testBackupPath . '/empty_dump.sql';
        touch($dumpPath); // Create empty file

        $result = $this->handler->validateDump($dumpPath);

        $this->assertFalse($result);
    }

    public function test_validate_dump_returns_false_for_invalid_content()
    {
        $dumpPath = $this->testBackupPath . '/invalid_dump.sql';
        file_put_contents($dumpPath, 'This is not a valid SQL dump file');

        $result = $this->handler->validateDump($dumpPath);

        $this->assertFalse($result);
    }

    public function test_validate_dump_recognizes_mysql_dump_headers()
    {
        $dumpPath = $this->testBackupPath . '/mysql_dump.sql';
        $content = "-- MySQL dump 10.13  Distrib 8.0.23, for Linux (x86_64)\n";
        $content .= "-- Host: localhost    Database: test_db\n";
        $content .= "SET @@GLOBAL.GTID_PURGED='';\n";
        file_put_contents($dumpPath, $content);

        $result = $this->handler->validateDump($dumpPath);

        $this->assertTrue($result);
    }

    public function test_validate_dump_recognizes_create_table_statements()
    {
        $dumpPath = $this->testBackupPath . '/create_table_dump.sql';
        $content = "SET NAMES utf8mb4;\n";
        $content .= "CREATE TABLE `users` (\n";
        $content .= "  `id` bigint unsigned NOT NULL AUTO_INCREMENT,\n";
        $content .= "  PRIMARY KEY (`id`)\n";
        $content .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";
        file_put_contents($dumpPath, $content);

        $result = $this->handler->validateDump($dumpPath);

        $this->assertTrue($result);
    }

    public function test_validate_dump_recognizes_insert_statements()
    {
        $dumpPath = $this->testBackupPath . '/insert_dump.sql';
        $content = "SET NAMES utf8mb4;\n";
        $content .= "INSERT INTO `users` VALUES (1,'John Doe','john@example.com');\n";
        file_put_contents($dumpPath, $content);

        $result = $this->handler->validateDump($dumpPath);

        $this->assertTrue($result);
    }

    public function test_get_dump_size_returns_correct_size()
    {
        $dumpPath = $this->testBackupPath . '/size_test.sql';
        $content = 'SELECT * FROM users;';
        file_put_contents($dumpPath, $content);

        $size = $this->handler->getDumpSize($dumpPath);

        $this->assertEquals(strlen($content), $size);
    }

    public function test_get_dump_size_returns_zero_for_nonexistent_file()
    {
        $size = $this->handler->getDumpSize('/nonexistent/file.sql');

        $this->assertEquals(0, $size);
    }

    public function test_create_dump_creates_backup_directory_if_not_exists()
    {
        $nonExistentPath = storage_path('app/new-backup-dir');
        
        // Clean up first if directory exists
        if (is_dir($nonExistentPath)) {
            $files = glob($nonExistentPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($nonExistentPath);
        }

        $mockConfig = Mockery::mock(BackupConfig::class);
        $mockConfig->shouldReceive('getStoragePath')->andReturn($nonExistentPath);
        $mockConfig->shouldReceive('isDatabaseSingleTransaction')->andReturn(true);
        $mockConfig->shouldReceive('includeDatabaseRoutines')->andReturn(true);
        $mockConfig->shouldReceive('includeDatabaseTriggers')->andReturn(true);

        $this->assertDirectoryDoesNotExist($nonExistentPath);

        // Create a testable handler that mocks command execution
        $handler = new class($mockConfig) extends DatabaseBackupHandler {
            protected function executeCommand(string $command): array
            {
                // Simulate successful mysqldump execution
                $outputPath = $this->extractOutputPath($command);
                file_put_contents($outputPath, $this->getValidDumpContent());
                return ['output' => [], 'return_code' => 0];
            }
            
            private function extractOutputPath(string $command): string
            {
                preg_match('/> (.+)$/', $command, $matches);
                return trim($matches[1], "'\"");
            }
            
            private function getValidDumpContent(): string
            {
                return "-- MySQL dump 10.13\nCREATE TABLE `test` (`id` int);\nINSERT INTO `test` VALUES (1);\n";
            }
        };
        
        try {
            $result = $handler->createDump('test_backup.sql');
            
            // Verify directory was created
            $this->assertDirectoryExists($nonExistentPath);
            $this->assertFileExists($result);
            
        } finally {
            // Clean up
            if (file_exists($result ?? '')) {
                unlink($result);
            }
            if (is_dir($nonExistentPath)) {
                rmdir($nonExistentPath);
            }
        }
    }

    public function test_validate_dump_handles_file_read_errors_gracefully()
    {
        // Skip this test on systems where chmod doesn't work as expected
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('File permission tests not reliable on Windows');
        }

        Log::shouldReceive('warning')->atLeast()->once();

        // Create a file and then make it unreadable
        $dumpPath = $this->testBackupPath . '/unreadable.sql';
        file_put_contents($dumpPath, 'test content');
        
        // Try to make file unreadable (may not work on all systems)
        $originalPerms = fileperms($dumpPath);
        chmod($dumpPath, 0000);

        $result = $this->handler->validateDump($dumpPath);

        $this->assertFalse($result);

        // Restore permissions for cleanup
        chmod($dumpPath, $originalPerms);
    }

    public function test_create_dump_throws_exception_on_command_failure()
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        // Create a testable handler that simulates command failure
        $handler = new class($this->mockConfig) extends DatabaseBackupHandler {
            protected function executeCommand(string $command): array
            {
                return [
                    'output' => ['mysqldump: command not found'],
                    'return_code' => 127
                ];
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Database backup failed: mysqldump: command not found');

        $handler->createDump('failed_backup.sql');
    }

    public function test_create_dump_throws_exception_when_file_not_created()
    {
        Log::shouldReceive('info')->once();

        // Create a testable handler that simulates successful command but no file created
        $handler = new class($this->mockConfig) extends DatabaseBackupHandler {
            protected function executeCommand(string $command): array
            {
                // Don't create the file to simulate failure
                return ['output' => [], 'return_code' => 0];
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Database dump file was not created or is empty');

        $handler->createDump('missing_file.sql');
    }



    /**
     * Create a valid mock dump file for testing validation
     */
    private function createValidMockDumpFile(string $path): void
    {
        file_put_contents($path, $this->getValidDumpContent());
    }

    /**
     * Get valid SQL dump content for testing
     */
    private function getValidDumpContent(): string
    {
        return "-- MySQL dump 10.13  Distrib 8.0.23, for Linux (x86_64)\n" .
               "-- Host: localhost    Database: test_db\n" .
               "-- Server version	8.0.23\n\n" .
               "SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;\n" .
               "SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;\n" .
               "SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;\n" .
               "SET NAMES utf8mb4;\n" .
               "SET @OLD_TIME_ZONE=@@TIME_ZONE;\n" .
               "SET TIME_ZONE='+00:00';\n\n" .
               "CREATE TABLE `users` (\n" .
               "  `id` bigint unsigned NOT NULL AUTO_INCREMENT,\n" .
               "  `name` varchar(255) NOT NULL,\n" .
               "  PRIMARY KEY (`id`)\n" .
               ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n" .
               "INSERT INTO `users` VALUES (1,'Test User');\n\n" .
               "-- Dump completed on " . date('Y-m-d H:i:s') . "\n";
    }
}