<?php

namespace Tests\Feature;

use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReadOnlyPackageDisplayTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_read_only_template_for_closed_manifest_individual_packages()
    {
        // Create a closed manifest
        $manifest = Manifest::factory()->create(['is_open' => false]);
        
        // Create some packages
        $packages = Package::factory()->count(3)->create([
            'manifest_id' => $manifest->id,
            'status' => PackageStatus::PROCESSING
        ]);

        // Create an admin user
        $admin = User::factory()->create();
        $this->actingAs($admin);

        // Test the individual packages tab component
        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $manifest]);

        // Verify it uses the read-only template
        $component->assertViewIs('livewire.manifests.packages.read-only-package-display');
        
        // Verify read-only notice is displayed
        $component->assertSee('Manifest is Closed - Read Only View');
        $component->assertSee('This manifest is locked and cannot be edited');
        
        // Verify packages are displayed
        foreach ($packages as $package) {
            $component->assertSee($package->tracking_number);
        }
        
        // Verify locked indicators are shown
        $component->assertSee('Locked');
        $component->assertSee('View Only');
    }

    /** @test */
    public function it_displays_editable_template_for_open_manifest_individual_packages()
    {
        // Create an open manifest
        $manifest = Manifest::factory()->create(['is_open' => true]);
        
        // Create some packages
        Package::factory()->count(3)->create([
            'manifest_id' => $manifest->id,
            'status' => PackageStatus::PROCESSING
        ]);

        // Create an admin role and user
        $adminRole = \App\Models\Role::factory()->create(['name' => 'admin']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->actingAs($admin);

        // Test the individual packages tab component
        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $manifest]);

        // Verify it uses the editable template
        $component->assertViewIs('livewire.manifests.individual-packages-tab');
        
        // Verify editable elements are present (like select all checkbox)
        $component->assertSee('Select All');
    }

    /** @test */
    public function read_only_template_shows_package_details_correctly()
    {
        // Create a closed manifest
        $manifest = Manifest::factory()->create(['is_open' => false]);
        
        // Create a package with detailed information
        $package = Package::factory()->create([
            'manifest_id' => $manifest->id,
            'status' => PackageStatus::READY,
            'tracking_number' => 'TEST123456',
            'description' => 'Test package description',
            'weight' => 5.5,
            'freight_price' => 25.00,
            'customs_duty' => 10.00,
            'storage_fee' => 5.00,
            'delivery_fee' => 15.00,
            'estimated_value' => 100.00
        ]);

        // Create an admin user
        $admin = User::factory()->create();
        $this->actingAs($admin);

        // Test the individual packages tab component
        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $manifest]);

        // Verify package details are displayed correctly
        $component->assertSee('TEST123456');
        $component->assertSee('Test package description');
        $component->assertSee('5.50 lbs');
        $component->assertSee('$25.00'); // Freight
        $component->assertSee('$10.00'); // Customs
        $component->assertSee('$5.00');  // Storage
        $component->assertSee('$15.00'); // Delivery
        $component->assertSee('$55.00'); // Total (25+10+5+15)
        
        // Verify status badge is shown
        $component->assertSee('Ready');
    }

    /** @test */
    public function read_only_template_allows_search_and_filtering()
    {
        // Create a closed manifest
        $manifest = Manifest::factory()->create(['is_open' => false]);
        
        // Create packages with different statuses
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'status' => PackageStatus::PROCESSING,
            'tracking_number' => 'SEARCH123'
        ]);
        
        Package::factory()->create([
            'manifest_id' => $manifest->id,
            'status' => PackageStatus::READY,
            'tracking_number' => 'OTHER456'
        ]);

        // Create an admin user
        $admin = User::factory()->create();
        $this->actingAs($admin);

        // Test the individual packages tab component
        $component = Livewire::test('manifests.individual-packages-tab', ['manifest' => $manifest]);

        // Test search functionality
        $component->set('search', 'SEARCH123')
                  ->assertSee('SEARCH123')
                  ->assertDontSee('OTHER456');

        // Test status filtering
        $component->set('search', '')
                  ->set('statusFilter', 'ready')
                  ->assertSee('OTHER456')
                  ->assertDontSee('SEARCH123');

        // Test clear filters
        $component->call('clearFilters')
                  ->assertSee('SEARCH123')
                  ->assertSee('OTHER456');
    }
}