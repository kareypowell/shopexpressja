<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Livewire\Dashboard;
use App\Models\User;
use App\Models\Package;
use App\Models\Manifest;
use App\Models\Shipper;
use App\Models\Office;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class CustomerDashboardModalTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $package;

    protected function setUp(): void
    {
        parent::setUp();

        // Create customer role and user
        $customerRole = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']);
        $this->customer = User::factory()->create(['role_id' => $customerRole->id]);

        // Create supporting models
        $manifest = Manifest::factory()->create();
        $shipper = Shipper::factory()->create();
        $office = Office::factory()->create();

        // Create a package for the customer
        $this->package = Package::factory()->create([
            'user_id' => $this->customer->id,
            'manifest_id' => $manifest->id,
            'shipper_id' => $shipper->id,
            'office_id' => $office->id,
            'status' => 'ready_for_pickup',
            'tracking_number' => 'DASH123',
            'description' => 'Dashboard Test Package',
            'weight' => 5.0,
        ]);
    }

    /** @test */
    public function customer_dashboard_includes_modal_wrapper()
    {
        $this->actingAs($this->customer);

        $component = Livewire::test(Dashboard::class);

        // Check that the dashboard renders without errors
        $component->assertStatus(200);
        
        // Check that it shows customer content
        $component->assertSee('Welcome back, ' . $this->customer->first_name);
        $component->assertSee('Packages');
        
        // Check that the package is displayed
        $component->assertSee('DASH123');
        $component->assertSee('Dashboard Test Package');
        $component->assertSee('View Details');
    }
}