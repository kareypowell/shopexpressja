<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use App\Models\User;
use App\Models\Manifest;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\Office;
use App\Models\Shipper;
use App\Models\Role;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ManifestTabsInitialLoadTest extends DuskTestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $manifest;
    protected $office;
    protected $shipper;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $adminRole = Role::create(['name' => 'superadmin', 'description' => 'Super Administrator']);
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);

        // Create customer user
        $customerRole = Role::create(['name' => 'Customer', 'description' => 'Customer']);
        $this->customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'email_verified_at' => now(),
        ]);

        // Create office and shipper
        $this->office = Office::factory()->create();
        $this->shipper = Shipper::factory()->create();

        // Create manifest
        $this->manifest = Manifest::factory()->create();
    }

    /** @test */
    public function dropdown_actions_work_on_initial_page_load()
    {
        // Create individual packages
        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        // Create consolidated packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit(route('admin.manifests.packages', $this->manifest))
                    ->waitFor('[role="tabpanel"]')
                    ->pause(1000); // Wait for Alpine.js to initialize

            // Test individual packages tab (should be active by default)
            $browser->assertVisible('#tabpanel-individual')
                    ->assertHidden('#tabpanel-consolidated');

            // Test that dropdown actions work on initial load for individual packages
            $browser->click('#tabpanel-individual .relative button[aria-haspopup]')
                    ->waitFor('#tabpanel-individual .relative div[x-show]')
                    ->assertSee('Toggle Details')
                    ->assertSee('Update Fees');

            // Close the dropdown
            $browser->click('body')
                    ->waitUntilMissing('#tabpanel-individual .relative div[x-show]');

            // Switch to consolidated packages tab
            $browser->click('[role="tab"][aria-controls="tabpanel-consolidated"]')
                    ->waitFor('#tabpanel-consolidated:not(.hidden)')
                    ->assertHidden('#tabpanel-individual')
                    ->assertVisible('#tabpanel-consolidated');

            // Test that dropdown actions work for consolidated packages after tab switch
            $browser->click('#tabpanel-consolidated .relative button[aria-haspopup]')
                    ->waitFor('#tabpanel-consolidated .relative div[x-show]')
                    ->assertSee('Toggle Details');

            // Close the dropdown
            $browser->click('body')
                    ->waitUntilMissing('#tabpanel-consolidated .relative div[x-show]');

            // Switch back to individual packages tab
            $browser->click('[role="tab"][aria-controls="tabpanel-individual"]')
                    ->waitFor('#tabpanel-individual:not(.hidden)')
                    ->assertVisible('#tabpanel-individual')
                    ->assertHidden('#tabpanel-consolidated');

            // Test that dropdown actions still work after switching back
            $browser->click('#tabpanel-individual .relative button[aria-haspopup]')
                    ->waitFor('#tabpanel-individual .relative div[x-show]')
                    ->assertSee('Toggle Details')
                    ->assertSee('Update Fees');
        });
    }

    /** @test */
    public function both_tab_components_are_rendered_on_initial_load()
    {
        // Create packages for both tabs
        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
        ]);

        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        Package::factory()->create([
            'manifest_id' => $this->manifest->id,
            'user_id' => $this->customer->id,
            'office_id' => $this->office->id,
            'shipper_id' => $this->shipper->id,
            'consolidated_package_id' => $consolidatedPackage->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit(route('admin.manifests.packages', $this->manifest))
                    ->waitFor('[role="tabpanel"]')
                    ->pause(1000); // Wait for Alpine.js to initialize

            // Verify both tab panels exist in the DOM (even if one is hidden)
            $browser->assertPresent('#tabpanel-individual')
                    ->assertPresent('#tabpanel-consolidated');

            // Verify the correct initial visibility
            $browser->assertVisible('#tabpanel-individual')
                    ->assertHidden('#tabpanel-consolidated');

            // Verify both panels have their Alpine.js components initialized
            // by checking for the presence of dropdown buttons
            $browser->assertPresent('#tabpanel-individual .relative button')
                    ->assertPresent('#tabpanel-consolidated .relative button');
        });
    }
}