<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Http\Livewire\ConsolidationToggle;
use App\Http\Livewire\Package as PackageComponent;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsolidationComponentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customerUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles using the seeder structure
        $adminRole = Role::create(['id' => 2, 'name' => 'admin', 'description' => 'Administrator']);
        $customerRole = Role::create(['id' => 3, 'name' => 'customer', 'description' => 'Customer']);

        // Create users
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customerUser = User::factory()->create(['role_id' => $customerRole->id]);
    }

    /** @test */
    public function consolidation_toggle_component_allows_admin_to_toggle()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ConsolidationToggle::class)
            ->call('toggleConsolidationMode')
            ->assertEmitted('consolidationModeChanged')
            ->assertDispatchedBrowserEvent('show-message');
    }

    /** @test */
    public function consolidation_toggle_component_denies_customer_access()
    {
        $this->actingAs($this->customerUser);

        Livewire::test(ConsolidationToggle::class)
            ->call('toggleConsolidationMode')
            ->assertDispatchedBrowserEvent('show-error', [
                'message' => 'You do not have permission to use consolidation features.'
            ]);
    }

    /** @test */
    public function consolidation_toggle_component_shows_correct_permission_status()
    {
        // Test admin user
        $this->actingAs($this->adminUser);
        $component = Livewire::test(ConsolidationToggle::class);
        $this->assertTrue($component->instance()->canUseConsolidation);

        // Test customer user
        $this->actingAs($this->customerUser);
        $component = Livewire::test(ConsolidationToggle::class);
        $this->assertFalse($component->instance()->canUseConsolidation);
    }

    /** @test */
    public function package_component_allows_admin_to_consolidate()
    {
        $this->actingAs($this->adminUser);

        // Create packages for consolidation
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $packageIds = $packages->pluck('id')->toArray();

        Livewire::test(PackageComponent::class)
            ->set('selectedPackagesForConsolidation', $packageIds)
            ->set('consolidationNotes', 'Test consolidation')
            ->call('consolidateSelectedPackages')
            ->assertSet('successMessage', 'Packages consolidated successfully');
    }

    /** @test */
    public function package_component_denies_customer_consolidation()
    {
        $this->actingAs($this->customerUser);

        // Create packages
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $packageIds = $packages->pluck('id')->toArray();

        Livewire::test(PackageComponent::class)
            ->set('selectedPackagesForConsolidation', $packageIds)
            ->call('consolidateSelectedPackages')
            ->assertSet('errorMessage', 'You do not have permission to consolidate packages.');
    }

    /** @test */
    public function package_component_allows_admin_to_unconsolidate()
    {
        $this->actingAs($this->adminUser);

        // Create consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'created_by' => $this->adminUser->id
        ]);

        Livewire::test(PackageComponent::class)
            ->call('unconsolidatePackage', $consolidatedPackage->id)
            ->assertSet('successMessage', 'Packages unconsolidated successfully');
    }

    /** @test */
    public function package_component_denies_customer_unconsolidation()
    {
        $this->actingAs($this->customerUser);

        // Create consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'created_by' => $this->adminUser->id
        ]);

        Livewire::test(PackageComponent::class)
            ->call('unconsolidatePackage', $consolidatedPackage->id)
            ->assertSet('errorMessage', 'You do not have permission to unconsolidate this package.');
    }

    /** @test */
    public function package_component_allows_admin_to_toggle_consolidation_mode()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(PackageComponent::class)
            ->call('toggleConsolidationMode')
            ->assertSet('consolidationMode', true);
    }

    /** @test */
    public function package_component_denies_customer_consolidation_mode_toggle()
    {
        $this->actingAs($this->customerUser);

        Livewire::test(PackageComponent::class)
            ->call('toggleConsolidationMode')
            ->assertSet('errorMessage', 'You do not have permission to use consolidation features.');
    }

    /** @test */
    public function package_component_shows_correct_consolidation_permissions()
    {
        // Test admin user
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PackageComponent::class);
        $this->assertTrue($component->instance()->canUseConsolidation);

        // Test customer user
        $this->actingAs($this->customerUser);
        $component = Livewire::test(PackageComponent::class);
        $this->assertFalse($component->instance()->canUseConsolidation);
    }

    /** @test */
    public function package_component_customer_can_view_consolidation_history_for_own_packages()
    {
        $this->actingAs($this->customerUser);

        // Create consolidated package for the customer
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'created_by' => $this->adminUser->id
        ]);

        Livewire::test(PackageComponent::class)
            ->call('showConsolidationHistory', $consolidatedPackage->id)
            ->assertSet('showHistoryModal', true)
            ->assertSet('selectedConsolidatedPackageForHistory.id', $consolidatedPackage->id);
    }

    /** @test */
    public function package_component_customer_cannot_view_other_customers_consolidation_history()
    {
        $this->actingAs($this->customerUser);

        // Create consolidated package for another customer
        $otherCustomer = User::factory()->create(['role_id' => 3]); // Customer role
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $otherCustomer->id,
            'created_by' => $this->adminUser->id
        ]);

        Livewire::test(PackageComponent::class)
            ->call('showConsolidationHistory', $consolidatedPackage->id)
            ->assertSet('errorMessage', 'Unable to load consolidation history: This action is unauthorized.');
    }

    /** @test */
    public function package_component_admin_gets_empty_available_packages_when_not_viewing_customer_packages()
    {
        $this->actingAs($this->adminUser);

        // Admin viewing their own packages (which should be empty for consolidation)
        $component = Livewire::test(PackageComponent::class);
        $availablePackages = $component->instance()->availablePackagesForConsolidation;
        
        // Admin user's own packages should be empty since they don't ship packages
        $this->assertTrue($availablePackages->isEmpty());
    }

    /** @test */
    public function package_component_customer_gets_empty_available_packages_for_consolidation()
    {
        $this->actingAs($this->customerUser);

        // Create packages for the customer
        Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'processing'
        ]);

        $component = Livewire::test(PackageComponent::class);
        $availablePackages = $component->instance()->availablePackagesForConsolidation;
        
        // Customer should not see packages available for consolidation (only admin can consolidate)
        $this->assertTrue($availablePackages->isEmpty());
    }

    /** @test */
    public function package_component_initializes_consolidation_mode_correctly_based_on_user_role()
    {
        // Admin user should be able to have consolidation mode from session
        $this->actingAs($this->adminUser);
        session(['consolidation_mode' => true]);
        
        $component = Livewire::test(PackageComponent::class);
        $this->assertTrue($component->instance()->consolidationMode);

        // Customer user should never have consolidation mode enabled
        $this->actingAs($this->customerUser);
        session(['consolidation_mode' => true]); // Even if session says true
        
        $component = Livewire::test(PackageComponent::class);
        $this->assertFalse($component->instance()->consolidationMode);
    }

    /** @test */
    public function package_component_can_consolidate_for_customer_method_works_correctly()
    {
        // Admin can consolidate for any customer
        $this->actingAs($this->adminUser);
        $component = Livewire::test(PackageComponent::class);
        $this->assertTrue($component->instance()->canConsolidateForCustomer($this->customerUser->id));

        // Customer cannot consolidate packages
        $this->actingAs($this->customerUser);
        $component = Livewire::test(PackageComponent::class);
        $this->assertFalse($component->instance()->canConsolidateForCustomer($this->customerUser->id));
        $this->assertFalse($component->instance()->canConsolidateForCustomer($this->adminUser->id));
    }

    /** @test */
    public function unauthenticated_user_cannot_access_consolidation_features()
    {
        // Test without authentication
        Livewire::test(ConsolidationToggle::class)
            ->call('toggleConsolidationMode')
            ->assertDispatchedBrowserEvent('show-error');

        Livewire::test(PackageComponent::class)
            ->call('toggleConsolidationMode')
            ->assertSet('errorMessage', 'You do not have permission to use consolidation features.');
    }
}