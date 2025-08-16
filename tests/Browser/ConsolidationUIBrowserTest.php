<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\Manifest;
use App\Models\Office;
use App\Models\Shipper;
use App\Enums\PackageStatus;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ConsolidationUIBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $adminUser;
    protected $customerUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);

        // Create users
        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin@test.com'
        ]);
        
        $this->customerUser = User::factory()->create([
            'role_id' => $customerRole->id,
            'email' => 'customer@test.com',
            'account_balance' => 500.00
        ]);

        // Create required entities
        $office = Office::factory()->create();
        $shipper = Shipper::factory()->create();
        $manifest = Manifest::factory()->create();

        // Create packages for testing
        Package::factory()->count(5)->create([
            'user_id' => $this->customerUser->id,
            'manifest_id' => $manifest->id,
            'office_id' => $office->id,
            'status' => PackageStatus::READY,
            'tracking_number' => function () {
                static $counter = 1;
                return 'TEST-UI-' . str_pad($counter++, 3, '0', STR_PAD_LEFT);
            }
        ]);
    }

    /** @test */
    public function admin_can_toggle_consolidation_mode()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/packages')
                    ->waitFor('@consolidation-toggle')
                    ->assertSee('Consolidation Mode')
                    ->assertSee('OFF')
                    ->click('@consolidation-toggle')
                    ->waitForText('ON')
                    ->assertSee('ON')
                    ->assertVisible('@consolidation-controls')
                    ->assertSee('Select packages to consolidate');
        });
    }

    /** @test */
    public function admin_can_select_packages_for_consolidation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/packages')
                    ->waitFor('@consolidation-toggle')
                    ->click('@consolidation-toggle')
                    ->waitForText('ON')
                    ->waitFor('@package-checkbox-1')
                    ->check('@package-checkbox-1')
                    ->check('@package-checkbox-2')
                    ->check('@package-checkbox-3')
                    ->waitFor('@selected-count')
                    ->assertSeeIn('@selected-count', '3 packages selected')
                    ->assertVisible('@consolidate-button')
                    ->assertSee('Consolidate Selected');
        });
    }

    /** @test */
    public function admin_can_consolidate_selected_packages()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/packages')
                    ->waitFor('@consolidation-toggle')
                    ->click('@consolidation-toggle')
                    ->waitForText('ON')
                    ->waitFor('@package-checkbox-1')
                    ->check('@package-checkbox-1')
                    ->check('@package-checkbox-2')
                    ->waitFor('@consolidate-button')
                    ->click('@consolidate-button')
                    ->waitFor('@consolidation-modal')
                    ->assertSee('Consolidate Packages')
                    ->assertSee('2 packages will be consolidated')
                    ->type('@consolidation-notes', 'Browser test consolidation')
                    ->click('@confirm-consolidation')
                    ->waitForText('Packages consolidated successfully')
                    ->assertSee('Packages consolidated successfully');
        });
    }

    /** @test */
    public function admin_can_view_consolidated_packages()
    {
        // First create a consolidated package
        $packages = Package::where('user_id', $this->customerUser->id)->take(3)->get();
        $consolidationService = app(\App\Services\PackageConsolidationService::class);
        
        $result = $consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->adminUser,
            ['notes' => 'Test consolidation for UI']
        );

        $consolidatedPackage = $result['consolidated_package'];

        $this->browse(function (Browser $browser) use ($consolidatedPackage) {
            $browser->loginAs($this->adminUser)
                    ->visit('/packages')
                    ->waitFor('@consolidation-toggle')
                    ->click('@consolidation-toggle')
                    ->waitForText('ON')
                    ->click('@consolidated-view-toggle')
                    ->waitFor('@consolidated-packages')
                    ->assertSee($consolidatedPackage->consolidated_tracking_number)
                    ->assertSee('3 packages')
                    ->assertSee($consolidatedPackage->total_weight . ' lbs')
                    ->click('@expand-consolidated-' . $consolidatedPackage->id)
                    ->waitFor('@individual-packages-' . $consolidatedPackage->id)
                    ->assertVisible('@individual-packages-' . $consolidatedPackage->id);
        });
    }

    /** @test */
    public function admin_can_unconsolidate_packages()
    {
        // Create a consolidated package
        $packages = Package::where('user_id', $this->customerUser->id)->take(2)->get();
        $consolidationService = app(\App\Services\PackageConsolidationService::class);
        
        $result = $consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->adminUser
        );

        $consolidatedPackage = $result['consolidated_package'];

        $this->browse(function (Browser $browser) use ($consolidatedPackage) {
            $browser->loginAs($this->adminUser)
                    ->visit('/packages')
                    ->waitFor('@consolidation-toggle')
                    ->click('@consolidation-toggle')
                    ->waitForText('ON')
                    ->click('@consolidated-view-toggle')
                    ->waitFor('@consolidated-packages')
                    ->click('@unconsolidate-' . $consolidatedPackage->id)
                    ->waitFor('@unconsolidate-modal')
                    ->assertSee('Unconsolidate Packages')
                    ->assertSee('This will separate the consolidated packages')
                    ->click('@confirm-unconsolidate')
                    ->waitForText('Packages unconsolidated successfully')
                    ->assertSee('Packages unconsolidated successfully');
        });
    }

    /** @test */
    public function consolidation_ui_shows_proper_validation_messages()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/packages')
                    ->waitFor('@consolidation-toggle')
                    ->click('@consolidation-toggle')
                    ->waitForText('ON')
                    ->waitFor('@consolidate-button')
                    ->click('@consolidate-button')
                    ->waitForText('Please select at least 2 packages')
                    ->assertSee('Please select at least 2 packages')
                    ->check('@package-checkbox-1')
                    ->click('@consolidate-button')
                    ->waitForText('At least 2 packages required')
                    ->assertSee('At least 2 packages required');
        });
    }

    /** @test */
    public function consolidation_ui_updates_package_counts_dynamically()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/packages')
                    ->waitFor('@consolidation-toggle')
                    ->click('@consolidation-toggle')
                    ->waitForText('ON')
                    ->waitFor('@package-checkbox-1')
                    ->assertSeeIn('@selected-count', '0 packages selected')
                    ->check('@package-checkbox-1')
                    ->waitForText('1 package selected')
                    ->assertSeeIn('@selected-count', '1 package selected')
                    ->check('@package-checkbox-2')
                    ->waitForText('2 packages selected')
                    ->assertSeeIn('@selected-count', '2 packages selected')
                    ->uncheck('@package-checkbox-1')
                    ->waitForText('1 package selected')
                    ->assertSeeIn('@selected-count', '1 package selected');
        });
    }

    /** @test */
    public function consolidation_ui_shows_package_details_in_modal()
    {
        $packages = Package::where('user_id', $this->customerUser->id)->take(2)->get();

        $this->browse(function (Browser $browser) use ($packages) {
            $browser->loginAs($this->adminUser)
                    ->visit('/packages')
                    ->waitFor('@consolidation-toggle')
                    ->click('@consolidation-toggle')
                    ->waitForText('ON')
                    ->check('@package-checkbox-1')
                    ->check('@package-checkbox-2')
                    ->click('@consolidate-button')
                    ->waitFor('@consolidation-modal')
                    ->assertSee($packages[0]->tracking_number)
                    ->assertSee($packages[1]->tracking_number)
                    ->assertSee($packages[0]->weight . ' lbs')
                    ->assertSee($packages[1]->weight . ' lbs')
                    ->assertSee('Total Weight: ' . ($packages[0]->weight + $packages[1]->weight) . ' lbs')
                    ->assertSee('Total Packages: 2');
        });
    }

    /** @test */
    public function consolidation_ui_handles_search_and_filtering()
    {
        // Create packages with specific tracking numbers
        Package::factory()->create([
            'user_id' => $this->customerUser->id,
            'tracking_number' => 'SEARCH-FILTER-001',
            'status' => PackageStatus::READY
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/packages')
                    ->waitFor('@package-search')
                    ->type('@package-search', 'SEARCH-FILTER')
                    ->waitFor('@search-results')
                    ->assertSee('SEARCH-FILTER-001')
                    ->click('@consolidation-toggle')
                    ->waitForText('ON')
                    ->assertVisible('@package-checkbox-search-filter-001')
                    ->check('@package-checkbox-search-filter-001')
                    ->assertSeeIn('@selected-count', '1 package selected');
        });
    }

    /** @test */
    public function consolidation_ui_shows_consolidated_package_history()
    {
        // Create a consolidated package with history
        $packages = Package::where('user_id', $this->customerUser->id)->take(2)->get();
        $consolidationService = app(\App\Services\PackageConsolidationService::class);
        
        $result = $consolidationService->consolidatePackages(
            $packages->pluck('id')->toArray(),
            $this->adminUser,
            ['notes' => 'Test with history']
        );

        $consolidatedPackage = $result['consolidated_package'];

        // Update status to create history
        $consolidationService->updateConsolidatedStatus(
            $consolidatedPackage,
            PackageStatus::SHIPPED,
            $this->adminUser
        );

        $this->browse(function (Browser $browser) use ($consolidatedPackage) {
            $browser->loginAs($this->adminUser)
                    ->visit('/packages')
                    ->waitFor('@consolidation-toggle')
                    ->click('@consolidation-toggle')
                    ->waitForText('ON')
                    ->click('@consolidated-view-toggle')
                    ->waitFor('@consolidated-packages')
                    ->click('@history-' . $consolidatedPackage->id)
                    ->waitFor('@history-modal')
                    ->assertSee('Consolidation History')
                    ->assertSee('Consolidated')
                    ->assertSee('Status Changed')
                    ->assertSee('SHIPPED')
                    ->assertSee($this->adminUser->first_name);
        });
    }

    /** @test */
    public function customer_cannot_access_consolidation_features()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->customerUser)
                    ->visit('/packages')
                    ->waitFor('@package-list')
                    ->assertDontSee('Consolidation Mode')
                    ->assertMissing('@consolidation-toggle')
                    ->assertMissing('@consolidation-controls');
        });
    }

    /** @test */
    public function consolidation_ui_responsive_design_works()
    {
        $this->browse(function (Browser $browser) {
            // Test desktop view
            $browser->loginAs($this->adminUser)
                    ->visit('/packages')
                    ->resize(1200, 800)
                    ->waitFor('@consolidation-toggle')
                    ->assertVisible('@consolidation-toggle')
                    ->click('@consolidation-toggle')
                    ->waitForText('ON')
                    ->assertVisible('@consolidation-controls');

            // Test tablet view
            $browser->resize(768, 1024)
                    ->assertVisible('@consolidation-toggle')
                    ->assertVisible('@consolidation-controls');

            // Test mobile view
            $browser->resize(375, 667)
                    ->assertVisible('@consolidation-toggle')
                    ->assertVisible('@consolidation-controls');
        });
    }

    /** @test */
    public function consolidation_ui_keyboard_navigation_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/packages')
                    ->waitFor('@consolidation-toggle')
                    ->keys('@consolidation-toggle', ' ') // Space to toggle
                    ->waitForText('ON')
                    ->assertSee('ON')
                    ->keys('@package-checkbox-1', ' ') // Space to check
                    ->keys('@package-checkbox-2', ' ') // Space to check
                    ->waitForText('2 packages selected')
                    ->keys('@consolidate-button', '{enter}') // Enter to click
                    ->waitFor('@consolidation-modal')
                    ->assertSee('Consolidate Packages');
        });
    }

    /** @test */
    public function consolidation_ui_shows_loading_states()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/packages')
                    ->waitFor('@consolidation-toggle')
                    ->click('@consolidation-toggle')
                    ->waitForText('ON')
                    ->check('@package-checkbox-1')
                    ->check('@package-checkbox-2')
                    ->click('@consolidate-button')
                    ->waitFor('@consolidation-modal')
                    ->type('@consolidation-notes', 'Loading test')
                    ->click('@confirm-consolidation')
                    ->assertSee('Processing...') // Loading state
                    ->waitForText('Packages consolidated successfully', 10);
        });
    }
}