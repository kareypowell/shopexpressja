<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerRouteAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function customer_can_access_my_profile_route()
    {
        // Create customer role
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create customer user with verified email
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => now(),
        ]);
        
        // Act as the customer
        $response = $this->actingAs($customer)->get('/my-profile');
        
        // Should not get 403 forbidden
        $response->assertStatus(200);
    }

    /** @test */
    public function customer_can_access_invoices_route()
    {
        // Create customer role
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create customer user with verified email
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => now(),
        ]);
        
        // Act as the customer
        $response = $this->actingAs($customer)->get('/invoices');
        
        // Should not get 403 forbidden
        $response->assertStatus(200);
    }

    /** @test */
    public function non_customer_cannot_access_customer_routes()
    {
        // Create admin role
        $adminRole = Role::factory()->create(['name' => 'admin']);
        
        // Create admin user with verified email
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
        
        // Act as the admin
        $response = $this->actingAs($admin)->get('/my-profile');
        
        // Should get 403 forbidden
        $response->assertStatus(403);
    }

    /** @test */
    public function unverified_customer_cannot_access_customer_routes()
    {
        // Create customer role
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        // Create customer user WITHOUT verified email
        $customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => null,
        ]);
        
        // Act as the customer
        $response = $this->actingAs($customer)->get('/my-profile');
        
        // Should redirect to email verification
        $response->assertRedirect('/email/verify');
    }

    /** @test */
    public function guest_cannot_access_customer_routes()
    {
        // Try to access customer route as guest
        $response = $this->get('/my-profile');
        
        // Should redirect to login
        $response->assertRedirect('/login');
    }
}