<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupHistoryRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::firstOrCreate(
            ['name' => 'superadmin'],
            ['description' => 'Super Administrator']
        );
        
        Role::firstOrCreate(
            ['name' => 'customer'],
            ['description' => 'Customer']
        );
    }

    /** @test */
    public function superadmin_can_access_backup_history_route()
    {
        $superAdminRole = Role::where('name', 'superadmin')->first();
        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
        ]);

        $response = $this->actingAs($superAdmin)
                         ->get('/admin/backup-history');

        $response->assertOk();
        $response->assertSeeLivewire('admin.backup-history');
    }

    /** @test */
    public function customer_cannot_access_backup_history_route()
    {
        $customerRole = Role::where('name', 'customer')->first();
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
        ]);

        $response = $this->actingAs($customer)
                         ->get('/admin/backup-history');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_backup_history_route()
    {
        $response = $this->get('/admin/backup-history');

        $response->assertRedirect('/login');
    }
}