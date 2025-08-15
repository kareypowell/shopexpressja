<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\Role;
use App\Enums\PackageStatus;
use App\Http\Livewire\Package as PackageComponent;
use App\Services\PackageConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

class PackageConsolidationComponentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $customerRole = Role::factory()->create(['name' => 'customer']);
        $adminRole = Role::factory()->create(['name' => 'admin']);

        // Create test users
        $this->user = User::factory()->create(['role_id' => $customerRole->id]);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
    }

    /** @test */
    public function it_can_toggle_consolidation_mode()
    {
        $this->actingAs($this->user);

        Livewire::test(PackageComponent::class)
            ->assertSet('consolidationMode', false)
            ->call('toggleConsolidationMode')
            ->assertSet('consolidationMode', true);

        // Check session persistence
        $this->assertTrue(Session::get('consolidation_mode'));
    }

    /** @test */
    public function it_can_toggle_consolidated_view()
    {
        $this->actingAs($this->user);

        Livewire::test(PackageComponent::class)
            ->assertSet('showConsolidatedView', false)
            ->call('toggleConsolidatedView')
            ->assertSet('showConsolidatedView', true);

        // Check session persistence
        $this->assertTrue(Session::get('show_consolidated_view'));
    }

    /** @test */
    public function it_can_select_packages_for_consolidation()
    {
        $this->actingAs($this->user);

        // Create test packages
        $package1 = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false
        ]);
        $package2 = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false
        ]);

        Livewire::test(PackageComponent::class)
            ->set('consolidationMode', true)
            ->call('togglePackageSelection', $package1->id)
            ->assertSet('selectedPackagesForConsolidation', [$package1->id])
            ->call('togglePackageSelection', $package2->id)
            ->assertSet('selectedPackagesForConsolidation', [$package1->id, $package2->id]);
    }

    /** @test */
    public function it_can_deselect_packages_for_consolidation()
    {
        $this->actingAs($this->user);

        $package1 = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false
        ]);

        Livewire::test(PackageComponent::class)
            ->set('consolidationMode', true)
            ->set('selectedPackagesForConsolidation', [$package1->id])
            ->call('togglePackageSelection', $package1->id)
            ->assertSet('selectedPackagesForConsolidation', []);
    }

    /** @test */
    public function it_prevents_package_selection_when_consolidation_mode_is_off()
    {
        $this->actingAs($this->user);

        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false
        ]);

        Livewire::test(PackageComponent::class)
            ->set('consolidationMode', false)
            ->call('togglePackageSelection', $package->id)
            ->assertSet('selectedPackagesForConsolidation', []);
    }

    /** @test */
    public function it_can_consolidate_selected_packages()
    {
        $this->actingAs($this->adminUser);

        // Create test packages
        $package1 = Package::factory()->create([
            'user_id' => $this->adminUser->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false,
            'weight' => 10.5,
            'freight_price' => 25.00
        ]);
        $package2 = Package::factory()->create([
            'user_id' => $this->adminUser->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false,
            'weight' => 8.3,
            'freight_price' => 20.00
        ]);

        $component = Livewire::test(PackageComponent::class);
        $component->set('consolidationMode', true);
        $component->set('selectedPackagesForConsolidation', [$package1->id, $package2->id]);
        $component->set('consolidationNotes', 'Test consolidation');
        
        // Call the method directly
        $component->instance()->consolidateSelectedPackages();
        
        // Check the component state
        $this->assertNotEmpty($component->get('successMessage'));
        $this->assertEmpty($component->get('selectedPackagesForConsolidation'));
        $this->assertEmpty($component->get('consolidationNotes'));

        // Verify consolidation was created
        $this->assertDatabaseHas('consolidated_packages', [
            'customer_id' => $this->adminUser->id,
            'is_active' => true,
            'notes' => 'Test consolidation'
        ]);

        // Verify packages were updated
        $package1->refresh();
        $package2->refresh();
        $this->assertTrue($package1->is_consolidated);
        $this->assertTrue($package2->is_consolidated);
        $this->assertNotNull($package1->consolidated_package_id);
        $this->assertNotNull($package2->consolidated_package_id);
    }

    /** @test */
    public function it_requires_at_least_two_packages_for_consolidation()
    {
        $this->actingAs($this->user);

        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false
        ]);

        Livewire::test(PackageComponent::class)
            ->set('consolidationMode', true)
            ->set('selectedPackagesForConsolidation', [$package->id])
            ->call('consolidateSelectedPackages')
            ->assertSet('errorMessage', 'At least 2 packages are required for consolidation.');
    }

    /** @test */
    public function it_shows_error_when_no_packages_selected_for_consolidation()
    {
        $this->actingAs($this->user);

        Livewire::test(PackageComponent::class)
            ->set('consolidationMode', true)
            ->set('selectedPackagesForConsolidation', [])
            ->call('consolidateSelectedPackages')
            ->assertSet('errorMessage', 'Please select at least 2 packages to consolidate.');
    }

    /** @test */
    public function it_can_unconsolidate_packages()
    {
        $this->actingAs($this->adminUser);

        // Create consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->adminUser->id,
            'created_by' => $this->adminUser->id,
            'is_active' => true
        ]);

        $package1 = Package::factory()->create([
            'user_id' => $this->adminUser->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
            'status' => PackageStatus::PENDING
        ]);
        $package2 = Package::factory()->create([
            'user_id' => $this->adminUser->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
            'status' => PackageStatus::PENDING
        ]);

        Livewire::test(PackageComponent::class)
            ->call('unconsolidatePackage', $consolidatedPackage->id)
            ->assertSet('successMessage', 'Packages unconsolidated successfully');

        // Verify unconsolidation
        $consolidatedPackage->refresh();
        $package1->refresh();
        $package2->refresh();

        $this->assertFalse($consolidatedPackage->is_active);
        $this->assertFalse($package1->is_consolidated);
        $this->assertFalse($package2->is_consolidated);
        $this->assertNull($package1->consolidated_package_id);
        $this->assertNull($package2->consolidated_package_id);
    }

    /** @test */
    public function it_can_clear_selected_packages()
    {
        $this->actingAs($this->user);

        $package1 = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false
        ]);
        $package2 = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false
        ]);

        Livewire::test(PackageComponent::class)
            ->set('consolidationMode', true)
            ->set('selectedPackagesForConsolidation', [$package1->id, $package2->id])
            ->set('consolidationNotes', 'Test notes')
            ->call('clearSelectedPackages')
            ->assertSet('selectedPackagesForConsolidation', [])
            ->assertSet('consolidationNotes', '');
    }

    /** @test */
    public function it_loads_individual_packages_for_current_user()
    {
        $this->actingAs($this->user);

        // Create packages for current user
        $package1 = Package::factory()->create([
            'user_id' => $this->user->id,
            'is_consolidated' => false
        ]);
        $package2 = Package::factory()->create([
            'user_id' => $this->user->id,
            'is_consolidated' => false
        ]);

        // Create package for different user
        $otherUser = User::factory()->create();
        Package::factory()->create([
            'user_id' => $otherUser->id,
            'is_consolidated' => false
        ]);

        $component = Livewire::test(PackageComponent::class);
        $individualPackages = $component->instance()->getIndividualPackagesProperty();

        $this->assertCount(2, $individualPackages);
        $this->assertTrue($individualPackages->contains('id', $package1->id));
        $this->assertTrue($individualPackages->contains('id', $package2->id));
    }

    /** @test */
    public function it_loads_consolidated_packages_for_current_user()
    {
        $this->actingAs($this->user);

        // Create consolidated package for current user
        $consolidatedPackage1 = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->user->id,
            'is_active' => true
        ]);
        $consolidatedPackage2 = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->user->id,
            'is_active' => true
        ]);

        // Create consolidated package for different user
        $otherUser = User::factory()->create();
        ConsolidatedPackage::factory()->create([
            'customer_id' => $otherUser->id,
            'is_active' => true
        ]);

        $component = Livewire::test(PackageComponent::class);
        $consolidatedPackages = $component->instance()->getConsolidatedPackagesProperty();

        $this->assertCount(2, $consolidatedPackages);
        $this->assertTrue($consolidatedPackages->contains('id', $consolidatedPackage1->id));
        $this->assertTrue($consolidatedPackages->contains('id', $consolidatedPackage2->id));
    }

    /** @test */
    public function it_loads_packages_available_for_consolidation()
    {
        $this->actingAs($this->user);

        // Create packages available for consolidation
        $availablePackage1 = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false,
            'consolidated_package_id' => null
        ]);
        $availablePackage2 = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PROCESSING,
            'is_consolidated' => false,
            'consolidated_package_id' => null
        ]);

        // Create package not available for consolidation (already consolidated)
        Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => true,
            'consolidated_package_id' => 1
        ]);

        // Create package not available for consolidation (delivered status)
        Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::DELIVERED,
            'is_consolidated' => false,
            'consolidated_package_id' => null
        ]);

        $component = Livewire::test(PackageComponent::class);
        $availablePackages = $component->instance()->getAvailablePackagesForConsolidationProperty();

        $this->assertCount(2, $availablePackages);
        $this->assertTrue($availablePackages->contains('id', $availablePackage1->id));
        $this->assertTrue($availablePackages->contains('id', $availablePackage2->id));
    }

    /** @test */
    public function it_correctly_identifies_selected_packages()
    {
        $this->actingAs($this->user);

        $package1 = Package::factory()->create(['user_id' => $this->user->id]);
        $package2 = Package::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::test(PackageComponent::class)
            ->set('selectedPackagesForConsolidation', [$package1->id]);

        $this->assertTrue($component->instance()->isPackageSelected($package1->id));
        $this->assertFalse($component->instance()->isPackageSelected($package2->id));
    }

    /** @test */
    public function it_correctly_counts_selected_packages()
    {
        $this->actingAs($this->user);

        $package1 = Package::factory()->create(['user_id' => $this->user->id]);
        $package2 = Package::factory()->create(['user_id' => $this->user->id]);
        $package3 = Package::factory()->create(['user_id' => $this->user->id]);

        $component = Livewire::test(PackageComponent::class)
            ->set('selectedPackagesForConsolidation', [$package1->id, $package2->id, $package3->id]);

        $this->assertEquals(3, $component->instance()->getSelectedPackagesCountProperty());
    }

    /** @test */
    public function it_clears_selections_when_toggling_consolidation_mode()
    {
        $this->actingAs($this->user);

        $package = Package::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(PackageComponent::class)
            ->set('consolidationMode', true)
            ->set('selectedPackagesForConsolidation', [$package->id])
            ->set('consolidationNotes', 'Test notes')
            ->call('toggleConsolidationMode')
            ->assertSet('selectedPackagesForConsolidation', [])
            ->assertSet('consolidationNotes', '');
    }

    /** @test */
    public function it_clears_selections_when_toggling_consolidated_view()
    {
        $this->actingAs($this->user);

        $package = Package::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(PackageComponent::class)
            ->set('selectedPackagesForConsolidation', [$package->id])
            ->call('toggleConsolidatedView')
            ->assertSet('selectedPackagesForConsolidation', []);
    }

    /** @test */
    public function it_resets_messages_when_performing_actions()
    {
        $this->actingAs($this->user);

        Livewire::test(PackageComponent::class)
            ->set('successMessage', 'Previous success')
            ->set('errorMessage', 'Previous error')
            ->call('toggleConsolidationMode')
            ->assertSet('successMessage', '')
            ->assertSet('errorMessage', '');
    }

    /** @test */
    public function it_handles_consolidation_service_errors_gracefully()
    {
        $this->actingAs($this->user);

        // Create packages that will fail consolidation (different customers)
        $otherUser = User::factory()->create();
        $package1 = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false
        ]);
        $package2 = Package::factory()->create([
            'user_id' => $otherUser->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false
        ]);

        Livewire::test(PackageComponent::class)
            ->set('consolidationMode', true)
            ->set('selectedPackagesForConsolidation', [$package1->id, $package2->id])
            ->call('consolidateSelectedPackages')
            ->assertSet('errorMessage', 'All packages must belong to the same customer');
    }

    /** @test */
    public function it_emits_events_after_successful_operations()
    {
        $this->actingAs($this->adminUser);

        // Test consolidation event
        $package1 = Package::factory()->create([
            'user_id' => $this->adminUser->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false
        ]);
        $package2 = Package::factory()->create([
            'user_id' => $this->adminUser->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false
        ]);

        Livewire::test(PackageComponent::class)
            ->set('consolidationMode', true)
            ->set('selectedPackagesForConsolidation', [$package1->id, $package2->id])
            ->call('consolidateSelectedPackages')
            ->assertEmitted('packagesConsolidated');

        // Test unconsolidation event
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->adminUser->id,
            'created_by' => $this->adminUser->id,
            'is_active' => true
        ]);

        Livewire::test(PackageComponent::class)
            ->call('unconsolidatePackage', $consolidatedPackage->id)
            ->assertEmitted('packagesUnconsolidated');
    }
}