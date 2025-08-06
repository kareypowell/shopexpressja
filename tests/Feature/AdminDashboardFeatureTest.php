<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminDashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_access_dashboard()
    {
        // Use existing superadmin role
        $role = Role::find(1);
        $user = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role_id' => $role->id,
        ]);

        // Create some test data for the dashboard to display
        $customer = User::factory()->create(['role_id' => Role::find(3)->id]);
        $manifest = \App\Models\Manifest::factory()->create();
        $office = \App\Models\Office::factory()->create();
        $shipper = \App\Models\Shipper::factory()->create();
        
        \App\Models\Package::factory()->create([
            'user_id' => $customer->id,
            'manifest_id' => $manifest->id,
            'office_id' => $office->id,
            'shipper_id' => $shipper->id,
            'freight_price' => 100.00,
            'customs_duty' => 25.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 15.00,
        ]);

        // Mock the analytics service to prevent database issues
        $this->mock(\App\Services\DashboardAnalyticsService::class, function ($mock) {
            $mock->shouldReceive('cacheKey')->andReturn('test-key');
            $mock->shouldReceive('getCustomerMetrics')->andReturn([]);
            $mock->shouldReceive('getShipmentMetrics')->andReturn([]);
            $mock->shouldReceive('getFinancialMetrics')->andReturn([]);
        });

        $this->mock(\App\Services\DashboardCacheService::class, function ($mock) {
            $mock->shouldReceive('remember')->andReturn([]);
            $mock->shouldReceive('flush')->andReturn(true);
        });

        // Access the admin dashboard
        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Admin Dashboard');
        $response->assertSee('Welcome back, Admin');
    }

    /** @test */
    public function non_admin_cannot_access_admin_dashboard()
    {
        // Create regular user role and user
        $role = Role::factory()->create(['name' => 'customer']);
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        // Try to access the admin dashboard
        $response = $this->actingAs($user)->get('/admin/dashboard');

        // Should be redirected or get 403
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }

    /** @test */
    public function guest_cannot_access_admin_dashboard()
    {
        $response = $this->get('/admin/dashboard');

        // Should be redirected to login
        $response->assertRedirect('/login');
    }
}