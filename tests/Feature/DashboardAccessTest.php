<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function customer_can_access_dashboard()
    {
        // Create customer role
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create customer user with verified email
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => now(),
        ]);
        
        // Act as the customer
        $response = $this->actingAs($customer)->get('/');
        
        // Should get 200 OK and show customer dashboard
        $response->assertStatus(200);
        $response->assertSee('Welcome back, ' . $customer->first_name);
    }

    /** @test */
    public function admin_can_access_dashboard()
    {
        // Create admin role
        $adminRole = Role::factory()->create(['name' => 'admin']);
        
        // Create admin user with verified email
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
        
        // Act as the admin
        $response = $this->actingAs($admin)->get('/');
        
        // Should get 200 OK and show admin dashboard
        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_access_dashboard()
    {
        // Create superadmin role
        $superadminRole = Role::factory()->create(['name' => 'superadmin']);
        
        // Create superadmin user with verified email
        $superadmin = User::factory()->create([
            'role_id' => $superadminRole->id,
            'email_verified_at' => now(),
        ]);
        
        // Act as the superadmin
        $response = $this->actingAs($superadmin)->get('/');
        
        // Should redirect to admin dashboard
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.dashboard'));
    }

    /** @test */
    public function unverified_user_cannot_access_dashboard()
    {
        // Create customer role
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create customer user WITHOUT verified email
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => null,
        ]);
        
        // Act as the customer
        $response = $this->actingAs($customer)->get('/');
        
        // Should redirect to email verification
        $response->assertRedirect('/email/verify');
    }

    /** @test */
    public function guest_cannot_access_dashboard()
    {
        // Try to access dashboard as guest
        $response = $this->get('/');
        
        // Should redirect to login
        $response->assertRedirect('/login');
    }
}