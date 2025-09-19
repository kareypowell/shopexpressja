<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\PackageDistribution;
use App\Models\Role;
use App\Enums\PackageStatus;
use App\Http\Livewire\PackageDistribution as PackageDistributionComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class ConsolidatedPackageDistributionTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $consolidatedPackage;
    protected $packages;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);

        // Create admin user
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);

        // Create customer user
        $this->customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
        ]);

        // Create packages for consolidation
        $this->packages = Package::factory()->count(3)->create([
            'user_id' => $this->customer->id,
            'status' => PackageStatus::READY,
            'freight_price' => 50.00,
            'customs_duty' => 25.00,
            'storage_fee' => 10.00,
            'delivery_fee' => 15.00,
        ]);

        // Create consolidated package
        $this->consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->admin->id,
            'status' => PackageStatus::READY,
            'total_freight_price' => 150.00,
            'total_customs_duty' => 75.00,
            'total_storage_fee' => 30.00,
            'total_delivery_fee' => 45.00,
            'is_active' => true,
        ]);

        // Associate packages with consolidated package
        $this->packages->each(function ($package) {
            $package->update([
                'consolidated_package_id' => $this->consolidatedPackage->id,
                'is_consolidated' => true,
            ]);
        });
    }

    /** @test */
    public function it_can_toggle_to_consolidated_view()
    {
        $this->actingAs($this->admin);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->call('toggleConsolidatedView')
            ->assertSet('showConsolidatedView', true)
            ->assertSet('selectedPackages', [])
            ->assertSet('selectedConsolidatedPackages', []);
    }

    /** @test */
    public function it_displays_consolidated_packages_in_consolidated_view()
    {
        $this->actingAs($this->admin);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('showConsolidatedView', true)
            ->assertSee($this->consolidatedPackage->consolidated_tracking_number)
            ->assertSee('3 packages')
            ->assertSee('$300.00'); // Total cost
    }

    /** @test */
    public function it_can_select_consolidated_packages_for_distribution()
    {
        $this->actingAs($this->admin);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('showConsolidatedView', true)
            ->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id])
            ->assertSet('totalCost', 300.00);
    }

    /** @test */
    public function it_calculates_totals_correctly_for_consolidated_packages()
    {
        $this->actingAs($this->admin);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('showConsolidatedView', true)
            ->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id])
            ->call('calculateTotals')
            ->assertSet('totalCost', 300.00);
    }

    /** @test */
    public function it_shows_consolidated_distribution_confirmation()
    {
        $this->actingAs($this->admin);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('showConsolidatedView', true)
            ->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id])
            ->set('amountCollected', 300.00)
            ->call('showDistributionConfirmation')
            ->assertSet('showConfirmation', true)
            ->assertSee('Consolidated Package: ' . $this->consolidatedPackage->consolidated_tracking_number)
            ->assertSee('3 packages');
    }

    /** @test */
    public function it_processes_consolidated_package_distribution_successfully()
    {
        $this->actingAs($this->admin);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('showConsolidatedView', true)
            ->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id])
            ->set('amountCollected', 300.00)
            ->call('showDistributionConfirmation')
            ->call('processDistribution')
            ->assertSet('successMessage', 'Consolidated packages distributed successfully')
            ->assertSet('showConfirmation', false);

        // Verify distribution was created
        $this->assertDatabaseHas('package_distributions', [
            'customer_id' => $this->customer->id,
            'total_amount' => 300.00,
            'amount_collected' => 300.00,
            'payment_status' => 'paid',
        ]);

        // Verify packages were marked as delivered
        $this->packages->each(function ($package) {
            $this->assertDatabaseHas('packages', [
                'id' => $package->id,
                'status' => PackageStatus::DELIVERED,
            ]);
        });

        // Verify consolidated package was marked as delivered
        $this->assertDatabaseHas('consolidated_packages', [
            'id' => $this->consolidatedPackage->id,
            'status' => PackageStatus::DELIVERED,
        ]);
    }

    /** @test */
    public function it_validates_consolidated_package_selection()
    {
        $this->actingAs($this->admin);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('showConsolidatedView', true)
            ->set('amountCollected', 300.00)
            ->call('showDistributionConfirmation')
            ->assertHasErrors(['selectedConsolidatedPackages']);
    }

    /** @test */
    public function it_handles_partial_payment_for_consolidated_packages()
    {
        $this->actingAs($this->admin);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('showConsolidatedView', true)
            ->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id])
            ->set('amountCollected', 150.00) // Partial payment
            ->call('showDistributionConfirmation')
            ->call('processDistribution')
            ->assertSet('successMessage', 'Consolidated packages distributed successfully');

        // Verify distribution was created with partial payment
        $this->assertDatabaseHas('package_distributions', [
            'customer_id' => $this->customer->id,
            'total_amount' => 300.00,
            'amount_collected' => 150.00,
            'payment_status' => 'partial',
        ]);
    }

    /** @test */
    public function it_applies_write_off_to_consolidated_packages()
    {
        $this->actingAs($this->admin);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('showConsolidatedView', true)
            ->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id])
            ->set('amountCollected', 250.00)
            ->set('writeOffAmount', 50.00)
            ->set('writeOffReason', 'Customer loyalty discount')
            ->call('showDistributionConfirmation')
            ->call('processDistribution')
            ->assertSet('successMessage', 'Consolidated packages distributed successfully');

        // Verify distribution was created with write-off
        $this->assertDatabaseHas('package_distributions', [
            'customer_id' => $this->customer->id,
            'total_amount' => 300.00,
            'amount_collected' => 250.00,
            'write_off_amount' => 50.00,
            'payment_status' => 'paid',
        ]);
    }

    /** @test */
    public function it_applies_percentage_write_off_to_consolidated_packages()
    {
        $this->actingAs($this->admin);

        // Test percentage write-off (20% of $300 = $60)
        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('showConsolidatedView', true)
            ->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id])
            ->set('amountCollected', 240.00)
            ->set('writeOffType', 'percentage')
            ->set('writeOffPercentage', 20.0)
            ->set('writeOffReason', 'Customer loyalty discount - 20%')
            ->call('showDistributionConfirmation')
            ->call('processDistribution')
            ->assertSet('successMessage', 'Consolidated packages distributed successfully');

        // Verify distribution was created with correct write-off amount (20% of $300 = $60)
        $this->assertDatabaseHas('package_distributions', [
            'customer_id' => $this->customer->id,
            'total_amount' => 300.00,
            'amount_collected' => 240.00,
            'write_off_amount' => 60.00, // 20% of $300
            'payment_status' => 'paid',
        ]);
    }

    /** @test */
    public function it_searches_consolidated_packages_by_tracking_number()
    {
        $this->actingAs($this->admin);

        $searchTerm = substr($this->consolidatedPackage->consolidated_tracking_number, 0, 8);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('showConsolidatedView', true)
            ->set('search', $searchTerm)
            ->assertSee($this->consolidatedPackage->consolidated_tracking_number);
    }

    /** @test */
    public function it_searches_consolidated_packages_by_individual_package_tracking()
    {
        $this->actingAs($this->admin);

        $individualPackage = $this->packages->first();
        $searchTerm = substr($individualPackage->tracking_number, 0, 8);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('showConsolidatedView', true)
            ->set('search', $searchTerm)
            ->assertSee($this->consolidatedPackage->consolidated_tracking_number);
    }

    /** @test */
    public function it_prevents_distribution_of_multiple_consolidated_packages()
    {
        // Create another consolidated package
        $anotherConsolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->admin->id,
            'status' => PackageStatus::READY,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('showConsolidatedView', true)
            ->set('selectedConsolidatedPackages', [
                $this->consolidatedPackage->id,
                $anotherConsolidatedPackage->id
            ])
            ->set('amountCollected', 300.00)
            ->call('showDistributionConfirmation')
            ->call('processDistribution')
            ->assertSet('errorMessage', 'Please select exactly one consolidated package for distribution.');
    }

    /** @test */
    public function it_resets_form_correctly_when_switching_views()
    {
        $this->actingAs($this->admin);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('selectedPackages', [1, 2, 3])
            ->set('amountCollected', 100.00)
            ->call('toggleConsolidatedView')
            ->assertSet('selectedPackages', [])
            ->assertSet('selectedConsolidatedPackages', [])
            ->assertSet('totalCost', 0);
    }

    /** @test */
    public function it_emits_correct_event_after_consolidated_distribution()
    {
        $this->actingAs($this->admin);

        Livewire::test(PackageDistributionComponent::class)
            ->set('selectedCustomerId', $this->customer->id)
            ->set('showConsolidatedView', true)
            ->set('selectedConsolidatedPackages', [$this->consolidatedPackage->id])
            ->set('amountCollected', 300.00)
            ->call('showDistributionConfirmation')
            ->call('processDistribution')
            ->assertEmitted('packageDistributed', function ($event) {
                return $event['customer_id'] === $this->customer->id &&
                       $event['package_count'] === 3 &&
                       $event['is_consolidated'] === true;
            });
    }
}