<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\Office;
use App\Models\Shipper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Livewire\Livewire;
use App\Http\Livewire\Customers\CustomerProfile;

class CustomerProfileViewingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $customer;
    protected $otherCustomer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'superadmin', 'description' => 'Super Administrator']);
        Role::create(['name' => 'admin', 'description' => 'Administrator']);
        Role::create(['name' => 'customer', 'description' => 'Customer']);

        // Create test users
        $this->admin = User::factory()->create(['role_id' => 2]);
        $this->customer = User::factory()->create(['role_id' => 3]);
        $this->otherCustomer = User::factory()->create(['role_id' => 3]);

        // Create profiles
        Profile::factory()->create(['user_id' => $this->customer->id]);
        Profile::factory()->create(['user_id' => $this->otherCustomer->id]);

        // Create test data for packages
        $manifest = Manifest::factory()->create();
        $office = Office::factory()->create();
        $shipper = Shipper::factory()->create();

        // Create packages for the customer
        Package::factory()->count(5)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'office_id' => $office->id,
            'shipper_id' => $shipper->id,
            'freight_price' => 100.00,
            'customs_duty' => 50.00,
            'storage_fee' => 25.00,
            'delivery_fee' => 15.00,
            'status' => 'delivered'
        ]);
    }

    /** @test */
    public function admin_can_view_customer_profile()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerProfile::class, ['customer' => $this->customer])
            ->assertStatus(200)
            ->assertSee($this->customer->full_name)
            ->assertSee($this->customer->email)
            ->assertViewHas('canViewFinancials', true)
            ->assertViewHas('canViewPackages', true);
    }

    /** @test */
    public function customer_can_view_own_profile()
    {
        $this->actingAs($this->customer);

        Livewire::test(CustomerProfile::class, ['customer' => $this->customer])
            ->assertStatus(200)
            ->assertSee($this->customer->full_name)
            ->assertSee($this->customer->email)
            ->assertViewHas('canViewFinancials', true)
            ->assertViewHas('canViewPackages', true);
    }

    /** @test */
    public function customer_cannot_view_other_customer_profile()
    {
        $this->actingAs($this->customer);

        Livewire::test(CustomerProfile::class, ['customer' => $this->otherCustomer])
            ->assertForbidden();
    }

    /** @test */
    public function customer_profile_displays_package_statistics()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerProfile::class, ['customer' => $this->customer]);

        $component->assertStatus(200);
        
        // Check that package stats are loaded
        $this->assertNotEmpty($component->get('packageStats'));
        $this->assertEquals(5, $component->get('packageStats')['total_packages']);
    }

    /** @test */
    public function customer_profile_displays_financial_summary()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerProfile::class, ['customer' => $this->customer]);

        $component->assertStatus(200);
        
        // Check that financial summary is loaded
        $this->assertNotEmpty($component->get('financialSummary'));
        $this->assertEquals(950.00, $component->get('financialSummary')['total_spent']); // 5 packages * 190 each
    }

    /** @test */
    public function customer_profile_displays_recent_packages()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerProfile::class, ['customer' => $this->customer]);

        $component->assertStatus(200);
        
        // Check that recent packages are loaded
        $this->assertCount(5, $component->get('recentPackages'));
    }

    /** @test */
    public function customer_profile_can_toggle_package_view()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerProfile::class, ['customer' => $this->customer])
            ->assertSet('showAllPackages', false)
            ->call('togglePackageView')
            ->assertSet('showAllPackages', true);
    }

    /** @test */
    public function unauthorized_user_cannot_toggle_package_view()
    {
        $this->actingAs($this->customer);

        Livewire::test(CustomerProfile::class, ['customer' => $this->otherCustomer])
            ->assertForbidden();
    }

    /** @test */
    public function customer_profile_handles_customer_with_no_packages()
    {
        $customerWithNoPackages = User::factory()->create(['role_id' => 3]);
        Profile::factory()->create(['user_id' => $customerWithNoPackages->id]);

        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerProfile::class, ['customer' => $customerWithNoPackages]);

        $component->assertStatus(200);
        
        // Check that stats show zero values
        $this->assertEquals(0, $component->get('packageStats')['total_packages']);
        $this->assertEquals(0.0, $component->get('financialSummary')['total_spent']);
        $this->assertEmpty($component->get('recentPackages'));
    }

    /** @test */
    public function customer_profile_loads_with_proper_relationships()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerProfile::class, ['customer' => $this->customer]);

        $customer = $component->get('customer');
        
        // Check that relationships are loaded
        $this->assertTrue($customer->relationLoaded('profile'));
        $this->assertTrue($customer->relationLoaded('role'));
    }

    /** @test */
    public function customer_profile_export_functionality_shows_info_message()
    {
        $this->actingAs($this->admin);

        Livewire::test(CustomerProfile::class, ['customer' => $this->customer])
            ->call('exportCustomerData')
            ->assertDispatchedBrowserEvent('show-alert', [
                'type' => 'info',
                'message' => 'Export functionality will be implemented in a future update.'
            ]);
    }

    /** @test */
    public function customer_profile_respects_financial_permissions()
    {
        // Create a user with limited permissions (customer viewing own profile)
        $this->actingAs($this->customer);

        $component = Livewire::test(CustomerProfile::class, ['customer' => $this->customer]);

        $component->assertStatus(200)
            ->assertViewHas('canViewFinancials', true)
            ->assertViewHas('canViewPackages', true);
    }

    /** @test */
    public function customer_profile_pagination_works_correctly()
    {
        // Create more packages to test pagination
        $manifest = Manifest::factory()->create();
        $office = Office::factory()->create();
        $shipper = Shipper::factory()->create();

        Package::factory()->count(15)->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'office_id' => $office->id,
            'shipper_id' => $shipper->id,
        ]);

        $this->actingAs($this->admin);

        $component = Livewire::test(CustomerProfile::class, ['customer' => $this->customer])
            ->call('togglePackageView'); // Show all packages

        // Check that pagination is working
        $packages = $component->viewData('packages');
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $packages);
        $this->assertEquals(10, $packages->perPage());
    }

    /** @test */
    public function customer_profile_handles_soft_deleted_customer()
    {
        // Soft delete the customer
        $this->customer->delete();

        $this->actingAs($this->admin);

        // Should still be able to view soft deleted customer profile
        Livewire::test(CustomerProfile::class, ['customer' => $this->customer->withTrashed()->find($this->customer->id)])
            ->assertStatus(200);
    }
}