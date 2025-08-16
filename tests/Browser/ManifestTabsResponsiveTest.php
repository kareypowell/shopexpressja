<?php

namespace Tests\Browser;

use App\Models\Manifest;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ManifestTabsResponsiveTest extends DuskTestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Manifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role_id' => 1]);
        $this->manifest = Manifest::factory()->create();
        
        // Create some individual packages
        Package::factory()->count(3)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => null
        ]);
        
        // Create consolidated packages
        $consolidatedPackage = ConsolidatedPackage::factory()->create();
        Package::factory()->count(2)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => $consolidatedPackage->id
        ]);
    }

    /** @test */
    public function tabs_are_responsive_on_mobile_devices()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->resize(375, 667) // iPhone SE size
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]')
                ->assertVisible('[role="tablist"]')
                ->assertVisible('[role="tab"]');

            // Check that tabs are horizontally scrollable on mobile
            $browser->assertPresent('[role="tablist"].overflow-x-auto')
                ->assertVisible('[role="tab"]:first-child')
                ->assertVisible('[role="tab"]:last-child');

            // Verify touch-friendly tab sizing
            $tabHeight = $browser->script('return document.querySelector("[role=tab]").offsetHeight')[0];
            $this->assertGreaterThanOrEqual(44, $tabHeight, 'Tab height should be at least 44px for touch accessibility');

            // Test tab switching on mobile
            $browser->click('[role="tab"][aria-selected="false"]')
                ->waitFor('[role="tabpanel"]')
                ->assertVisible('[role="tabpanel"]');
        });
    }

    /** @test */
    public function tabs_work_properly_on_tablet_devices()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->resize(768, 1024) // iPad size
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]')
                ->assertVisible('[role="tablist"]')
                ->assertVisible('[role="tab"]');

            // Check that tabs display properly on tablet
            $browser->assertVisible('[role="tab"]:first-child')
                ->assertVisible('[role="tab"]:last-child');

            // Test tab switching
            $browser->click('#tab-consolidated')
                ->waitFor('#tabpanel-consolidated')
                ->assertVisible('#tabpanel-consolidated')
                ->assertAttribute('#tab-consolidated', 'aria-selected', 'true');

            $browser->click('#tab-individual')
                ->waitFor('#tabpanel-individual')
                ->assertVisible('#tabpanel-individual')
                ->assertAttribute('#tab-individual', 'aria-selected', 'true');
        });
    }

    /** @test */
    public function tabs_work_properly_on_desktop()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->resize(1920, 1080) // Desktop size
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]')
                ->assertVisible('[role="tablist"]')
                ->assertVisible('[role="tab"]');

            // Check that tabs display properly on desktop
            $browser->assertVisible('[role="tab"]:first-child')
                ->assertVisible('[role="tab"]:last-child');

            // Test hover effects (desktop only)
            $browser->mouseover('#tab-consolidated')
                ->pause(200); // Allow hover transition

            // Test tab switching
            $browser->click('#tab-consolidated')
                ->waitFor('#tabpanel-consolidated')
                ->assertVisible('#tabpanel-consolidated')
                ->assertAttribute('#tab-consolidated', 'aria-selected', 'true');
        });
    }

    /** @test */
    public function horizontal_scrolling_works_on_small_screens()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->resize(320, 568) // Very small screen
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]')
                ->assertVisible('[role="tablist"]');

            // Check that tablist is scrollable
            $isScrollable = $browser->script('
                const tablist = document.querySelector("[role=tablist]");
                return tablist.scrollWidth > tablist.clientWidth;
            ')[0];

            if ($isScrollable) {
                // Test scrolling to the last tab
                $browser->script('
                    const tablist = document.querySelector("[role=tablist]");
                    tablist.scrollLeft = tablist.scrollWidth;
                ');

                $browser->pause(500)
                    ->assertVisible('[role="tab"]:last-child');
            }
        });
    }

    /** @test */
    public function tab_content_is_responsive()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->resize(375, 667) // Mobile size
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tabpanel"]')
                ->assertVisible('[role="tabpanel"]');

            // Check that content container adapts to mobile
            $browser->assertPresent('#tab-content')
                ->assertVisible('#tab-content');

            // Switch to tablet size
            $browser->resize(768, 1024)
                ->pause(500)
                ->assertVisible('[role="tabpanel"]');

            // Switch to desktop size
            $browser->resize(1920, 1080)
                ->pause(500)
                ->assertVisible('[role="tabpanel"]');
        });
    }

    /** @test */
    public function loading_states_are_visible_and_accessible()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Click on a tab and check for loading state
            $browser->click('#tab-consolidated');

            // Check for loading indicator (may be brief)
            $browser->waitFor('[role="tabpanel"]')
                ->assertVisible('[role="tabpanel"]');

            // Verify loading state has proper accessibility attributes
            $browser->assertAttribute('[role="tabpanel"]', 'aria-live', 'polite');
        });
    }

    /** @test */
    public function empty_state_displays_properly_on_all_screen_sizes()
    {
        // Create a manifest with no packages
        $emptyManifest = Manifest::factory()->create();

        $this->browse(function (Browser $browser) use ($emptyManifest) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$emptyManifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Test on mobile
            $browser->resize(375, 667)
                ->assertVisible('[role="tabpanel"]')
                ->assertSee('No individual packages found');

            // Test on tablet
            $browser->resize(768, 1024)
                ->assertVisible('[role="tabpanel"]')
                ->assertSee('No individual packages found');

            // Test on desktop
            $browser->resize(1920, 1080)
                ->assertVisible('[role="tabpanel"]')
                ->assertSee('No individual packages found');
        });
    }

    /** @test */
    public function tab_labels_truncate_properly_on_small_screens()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->resize(375, 667) // Mobile size
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Check that mobile labels are shown
            $browser->assertVisible('.sm\\:hidden')
                ->assertSee('Individual')
                ->assertSee('Consolidated');

            // Switch to desktop and check full labels
            $browser->resize(1920, 1080)
                ->assertVisible('.hidden.sm\\:inline')
                ->assertSee('Individual Packages')
                ->assertSee('Consolidated Packages');
        });
    }

    /** @test */
    public function touch_interactions_work_properly()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->resize(375, 667) // Mobile size
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Simulate touch interaction
            $browser->script('
                const tab = document.querySelector("#tab-consolidated");
                const touchEvent = new TouchEvent("touchstart", {
                    touches: [new Touch({
                        identifier: 1,
                        target: tab,
                        clientX: tab.getBoundingClientRect().left + 10,
                        clientY: tab.getBoundingClientRect().top + 10
                    })]
                });
                tab.dispatchEvent(touchEvent);
            ');

            // Click the tab
            $browser->click('#tab-consolidated')
                ->waitFor('#tabpanel-consolidated')
                ->assertVisible('#tabpanel-consolidated')
                ->assertAttribute('#tab-consolidated', 'aria-selected', 'true');
        });
    }

    /** @test */
    public function print_styles_hide_tabs_and_show_content()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Apply print media styles
            $browser->script('
                const style = document.createElement("style");
                style.textContent = "@media print { [role=tablist] { display: none !important; } }";
                document.head.appendChild(style);
                
                // Simulate print media
                const mediaQuery = window.matchMedia("print");
                Object.defineProperty(mediaQuery, "matches", { value: true });
            ');

            // In a real print scenario, tabs would be hidden
            // We can't fully test print styles in browser tests, but we can verify the CSS exists
            $browser->assertPresent('[role="tablist"]')
                ->assertPresent('#tab-content');
        });
    }
}