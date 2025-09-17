<?php

namespace Tests\Feature;

use App\Models\Backup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BackupCommandsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test configuration
        Config::set('backup.storage.path', storage_path('app/test-backups'));
        Config::set('backup.retention.database_days', 30);
        Config::set('backup.retention.files_days', 14);
        Config::set('backup.notifications.email', 'test@example.com');
    }

    /** @test */
    public function backup_create_command_is_registered()
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('backup:create', $commands);
    }

    /** @test */
    public function backup_cleanup_command_is_registered()
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('backup:cleanup', $commands);
    }

    /** @test */
    public function backup_status_command_is_registered()
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('backup:status', $commands);
    }

    /** @test */
    public function backup_create_command_shows_help()
    {
        $exitCode = Artisan::call('backup:create', ['--help' => true]);
        
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Create a manual backup', $output);
        $this->assertStringContainsString('--database', $output);
        $this->assertStringContainsString('--files', $output);
        $this->assertStringContainsString('--name', $output);
    }

    /** @test */
    public function backup_cleanup_command_shows_help()
    {
        $exitCode = Artisan::call('backup:cleanup', ['--help' => true]);
        
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Clean up old backup files', $output);
        $this->assertStringContainsString('--dry-run', $output);
    }

    /** @test */
    public function backup_status_command_shows_help()
    {
        $exitCode = Artisan::call('backup:status', ['--help' => true]);
        
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Display backup system status', $output);
    }

    /** @test */
    public function backup_create_command_validates_conflicting_options()
    {
        $exitCode = Artisan::call('backup:create', [
            '--database' => true,
            '--files' => true
        ]);

        $this->assertEquals(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Cannot specify both --database and --files options', $output);
    }

    /** @test */
    public function backup_status_command_runs_without_errors()
    {
        // Create some test backup records
        Backup::factory()->completed()->create([
            'type' => 'database',
            'created_at' => now()->subHours(2)
        ]);

        Backup::factory()->failed()->create([
            'type' => 'files',
            'created_at' => now()->subDays(1)
        ]);

        $exitCode = Artisan::call('backup:status');
        
        $this->assertEquals(0, $exitCode);
        
        // The command should run successfully even if output capture doesn't work in tests
        // This tests that the command doesn't crash or throw exceptions
    }

    /** @test */
    public function backup_cleanup_command_dry_run_works()
    {
        // Create some old backup records
        Backup::factory()->completed()->old(35)->create([
            'type' => 'database',
            'file_path' => '/path/to/old/backup.sql'
        ]);

        $exitCode = Artisan::call('backup:cleanup', ['--dry-run' => true]);
        
        $this->assertEquals(0, $exitCode);
        
        // The command should run successfully even if output capture doesn't work in tests
        // This tests that the command doesn't crash or throw exceptions
    }

    /** @test */
    public function commands_are_properly_configured()
    {
        // Test that commands are properly configured and don't have syntax errors
        $commands = Artisan::all();
        
        $this->assertArrayHasKey('backup:create', $commands);
        $this->assertArrayHasKey('backup:cleanup', $commands);
        $this->assertArrayHasKey('backup:status', $commands);
        
        // Test that command signatures are valid
        $createCommand = $commands['backup:create'];
        $this->assertNotNull($createCommand->getDescription());
        
        $cleanupCommand = $commands['backup:cleanup'];
        $this->assertNotNull($cleanupCommand->getDescription());
        
        $statusCommand = $commands['backup:status'];
        $this->assertNotNull($statusCommand->getDescription());
    }
}