<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Http\Livewire\Users\UserManagement;
use App\Http\Livewire\Users\UserCreate;
use App\Http\Livewire\Users\UserEdit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class UserManagementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $admin;
    protected $customer;
    protected $purchaser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create or get existing roles
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']);
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        $customerRole = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
        $purchaserRole = Role::firstOrCreate(['name' => 'purchaser'], ['description' => 'Purchaser']);
        
        // Create users
        $this->superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customer = User::factory()->create(['role_id' => $customerRole->id]);
        $this->purchaser = User::factory()->create(['role_id' => $purchaserRole->id]);
    }

    /** @test */
    public function superadmin_can_access_user_management()
    {
        $this->actingAs($this->superAdmin);
        
        $response = $this->get(route('admin.users.index'));
        $response->assertSuccessful();
        
        Livewire::test(UserManagement::class)
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_access_user_management()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.users.index'));
        $response->assertSuccessful();
        
        Livewire::test(UserManagement::class)
            ->assertSuccessful();
    }

    /** @test */
    public function customer_cannot_access_user_management()
    {
        $this->actingAs($this->customer);
        
        $response = $this->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function purchaser_cannot_access_user_management()
    {
        $this->actingAs($this->purchaser);
        
        $response = $this->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function superadmin_can_access_user_creation()
    {
        $this->actingAs($this->superAdmin);
        
        $response = $this->get(route('admin.users.create'));
        $response->assertSuccessful();
        
        Livewire::test(UserCreate::class)
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_access_user_creation()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.users.create'));
        $response->assertSuccessful();
        
        Livewire::test(UserCreate::class)
            ->assertSuccessful();
    }

    /** @test */
    public function customer_cannot_access_user_creation()
    {
        $this->actingAs($this->customer);
        
        $response = $this->get(route('admin.users.create'));
        $response->assertStatus(403);
    }

    /** @test */
    public function superadmin_can_edit_any_user()
    {
        $this->actingAs($this->superAdmin);
        
        $response = $this->get(route('admin.users.edit', $this->admin));
        $response->assertSuccessful();
        
        $response = $this->get(route('admin.users.edit', $this->customer));
        $response->assertSuccessful();
        
        Livewire::test(UserEdit::class, ['user' => $this->admin])
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_edit_customers_and_own_profile()
    {
        $this->actingAs($this->admin);
        
        // Can edit customer
        $response = $this->get(route('admin.users.edit', $this->customer));
        $response->assertSuccessful();
        
        // Can edit own profile
        $response = $this->get(route('admin.users.edit', $this->admin));
        $response->assertSuccessful();
        
        // Cannot edit superadmin
        $response = $this->get(route('admin.users.edit', $this->superAdmin));
        $response->assertStatus(403);
        
        // Cannot edit purchaser
        $response = $this->get(route('admin.users.edit', $this->purchaser));
        $response->assertStatus(403);
    }

    /** @test */
    public function customer_cannot_edit_other_users()
    {
        $this->actingAs($this->customer);
        
        $response = $this->get(route('admin.users.edit', $this->admin));
        $response->assertStatus(403);
        
        $response = $this->get(route('admin.users.edit', $this->superAdmin));
        $response->assertStatus(403);
    }

    /** @test */
    public function superadmin_sees_all_users_in_management()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(UserManagement::class)
            ->assertSee($this->admin->full_name)
            ->assertSee($this->customer->full_name)
            ->assertSee($this->purchaser->full_name);
    }

    /** @test */
    public function admin_sees_only_customers_in_management()
    {
        $this->actingAs($this->admin);
        
        Livewire::test(UserManagement::class)
            ->assertSee($this->customer->full_name)
            ->assertDontSee($this->superAdmin->full_name)
            ->assertDontSee($this->purchaser->full_name);
    }

    /** @test */
    public function superadmin_can_delete_users()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(UserManagement::class)
            ->call('confirmDelete', $this->customer->id)
            ->assertSet('showDeleteModal', true)
            ->call('executeDelete')
            ->assertHasNoErrors();
            
        $this->assertSoftDeleted($this->customer);
    }

    /** @test */
    public function admin_can_delete_customers_only()
    {
        $this->actingAs($this->admin);
        
        // Can delete customer
        Livewire::test(UserManagement::class)
            ->call('confirmDelete', $this->customer->id)
            ->assertSet('showDeleteModal', true)
            ->call('executeDelete')
            ->assertHasNoErrors();
            
        $this->assertSoftDeleted($this->customer);
    }

    /** @test */
    public function users_cannot_delete_themselves()
    {
        $this->actingAs($this->superAdmin);
        
        Livewire::test(UserManagement::class)
            ->call('confirmDelete', $this->superAdmin->id)
            ->call('executeDelete')
            ->assertHasErrors();
            
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id, 'deleted_at' => null]);
    }

    /** @test */
    public function superadmin_can_restore_users()
    {
        $this->customer->delete();
        $this->actingAs($this->superAdmin);
        
        Livewire::test(UserManagement::class)
            ->call('restoreUser', $this->customer->id)
            ->assertHasNoErrors();
            
        $this->assertDatabaseHas('users', ['id' => $this->customer->id, 'deleted_at' => null]);
    }

    /** @test */
    public function admin_can_restore_customers_only()
    {
        $this->customer->delete();
        $this->actingAs($this->admin);
        
        Livewire::test(UserManagement::class)
            ->call('restoreUser', $this->customer->id)
            ->assertHasNoErrors();
            
        $this->assertDatabaseHas('users', ['id' => $this->customer->id, 'deleted_at' => null]);
    }
}