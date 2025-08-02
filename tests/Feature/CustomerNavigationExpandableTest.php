<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerNavigationExpandableTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and user
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->admin = User::factory()->create(['role_id' => $this->adminRole->id]);
        Profile::factory()->create(['user_id' => $this->admin->id]);
    }

    /** @test */
    public function expandable_customer_menu_is_present_in_navigation()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.customers.index'));
        
        $response->assertStatus(200);
        
        // Check for expandable menu elements (HTML double-encoded)
        $response->assertSee('x-data=&amp;quot;{ open:');
        $response->assertSee('All Customers');
        $response->assertSee('Create Customer');
        $response->assertSee('@click=&amp;quot;open = !open&amp;quot;');
    }

    /** @test */
    public function customer_submenu_links_are_correct()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.customers.index'));
        
        $response->assertStatus(200);
        
        // Check that the submenu contains correct routes
        $response->assertSee(route('admin.customers.index'));
        $response->assertSee(route('admin.customers.create'));
    }

    /** @test */
    public function customer_menu_shows_active_state_correctly()
    {
        // Test customer index page
        $response = $this->actingAs($this->admin)->get(route('admin.customers.index'));
        $response->assertStatus(200);
        $response->assertSee('bg-gray-900 text-white'); // Active state

        // Test customer create page
        $response = $this->actingAs($this->admin)->get(route('admin.customers.create'));
        $response->assertStatus(200);
        $response->assertSee('bg-gray-900 text-white'); // Active state
    }

    /** @test */
    public function customer_menu_is_expanded_when_on_customer_pages()
    {
        // Test that menu is expanded (open: true) when on customer pages
        $response = $this->actingAs($this->admin)->get(route('admin.customers.index'));
        $response->assertStatus(200);
        $response->assertSee('open: true'); // Menu should be expanded

        $response = $this->actingAs($this->admin)->get(route('admin.customers.create'));
        $response->assertStatus(200);
        $response->assertSee('open: true'); // Menu should be expanded
    }

    /** @test */
    public function customer_create_link_is_accessible_from_navigation()
    {
        // Verify that the create customer link works
        $response = $this->actingAs($this->admin)->get(route('admin.customers.create'));
        $response->assertStatus(200);
        $response->assertSee('Create New Customer');
    }
}