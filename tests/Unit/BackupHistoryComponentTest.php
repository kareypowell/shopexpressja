<?php

namespace Tests\Unit;

use App\Http\Livewire\Admin\BackupHistory;
use App\Models\Backup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;
use Tests\TestCase;

class BackupHistoryComponentTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']);
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
    }

    /**
     * Test that the BackupHistory component can be instantiated
     */
    public function test_backup_history_component_can_be_instantiated()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $component = Livewire::test(BackupHistory::class);
        
        $component->assertStatus(200);
    }

    /**
     * Test backup filtering functionality
     */
    public function test_backup_filtering_works()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Create test backups
        $backup1 = Backup::factory()->create([
            'name' => 'database_backup_test',
            'status' => 'completed',
            'type' => 'database',
            'created_by' => $user->id,
        ]);

        $backup2 = Backup::factory()->create([
            'name' => 'full_backup_test',
            'status' => 'completed',
            'type' => 'full',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(BackupHistory::class)
            ->set('typeFilter', 'database')
            ->assertSee('database_backup_test')
            ->assertDontSee('full_backup_test');
    }

    /**
     * Test search functionality
     */
    public function test_search_functionality_works()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Create test backups
        Backup::factory()->create([
            'name' => 'manual_backup_test',
            'status' => 'completed',
            'type' => 'full',
            'created_by' => $user->id,
        ]);

        Backup::factory()->create([
            'name' => 'scheduled_backup_test',
            'status' => 'completed',
            'type' => 'database',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(BackupHistory::class)
            ->set('search', 'manual')
            ->assertSee('manual_backup_test')
            ->assertDontSee('scheduled_backup_test');
    }

    /**
     * Test backup deletion functionality
     */
    public function test_backup_deletion_works()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $backup = Backup::factory()->create([
            'name' => 'backup_to_delete',
            'status' => 'completed',
            'type' => 'full',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $this->assertDatabaseHas('backups', ['id' => $backup->id]);

        Livewire::test(BackupHistory::class)
            ->call('deleteBackup', $backup->id);

        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
    }

    /**
     * Test sorting functionality
     */
    public function test_sorting_functionality_works()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $component = Livewire::test(BackupHistory::class)
            ->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'asc')
            ->call('sortBy', 'name')
            ->assertSet('sortDirection', 'desc');
    }

    /**
     * Test clear filters functionality
     */
    public function test_clear_filters_works()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $component = Livewire::test(BackupHistory::class)
            ->set('search', 'test')
            ->set('typeFilter', 'database')
            ->set('statusFilter', 'completed')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('typeFilter', '')
            ->assertSet('statusFilter', '');
    }
}