<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\Rate;
use App\Models\Office;
use App\Models\Shipper;
use App\Http\Livewire\Manifests\Manifest as ManifestComponent;
use App\Http\Livewire\Manifests\Packages\ManifestPackage;
use App\Services\SeaRateCalculator;
use App\Exceptions\SeaRateNotFoundException;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SeaManifestValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user with admin role
        $adminRole = Role::where('name', 'superadmin')->first();
        $this->user = User::factory()->create(['role_id' => $adminRole->id]);
        $this->actingAs($this->user);
        
        // Create test data
        $this->office = Office::factory()->create();
        $this->shipper = Shipper::factory()->create();
        $customerRole = Role::where('name', 'customer')->first();
        $this->customer = User::factory()->create(['role_id' => $customerRole->id]);
    }

    /** @test */
    public function it_validates_vessel_information_for_sea_manifests()
    {
        Livewire::test(ManifestComponent::class)
            ->set('type', 'sea')
            ->set('name', 'Test Sea Manifest')
            ->set('reservation_number', 'RES123')
            ->set('exchange_rate', '1.5')
            ->set('shipment_date', '2025-01-01')
            ->set('vessel_name', '') // Empty vessel name
            ->set('voyage_number', '')
            ->set('departure_port', '')
            ->call('store')
            ->assertHasErrors([
                'vessel_name' => 'required',
                'voyage_number' => 'required', 
                'departure_port' => 'required'
            ]);
    }

    /** @test */
    public function it_validates_vessel_name_minimum_length()
    {
        Livewire::test(ManifestComponent::class)
            ->set('type', 'sea')
            ->set('name', 'Test Sea Manifest')
            ->set('reservation_number', 'RES123')
            ->set('exchange_rate', '1.5')
            ->set('shipment_date', '2025-01-01')
            ->set('vessel_name', 'A') // Too short
            ->set('voyage_number', 'V123')
            ->set('departure_port', 'Port A')
            ->call('store')
            ->assertHasErrors(['vessel_name' => 'min']);
    }

    /** @test */
    public function it_validates_estimated_arrival_date_after_shipment_date()
    {
        Livewire::test(ManifestComponent::class)
            ->set('type', 'sea')
            ->set('name', 'Test Sea Manifest')
            ->set('reservation_number', 'RES123')
            ->set('exchange_rate', '1.5')
            ->set('shipment_date', '2025-01-01')
            ->set('vessel_name', 'Test Vessel')
            ->set('voyage_number', 'V123')
            ->set('departure_port', 'Port A')
            ->set('estimated_arrival_date', '2024-12-31') // Before shipment date
            ->call('store')
            ->assertHasErrors(['estimated_arrival_date' => 'after']);
    }

    /** @test */
    public function it_validates_container_dimensions_for_sea_packages()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea']);
        
        $component = Livewire::test(ManifestPackage::class)
            ->set('manifest_id', $manifest->id)
            ->set('user_id', $this->customer->id)
            ->set('shipper_id', $this->shipper->id)
            ->set('office_id', $this->office->id)
            ->set('tracking_number', 'TEST123')
            ->set('description', 'Test Package')
            ->set('weight', '10')
            ->set('estimated_value', '100')
            ->set('container_type', 'box')
            ->set('length_inches', '0') // Invalid dimension
            ->set('width_inches', '10')
            ->set('height_inches', '10')
            ->set('items', [['description' => 'Test Item', 'quantity' => 1]])
            ->call('store');
            
        // Debug: Check if it's detecting as sea manifest
        $this->assertTrue($component->instance()->isSeaManifest());
        
        $component->assertHasErrors(['length_inches']);
    }

    /** @test */
    public function it_validates_package_items_for_sea_packages()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea']);
        
        Livewire::test(ManifestPackage::class)
            ->set('manifest_id', $manifest->id)
            ->set('user_id', $this->customer->id)
            ->set('shipper_id', $this->shipper->id)
            ->set('office_id', $this->office->id)
            ->set('tracking_number', 'TEST123')
            ->set('description', 'Test Package')
            ->set('weight', '10')
            ->set('estimated_value', '100')
            ->set('container_type', 'box')
            ->set('length_inches', '10')
            ->set('width_inches', '10')
            ->set('height_inches', '10')
            ->set('items', []) // No items
            ->call('store')
            ->assertHasErrors(['items']);
    }

    /** @test */
    public function it_validates_item_descriptions_and_quantities()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea']);
        
        Livewire::test(ManifestPackage::class)
            ->set('manifest_id', $manifest->id)
            ->set('user_id', $this->customer->id)
            ->set('shipper_id', $this->shipper->id)
            ->set('office_id', $this->office->id)
            ->set('tracking_number', 'TEST123')
            ->set('description', 'Test Package')
            ->set('weight', '10')
            ->set('estimated_value', '100')
            ->set('container_type', 'box')
            ->set('length_inches', '10')
            ->set('width_inches', '10')
            ->set('height_inches', '10')
            ->set('items', [
                ['description' => '', 'quantity' => 0], // Invalid item
                ['description' => 'A', 'quantity' => -1], // Invalid item
            ])
            ->call('store')
            ->assertHasErrors([
                'items.0.description' => 'required',
                'items.0.quantity' => 'min',
                'items.1.description' => 'min',
                'items.1.quantity' => 'min'
            ]);
    }

    /** @test */
    public function it_validates_container_type_for_sea_packages()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea']);
        
        Livewire::test(ManifestPackage::class)
            ->set('manifest_id', $manifest->id)
            ->set('user_id', $this->customer->id)
            ->set('shipper_id', $this->shipper->id)
            ->set('office_id', $this->office->id)
            ->set('tracking_number', 'TEST123')
            ->set('description', 'Test Package')
            ->set('weight', '10')
            ->set('estimated_value', '100')
            ->set('container_type', 'invalid_type') // Invalid container type
            ->set('length_inches', '10')
            ->set('width_inches', '10')
            ->set('height_inches', '10')
            ->set('items', [['description' => 'Test Item', 'quantity' => 1]])
            ->call('store')
            ->assertHasErrors(['container_type' => 'in']);
    }

    /** @test */
    public function it_handles_missing_sea_rates_gracefully()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea', 'exchange_rate' => 1.5]);
        
        // Create a package without any sea rates in the system
        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'container_type' => 'box',
            'length_inches' => 10,
            'width_inches' => 10,
            'height_inches' => 10,
            'cubic_feet' => 5.787 // (10*10*10)/1728
        ]);

        $calculator = new SeaRateCalculator();
        
        $this->expectException(SeaRateNotFoundException::class);
        $calculator->calculateFreightPrice($package);
    }

    /** @test */
    public function it_provides_user_friendly_error_message_for_missing_rates()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea', 'exchange_rate' => 1.5]);
        
        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'container_type' => 'box',
            'length_inches' => 10,
            'width_inches' => 10,
            'height_inches' => 10,
            'cubic_feet' => 5.787
        ]);

        $calculator = new SeaRateCalculator();
        
        try {
            $calculator->calculateFreightPrice($package);
        } catch (SeaRateNotFoundException $e) {
            $this->assertStringContainsString('5.787 cubic feet', $e->getUserMessage());
            $this->assertStringContainsString('contact support', $e->getUserMessage());
        }
    }

    /** @test */
    public function it_validates_cubic_feet_calculation()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea']);
        
        Livewire::test(ManifestPackage::class)
            ->set('manifest_id', $manifest->id)
            ->set('user_id', $this->customer->id)
            ->set('shipper_id', $this->shipper->id)
            ->set('office_id', $this->office->id)
            ->set('tracking_number', 'TEST123')
            ->set('description', 'Test Package')
            ->set('weight', '10')
            ->set('estimated_value', '100')
            ->set('container_type', 'box')
            ->set('length_inches', '0') // Will result in 0 cubic feet
            ->set('width_inches', '10')
            ->set('height_inches', '10')
            ->set('items', [['description' => 'Test Item', 'quantity' => 1]])
            ->call('store')
            ->assertHasErrors(['length_inches']);
    }

    /** @test */
    public function it_validates_maximum_cubic_feet()
    {
        $manifest = Manifest::factory()->create(['type' => 'sea']);
        
        Livewire::test(ManifestPackage::class)
            ->set('manifest_id', $manifest->id)
            ->set('user_id', $this->customer->id)
            ->set('shipper_id', $this->shipper->id)
            ->set('office_id', $this->office->id)
            ->set('tracking_number', 'TEST123')
            ->set('description', 'Test Package')
            ->set('weight', '10')
            ->set('estimated_value', '100')
            ->set('container_type', 'box')
            ->set('length_inches', '1001') // Exceeds maximum
            ->set('width_inches', '10')
            ->set('height_inches', '10')
            ->set('items', [['description' => 'Test Item', 'quantity' => 1]])
            ->call('store')
            ->assertHasErrors(['length_inches']);
    }

    /** @test */
    public function it_does_not_validate_sea_fields_for_air_manifests()
    {
        Livewire::test(ManifestComponent::class)
            ->set('type', 'air')
            ->set('name', 'Test Air Manifest')
            ->set('reservation_number', 'RES123')
            ->set('exchange_rate', '1.5')
            ->set('shipment_date', '2025-01-01')
            ->set('flight_number', 'FL123')
            ->set('flight_destination', 'Miami')
            ->set('vessel_name', '') // Should not be validated for air manifests
            ->call('store')
            ->assertHasNoErrors(['vessel_name', 'voyage_number', 'departure_port']);
    }

    /** @test */
    public function it_does_not_validate_container_fields_for_air_packages()
    {
        $manifest = Manifest::factory()->create(['type' => 'air']);
        
        Livewire::test(ManifestPackage::class)
            ->set('manifest_id', $manifest->id)
            ->set('user_id', $this->customer->id)
            ->set('shipper_id', $this->shipper->id)
            ->set('office_id', $this->office->id)
            ->set('tracking_number', 'TEST123')
            ->set('description', 'Test Package')
            ->set('weight', '10')
            ->set('estimated_value', '100')
            ->set('container_type', '') // Should not be validated for air packages
            ->set('length_inches', '')
            ->set('width_inches', '')
            ->set('height_inches', '')
            ->call('store')
            ->assertHasNoErrors(['container_type', 'length_inches', 'width_inches', 'height_inches', 'items']);
    }
}