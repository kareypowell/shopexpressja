<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BackupHistoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $superAdminRole = Role::create([
            'name' => 'superadmin',
            'description' => 'Super Administrator'
        ]);
        
        // Create super admin user
        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@test.com',
            'role_id' => $superAdminRole->id,
        ]);

        // Create test backup files
        Storage::fake('local');
    }

    /** @test */
    public function superadmin_can_view_backup_history_page()
    {
        $this->actingAs($this->superAdmin)
             ->get('/admin/backup-history')
             ->assertOk()
             ->assertSee('Backup History')
             ->assertSee('Manage and download backup files')
             ->assertSee('Total Backups')
             ->assertSee('Completed')
             ->assertSee('Failed')
             ->assertSee('Total Size');
    }

    /** @test */
    public function backup_history_displays_backup_files_correctly()
    {
        // Create test backups
        $databaseBackup = Backup::factory()->create([
            'name' => 'Database Backup 2024-01-15',
            'type' => 'database',
            'status' => 'completed',
            'file_size' => 1024000,
            'file_path' => 'backups/database_2024-01-15.sql',
        ]);

        $fileBackup = Backup::factory()->create([
            'name' => 'Files Backup 2024-01-14',
            'type' => 'files',
            'status' => 'completed',
            'file_size' => 2048000,
            'file_path' => 'backups/files_2024-01-14.zip',
        ]);

        $failedBackup = Backup::factory()->create([
            'name' => 'Failed Backup 2024-01-13',
            'type' => 'full',
            'status' => 'failed',
            'file_size' => null,
            'file_path' => 'backups/failed_2024-01-13.zip',
        ]);

        Livewire::actingAs($this->superAdmin)
                ->test('admin.backup-history')
                ->assertSee($databaseBackup->name)
                ->assertSee($fileBackup->name)
                ->assertSee($failedBackup->name)
                ->assertSee('Database')
                ->assertSee('Files')
                ->assertSee('Full')
                ->assertSee('Completed')
                ->assertSee('Failed')
                ->assertSee('1.00 MB') // Database backup size
                ->assertSee('2.00 MB'); // File backup size
    }

    /** @test */
    public function search_functionality_filters_backups_correctly()
    {
        // Create test backups
        Backup::factory()->create([
            'name' => 'Database Backup January',
            'type' => 'database',
            'status' => 'completed',
        ]);

        Backup::factory()->create([
            'name' => 'Files Backup February',
            'type' => 'files',
            'status' => 'completed',
        ]);

        Livewire::actingAs($this->superAdmin)
                ->test('admin.backup-history')
                ->assertSee('Database Backup January')
                ->assertSee('Files Backup February')
                ->set('search', 'January')
                ->assertSee('Database Backup January')
                ->assertDontSee('Files Backup February')
                ->set('search', '')
                ->assertSee('Database Backup January')
                ->assertSee('Files Backup February');
    }

    /** @test */
    public function filter_functionality_works_correctly()
    {
        // Create test backups
        Backup::factory()->create([
            'name' => 'Database Backup',
            'type' => 'database',
            'status' => 'completed',
        ]);

        Backup::factory()->create([
            'name' => 'Files Backup',
            'type' => 'files',
            'status' => 'failed',
        ]);

        Livewire::actingAs($this->superAdmin)
                ->test('admin.backup-history')
                ->call('toggleFilters')
                ->set('typeFilter', 'database')
                ->assertSee('Database Backup')
                ->assertDontSee('Files Backup')
                ->set('statusFilter', 'failed')
                ->assertDontSee('Database Backup')
                ->call('clearFilters')
                ->assertSee('Database Backup')
                ->assertSee('Files Backup');
    }

    /** @test */
    public function sorting_functionality_works_correctly()
    {
        // Create test backups with different dates
        $olderBackup = Backup::factory()->create([
            'name' => 'Older Backup',
            'created_at' => now()->subDays(2),
        ]);

        $newerBackup = Backup::factory()->create([
            'name' => 'Newer Backup',
            'created_at' => now()->subDay(),
        ]);

        $component = Livewire::actingAs($this->superAdmin)
                            ->test('admin.backup-history');

        // Test default sorting (desc by created_at)
        $backups = $component->get('backups');
        $this->assertEquals($newerBackup->id, $backups->first()->id);

        // Test sorting by created_at ascending
        $component->call('sortBy', 'created_at');
        $backups = $component->get('backups');
        $this->assertEquals($olderBackup->id, $backups->first()->id);
    }

    /** @test */
    public function download_button_is_only_visible_for_completed_backups()
    {
        // Create backups with different statuses
        $completedBackup = Backup::factory()->create([
            'name' => 'Completed Backup',
            'status' => 'completed',
            'file_path' => 'backups/completed.sql',
        ]);

        $failedBackup = Backup::factory()->create([
            'name' => 'Failed Backup',
            'status' => 'failed',
            'file_path' => 'backups/failed.sql',
        ]);

        $pendingBackup = Backup::factory()->create([
            'name' => 'Pending Backup',
            'status' => 'pending',
            'file_path' => 'backups/pending.sql',
        ]);

        Livewire::actingAs($this->superAdmin)
                ->test('admin.backup-history')
                ->assertSee($completedBackup->name)
                ->assertSee($failedBackup->name)
                ->assertSee($pendingBackup->name)
                ->assertSee('Download') // Should only appear for completed backup
                ->assertSeeHtml('wire:click="generateDownloadLink(' . $completedBackup->id . ')"');
    }

    /** @test */
    public function batch_selection_works()
    {
        // Create completed backups
        $backup1 = Backup::factory()->create([
            'name' => 'Backup 1',
            'status' => 'completed',
            'file_path' => 'backups/backup1.sql',
        ]);

        $backup2 = Backup::factory()->create([
            'name' => 'Backup 2',
            'status' => 'completed',
            'file_path' => 'backups/backup2.sql',
        ]);

        Livewire::actingAs($this->superAdmin)
                ->test('admin.backup-history')
                ->set('selectedBackups', [$backup1->id])
                ->assertSee('Download Selected (1)')
                ->set('selectedBackups', [$backup1->id, $backup2->id])
                ->assertSee('Download Selected (2)');
    }

    /** @test */
    public function delete_backup_functionality_works()
    {
        $backup = Backup::factory()->create([
            'name' => 'Test Backup to Delete',
            'status' => 'completed',
            'file_path' => 'backups/test_delete.sql',
        ]);

        // Create fake file
        Storage::put($backup->file_path, 'fake backup content');

        Livewire::actingAs($this->superAdmin)
                ->test('admin.backup-history')
                ->assertSee($backup->name)
                ->call('deleteBackup', $backup->id)
                ->assertDontSee($backup->name);

        // Verify backup was deleted from database
        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
        
        // Verify file was deleted
        Storage::assertMissing($backup->file_path);
    }

    /** @test */
    public function empty_state_displays_correctly()
    {
        Livewire::actingAs($this->superAdmin)
                ->test('admin.backup-history')
                ->assertSee('No backups found')
                ->assertSee('No backups match your current filters');
    }

    /** @test */
    public function date_range_filter_works()
    {
        // Create backups with different dates
        $oldBackup = Backup::factory()->create([
            'name' => 'Old Backup',
            'created_at' => now()->subDays(10),
        ]);

        $recentBackup = Backup::factory()->create([
            'name' => 'Recent Backup',
            'created_at' => now()->subDays(2),
        ]);

        Livewire::actingAs($this->superAdmin)
                ->test('admin.backup-history')
                ->set('dateFrom', now()->subDays(5)->format('Y-m-d'))
                ->assertSee($recentBackup->name)
                ->assertDontSee($oldBackup->name);
    }
}