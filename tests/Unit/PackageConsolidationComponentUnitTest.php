<?php

namespace Tests\Unit;

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

class PackageConsolidationComponentUnitTest extends TestCase
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
    public function it_can_toggle_package_selection()
    {
        $this->actingAs($this->user);

        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false
        ]);

        $component = new PackageComponent();
        $component->consolidationMode = true;

        // Test selecting a package
        $component->togglePackageSelection($package->id);
        $this->assertContains($package->id, $component->selectedPackagesForConsolidation);

        // Test deselecting a package
        $component->togglePackageSelection($package->id);
        $this->assertNotContains($package->id, $component->selectedPackagesForConsolidation);
    }

    /** @test */
    public function it_prevents_selection_when_consolidation_mode_is_off()
    {
        $this->actingAs($this->user);

        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => false
        ]);

        $component = new PackageComponent();
        $component->consolidationMode = false;

        $component->togglePackageSelection($package->id);
        $this->assertEmpty($component->selectedPackagesForConsolidation);
    }

    /** @test */
    public function it_can_clear_selected_packages()
    {
        $this->actingAs($this->user);

        $package1 = Package::factory()->create(['user_id' => $this->user->id]);
        $package2 = Package::factory()->create(['user_id' => $this->user->id]);

        $component = new PackageComponent();
        $component->selectedPackagesForConsolidation = [$package1->id, $package2->id];
        $component->consolidationNotes = 'Test notes';

        $component->clearSelectedPackages();

        $this->assertEmpty($component->selectedPackagesForConsolidation);
        $this->assertEmpty($component->consolidationNotes);
    }

    /** @test */
    public function it_correctly_identifies_selected_packages()
    {
        $this->actingAs($this->user);

        $package1 = Package::factory()->create(['user_id' => $this->user->id]);
        $package2 = Package::factory()->create(['user_id' => $this->user->id]);

        $component = new PackageComponent();
        $component->selectedPackagesForConsolidation = [$package1->id];

        $this->assertTrue($component->isPackageSelected($package1->id));
        $this->assertFalse($component->isPackageSelected($package2->id));
    }

    /** @test */
    public function it_correctly_counts_selected_packages()
    {
        $this->actingAs($this->user);

        $package1 = Package::factory()->create(['user_id' => $this->user->id]);
        $package2 = Package::factory()->create(['user_id' => $this->user->id]);
        $package3 = Package::factory()->create(['user_id' => $this->user->id]);

        $component = new PackageComponent();
        $component->selectedPackagesForConsolidation = [$package1->id, $package2->id, $package3->id];

        $this->assertEquals(3, $component->getSelectedPackagesCountProperty());
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

        $component = new PackageComponent();
        $individualPackages = $component->getIndividualPackagesProperty();

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

        $component = new PackageComponent();
        $consolidatedPackages = $component->getConsolidatedPackagesProperty();

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

        // Create consolidated package first
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->user->id,
            'created_by' => $this->user->id,
            'is_active' => true
        ]);

        // Create package not available for consolidation (already consolidated)
        Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::PENDING,
            'is_consolidated' => true,
            'consolidated_package_id' => $consolidatedPackage->id
        ]);

        // Create package not available for consolidation (delivered status)
        Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => PackageStatus::DELIVERED,
            'is_consolidated' => false,
            'consolidated_package_id' => null
        ]);

        $component = new PackageComponent();
        $availablePackages = $component->getAvailablePackagesForConsolidationProperty();

        $this->assertCount(2, $availablePackages);
        $this->assertTrue($availablePackages->contains('id', $availablePackage1->id));
        $this->assertTrue($availablePackages->contains('id', $availablePackage2->id));
    }

    /** @test */
    public function it_validates_consolidation_requirements()
    {
        $this->actingAs($this->user);

        $component = new PackageComponent();

        // Test with no packages selected
        $component->selectedPackagesForConsolidation = [];
        $component->consolidateSelectedPackages();
        $this->assertStringContainsString('Please select at least 2 packages', $component->errorMessage);

        // Test with only one package selected
        $package = Package::factory()->create(['user_id' => $this->user->id]);
        $component->selectedPackagesForConsolidation = [$package->id];
        $component->consolidateSelectedPackages();
        $this->assertStringContainsString('At least 2 packages are required', $component->errorMessage);
    }

    /** @test */
    public function it_can_consolidate_packages_successfully()
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

        $component = new PackageComponent();
        $component->selectedPackagesForConsolidation = [$package1->id, $package2->id];
        $component->consolidationNotes = 'Test consolidation';

        $component->consolidateSelectedPackages();

        // Check success message
        $this->assertStringContainsString('successfully', $component->successMessage);
        $this->assertEmpty($component->selectedPackagesForConsolidation);
        $this->assertEmpty($component->consolidationNotes);

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
    public function it_can_unconsolidate_packages_successfully()
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

        $component = new PackageComponent();
        $component->unconsolidatePackage($consolidatedPackage->id);

        // Check success message
        $this->assertStringContainsString('successfully', $component->successMessage);

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
}