<?php

namespace Tests\Unit;

use App\Services\BackupConfig;
use App\Services\DatabaseBackupHandler;
use Tests\TestCase;

class DatabaseBackupHandlerIntegrationTest extends TestCase
{
    private DatabaseBackupHandler $handler;
    private BackupConfig $config;
    private string $testBackupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testBackupPath = storage_path('app/test-integration-backups');
        
        // Create test backup directory
        if (!is_dir($this->testBackupPath)) {
            mkdir($this->testBackupPath, 0755, true);
        }

        // Override config for testing
        config(['backup.storage.path' => $this->testBackupPath]);
        
        $this->config = new BackupConfig();
        $this->handler = new DatabaseBackupHandler($this->config);
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

        parent::tearDown();
    }

    public function test_handler_uses_config_correctly()
    {
        // Test that the handler correctly uses BackupConfig values
        $this->assertEquals($this->testBackupPath, $this->config->getStoragePath());
        $this->assertIsInt($this->config->getDatabaseTimeout());
        $this->assertIsBool($this->config->includeDatabaseRoutines());
        $this->assertIsBool($this->config->includeDatabaseTriggers());
    }

    public function test_handler_can_validate_existing_dump_files()
    {
        // Create a valid dump file
        $dumpPath = $this->testBackupPath . '/integration_test.sql';
        $validContent = "-- MySQL dump 10.13\n";
        $validContent .= "CREATE TABLE `test` (`id` int);\n";
        $validContent .= "INSERT INTO `test` VALUES (1);\n";
        
        file_put_contents($dumpPath, $validContent);

        $isValid = $this->handler->validateDump($dumpPath);
        $size = $this->handler->getDumpSize($dumpPath);

        $this->assertTrue($isValid);
        $this->assertEquals(strlen($validContent), $size);
    }

    public function test_handler_generates_proper_filenames()
    {
        // Test filename generation without actually creating dumps
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('generateTimestampedFilename');
        $method->setAccessible(true);

        $filename = $method->invoke($this->handler);

        $this->assertStringContainsString('database_backup_', $filename);
        $this->assertStringContainsString(date('Y-m-d'), $filename);
        $this->assertStringEndsWith('.sql', $filename);
    }

    public function test_handler_builds_proper_mysqldump_command()
    {
        // Test command building
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('buildMysqldumpCommand');
        $method->setAccessible(true);

        $dbConfig = [
            'host' => 'localhost',
            'port' => 3306,
            'username' => 'testuser',
            'password' => 'testpass',
            'database' => 'testdb'
        ];

        $command = $method->invoke($this->handler, $dbConfig, '/tmp/test.sql');

        $this->assertStringContainsString('mysqldump', $command);
        $this->assertStringContainsString("--host='localhost'", $command);
        $this->assertStringContainsString("--user='testuser'", $command);
        $this->assertStringContainsString("--password='testpass'", $command);
        $this->assertStringContainsString("'testdb'", $command);
        $this->assertStringContainsString('--routines', $command);
        $this->assertStringContainsString('--triggers', $command);
        $this->assertStringContainsString('--single-transaction', $command);
    }

    public function test_handler_sanitizes_command_for_logging()
    {
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('sanitizeCommandForLogging');
        $method->setAccessible(true);

        $command = 'mysqldump --user=test --password=secret123 --host=localhost testdb';
        $sanitized = $method->invoke($this->handler, $command);

        $this->assertStringNotContainsString('secret123', $sanitized);
        $this->assertStringContainsString('--password=***', $sanitized);
        $this->assertStringContainsString('--user=test', $sanitized);
    }
}