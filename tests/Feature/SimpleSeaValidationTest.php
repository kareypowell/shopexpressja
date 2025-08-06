<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Manifest;
use App\Models\Office;
use App\Models\Shipper;
use App\Http\Livewire\Manifests\Packages\ManifestPackage;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleSeaValidationTest extends TestCase
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
    public function it_requires_container_type_for_sea_packages()
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
            ->set('container_type', '') // Missing container type
            ->set('length_inches', '10')
            ->set('width_inches', '10')
            ->set('height_inches', '10')
            ->set('items', [['description' => 'Test Item', 'quantity' => 1]])
            ->call('store')
            ->assertHasErrors(['container_type']);
    }

    /** @test */
    public function it_requires_dimensions_for_sea_packages()
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
            ->set('length_inches', '0') // Invalid dimension (too small)
            ->set('width_inches', '10')
            ->set('height_inches', '10')
            ->set('items', [['description' => 'Test Item', 'quantity' => 1]]);
            
        // Debug: Check if it's detecting as sea manifest
        $this->assertTrue($component->instance()->isSeaManifest(), 'Component should detect sea manifest');
        
        $component->call('store')
            ->assertHasErrors(['length_inches']);
    }

    /** @test */
    public function it_requires_items_for_sea_packages()
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
}