<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Backup;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']);
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
    }

    /**
     * Test backup dashboard page loads correctly with real data
     */
    public function test_backup_dashboard_page_loads_with_real_data()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Create some real backup data
        Backup::factory()->create([
            'name' => 'test_backup_1',
            'status' => 'completed',
            'type' => 'full',
            'file_size' => 1024 * 1024, // 1MB
            'created_by' => $user->id,
            'created_at' => now()->subHours(2),
        ]);

        Backup::factory()->create([
            'name' => 'test_backup_2',
            'status' => 'failed',
            'type' => 'database',
            'file_size' => 512 * 1024, // 512KB
            'created_by' => $user->id,
            'created_at' => now()->subHours(4),
        ]);

        $response = $this->actingAs($user)
                         ->get('/admin/backup-dashboard');

        $response->assertStatus(200)
                 ->assertSee('Backup Management')
                 ->assertSee('Monitor and manage system backups')
                 ->assertSee('System Health')
                 ->assertSee('Success Rate')
                 ->assertSee('Storage Usage')
                 ->assertSee('Last Backup')
                 ->assertSee('Recent Backups')
                 ->assertSee('Create Backup')
                 ->assertSee('test_backup_1')
                 ->assertSee('test_backup_2');
    }

    /**
     * Test backup dashboard shows empty state when no backups exist
     */
    public function test_backup_dashboard_shows_empty_state()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
                         ->get('/admin/backup-dashboard');

        $response->assertStatus(200)
                 ->assertSee('Backup Management')
                 ->assertSee('No backups found')
                 ->assertSee('Get started by creating your first backup.')
                 ->assertSee('Create First Backup');
    }

    /**
     * Test backup dashboard access control
     */
    public function test_backup_dashboard_access_control()
    {
        // Test customer cannot access
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($customer)
             ->get('/admin/backup-dashboard')
             ->assertStatus(403);

        // Test admin can access (if they have the right middleware)
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);

        // Admin should not be able to access since route is restricted to superadmin
        $this->actingAs($admin)
             ->get('/admin/backup-dashboard')
             ->assertStatus(403);

        // Test superadmin can access
        $superadminRole = Role::where('name', 'superadmin')->first();
        $superadmin = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($superadmin)
             ->get('/admin/backup-dashboard')
             ->assertStatus(200)
             ->assertSee('Backup Management');
    }

    /**
     * Test backup dashboard with various backup statuses
     */
    public function test_backup_dashboard_with_various_statuses()
    {
        $superadminRole = Role::where('name', 'superadmin')->first();
        $user = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);

        // Create backups with different statuses
        Backup::factory()->create([
            'name' => 'completed_backup',
            'status' => 'completed',
            'type' => 'full',
            'created_by' => $user->id,
        ]);

        Backup::factory()->create([
            'name' => 'failed_backup',
            'status' => 'failed',
            'type' => 'database',
            'created_by' => $user->id,
        ]);

        Backup::factory()->create([
            'name' => 'pending_backup',
            'status' => 'pending',
            'type' => 'files',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
                         ->get('/admin/backup-dashboard');

        $response->assertStatus(200)
                 ->assertSee('completed_backup')
                 ->assertSee('failed_backup')
                 ->assertSee('pending_backup')
                 ->assertSee('Completed')
                 ->assertSee('Failed')
                 ->assertSee('Pending');
    }
}