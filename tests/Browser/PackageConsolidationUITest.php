<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\Manifest;
use App\Models\Office;
use App\Models\Shipper;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PackageConsolidationUITest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $adminUser;
    protected $customerUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and users
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);
        
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customerUser = User::factory()->create(['role_id' => $customerRole->id]);
    }

    /** @test */
    public function user_can_toggle_consolidation_mode_and_see_visual_indicators()
    {
        // Create test packages
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'pending'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->customerUser)
                ->visit('/packages')
                ->assertSee('Packages')
                ->assertSee('Consolidation Mode')
                
                // Toggle consolidation mode on
                ->click('input[wire:model="consolidationMode"]')
                ->waitForText('Consolidation Mode Active')
                ->assertSee('Select packages to consolidate them into a single group')
                ->assertPresent('.bg-gradient-to-r.from-blue-50')
                
                // Toggle consolidation mode off
                ->click('input[wire:model="consolidationMode"]')
                ->waitUntilMissing('.bg-gradient-to-r.from-blue-50')
                ->assertDontSee('Consolidation Mode Active');
        });
    }

    /** @test */
    public function user_can_select_packages_for_consolidation_with_visual_feedback()
    {
        // Create test packages
        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'pending',
            'weight' => 10.5,
            'freight_price' => 25.00
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->customerUser)
                ->visit('/packages')
                
                // Enable consolidation mode
                ->click('input[wire:model="consolidationMode"]')
                ->waitForText('Consolidation Mode Active')
                
                // Select first package
                ->click('.space-y-3 > div:first-child')
                ->waitForText('1 package(s) selected for consolidation')
                ->assertPresent('.ring-2.ring-blue-500')
                ->assertSee('Selected')
                
                // Select second package
                ->click('.space-y-3 > div:nth-child(2)')
                ->waitForText('2 package(s) selected for consolidation')
                ->assertSee('Selection Summary')
                ->assertSee('2') // Package count
                ->assertSee('21.0') // Total weight
                ->assertSee('$50.00') // Total cost
                
                // Deselect first package
                ->click('.space-y-3 > div:first-child')
                ->waitForText('1 package(s) selected for consolidation')
                ->assertDontSee('Selection Summary');
        });
    }

    /** @test */
    public function user_can_consolidate_selected_packages()
    {
        // Create test packages
        $packages = Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'status' => 'pending',
            'weight' => 10.0,
            'freight_price' => 25.00
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->customerUser)
                ->visit('/packages')
                
                // Enable consolidation mode
                ->click('input[wire:model="consolidationMode"]')
                ->waitForText('Consolidation Mode Active')
                
                // Select packages
                ->click('.space-y-3 > div:first-child')
                ->click('.space-y-3 > div:nth-child(2)')
                ->waitForText('2 package(s) selected for consolidation')
                
                // Add consolidation notes
                ->type('textarea[wire:model="consolidationNotes"]', 'Test consolidation notes')
                
                // Consolidate packages
                ->click('button:contains("Consolidate Selected")')
                ->waitForText('Packages consolidated successfully')
                ->assertSee('Packages consolidated successfully');
        });
    }

    /** @test */
    public function user_can_view_consolidated_packages_with_expandable_details()
    {
        // Create consolidated package with individual packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'consolidated_tracking_number' => 'CONS-20241208-0001'
        ]);

        $packages = Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
            'status' => 'pending'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->customerUser)
                ->visit('/packages')
                
                // Toggle to consolidated view
                ->click('input[wire:model="showConsolidatedView"]')
                ->waitForText('Consolidated Packages')
                ->assertSee('CONS-20241208-0001')
                ->assertSee('3') // Package count in summary
                ->assertPresent('.bg-gradient-to-r.from-green-50')
                
                // Expand package details
                ->click('button:contains("Show Details")')
                ->waitForText('Hide Details')
                ->assertVisible('#packages-' . $consolidatedPackage->id)
                ->assertSee($packages->first()->tracking_number)
                
                // Collapse package details
                ->click('button:contains("Hide Details")')
                ->waitForText('Show Details')
                ->assertNotVisible('#packages-' . $consolidatedPackage->id);
        });
    }

    /** @test */
    public function user_can_unconsolidate_packages()
    {
        // Create consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'consolidated_tracking_number' => 'CONS-20241208-0001'
        ]);

        Package::factory()->count(2)->create([
            'user_id' => $this->customerUser->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
            'status' => 'pending'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->customerUser)
                ->visit('/packages')
                
                // Toggle to consolidated view
                ->click('input[wire:model="showConsolidatedView"]')
                ->waitForText('Consolidated Packages')
                ->assertSee('CONS-20241208-0001')
                
                // Unconsolidate packages
                ->click('button:contains("Unconsolidate")')
                ->acceptDialog()
                ->waitForText('Packages unconsolidated successfully')
                ->assertSee('Packages unconsolidated successfully');
        });
    }

    /** @test */
    public function consolidated_package_summary_cards_display_correct_information()
    {
        // Create consolidated package with specific totals
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id,
            'total_weight' => 25.5,
            'total_freight_price' => 75.00,
            'total_customs_duty' => 15.00,
            'total_storage_fee' => 10.00,
            'total_delivery_fee' => 5.00
        ]);

        // Create individual packages
        Package::factory()->count(3)->create([
            'user_id' => $this->customerUser->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true
        ]);

        // Create packages from different shippers
        $shipper1 = Shipper::factory()->create(['name' => 'Amazon']);
        $shipper2 = Shipper::factory()->create(['name' => 'eBay']);
        
        Package::factory()->create([
            'user_id' => $this->customerUser->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
            'shipper_id' => $shipper1->id
        ]);

        Package::factory()->create([
            'user_id' => $this->customerUser->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
            'shipper_id' => $shipper2->id
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->customerUser)
                ->visit('/packages')
                
                // Toggle to consolidated view
                ->click('input[wire:model="showConsolidatedView"]')
                ->waitForText('Consolidated Packages')
                
                // Verify summary cards display correct information
                ->assertSeeIn('.bg-white.rounded-lg.p-3.text-center.shadow-sm:nth-child(1)', '5') // Package count (3 + 2)
                ->assertSeeIn('.bg-white.rounded-lg.p-3.text-center.shadow-sm:nth-child(2)', '25.5') // Total weight
                ->assertSeeIn('.bg-white.rounded-lg.p-3.text-center.shadow-sm:nth-child(3)', '$105.00') // Total cost
                ->assertSeeIn('.bg-white.rounded-lg.p-3.text-center.shadow-sm:nth-child(4)', '2'); // Unique shippers
        });
    }

    /** @test */
    public function visual_indicators_distinguish_consolidated_from_individual_packages()
    {
        // Create individual package
        $individualPackage = Package::factory()->create([
            'user_id' => $this->customerUser->id,
            'status' => 'pending'
        ]);

        // Create consolidated package
        $consolidatedPackage = ConsolidatedPackage::factory()->create([
            'customer_id' => $this->customerUser->id
        ]);

        Package::factory()->create([
            'user_id' => $this->customerUser->id,
            'consolidated_package_id' => $consolidatedPackage->id,
            'is_consolidated' => true,
            'status' => 'pending'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->customerUser)
                ->visit('/packages')
                
                // Check individual package indicators
                ->assertSee('Individual')
                ->assertPresent('.bg-gray-100.text-gray-800')
                
                // Toggle to consolidated view
                ->click('input[wire:model="showConsolidatedView"]')
                ->waitForText('Consolidated Packages')
                
                // Check consolidated package indicators
                ->assertSee('Consolidated')
                ->assertPresent('.bg-green-100.text-green-800')
                ->assertPresent('.bg-gradient-to-r.from-green-50')
                ->assertPresent('.border-l-4.border-green-400');
        });
    }

    /** @test */
    public function empty_states_display_helpful_messages()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->customerUser)
                ->visit('/packages')
                
                // Check empty state for consolidation mode
                ->click('input[wire:model="consolidationMode"]')
                ->waitForText('No packages available for consolidation')
                ->assertSee('Packages must meet the following criteria')
                ->assertSee('Status: Pending, Processing, or Shipped')
                ->assertSee('Not already consolidated')
                ->assertSee('Belong to your account')
                
                // Check empty state for consolidated view
                ->click('input[wire:model="showConsolidatedView"]')
                ->waitForText('No consolidated packages found')
                
                // Check empty state for individual packages
                ->click('input[wire:model="showConsolidatedView"]') // Toggle off
                ->click('input[wire:model="consolidationMode"]') // Toggle off
                ->waitForText('No individual packages found')
                ->assertSee('All your packages may be consolidated');
        });
    }
}