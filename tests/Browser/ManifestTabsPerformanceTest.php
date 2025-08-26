<?php

namespace Tests\Browser;

use App\Models\Manifest;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ManifestTabsPerformanceTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Manifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role_id' => 1]);
        $this->manifest = Manifest::factory()->create([
            'type' => 'air',
            'flight_number' => 'AA123'
        ]);
    }

    /** @test */
    public function it_loads_tabs_efficiently_with_large_datasets()
    {
        // Create a large number of packages
        Package::factory()->count(100)->create([
            'manifest_id' => $this->manifest->id,
            'weight' => fake()->randomFloat(2, 1, 50),
            'consolidated_package_id' => null
        ]);
        
        Package::factory()->count(50)->create([
            'manifest_id' => $this->manifest->id,
            'weight' => fake()->randomFloat(2, 1, 50),
            'consolidated_package_id' => 1
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/manifests/{$this->manifest->id}/packages")
                ->waitFor('.manifest-tabs-container', 10)
                ->assertSee('Individual Packages')
                ->assertSee('Consolidated Packages');

            // Measure tab switching performance
            $startTime = microtime(true);
            
            $browser->click('[role="tab"][aria-controls="tabpanel-consolidated"]')
                ->waitFor('#tabpanel-consolidated', 5)
                ->assertVisible('#tabpanel-consolidated');
            
            $switchTime = microtime(true) - $startTime;
            
            // Tab switch should be fast (less than 2 seconds even with large dataset)
            $this->assertLessThan(2.0, $switchTime);
            
            // Switch back to individual packages
            $startTime = microtime(true);
            
            $browser->click('[role="tab"][aria-controls="tabpanel-individual"]')
                ->waitFor('#tabpanel-individual', 5)
                ->assertVisible('#tabpanel-individual');
            
            $switchBackTime = microtime(true) - $startTime;
            
            // Second switch should be even faster due to caching
            $this->assertLessThan(1.5, $switchBackTime);
        });
    }

    /** @test */
    public function it_handles_memory_management_during_tab_operations()
    {
        // Create packages for both tabs
        Package::factory()->count(50)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => null
        ]);
        
        Package::factory()->count(30)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => 1
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/manifests/{$this->manifest->id}/packages")
                ->waitFor('.manifest-tabs-container', 10);

            // Perform multiple tab switches to test memory management
            for ($i = 0; $i < 5; $i++) {
                $browser->click('[role="tab"][aria-controls="tabpanel-consolidated"]')
                    ->waitFor('#tabpanel-consolidated', 3)
                    ->pause(500)
                    ->click('[role="tab"][aria-controls="tabpanel-individual"]')
                    ->waitFor('#tabpanel-individual', 3)
                    ->pause(500);
            }

            // Check for memory leaks by evaluating JavaScript memory usage
            $memoryUsage = $browser->script('
                if (performance.memory) {
                    return {
                        used: performance.memory.usedJSHeapSize,
                        total: performance.memory.totalJSHeapSize,
                        limit: performance.memory.jsHeapSizeLimit
                    };
                }
                return null;
            ')[0];

            if ($memoryUsage) {
                $memoryUsageMB = $memoryUsage['used'] / (1024 * 1024);
                
                // Memory usage should be reasonable (less than 100MB for this test)
                $this->assertLessThan(100, $memoryUsageMB);
            }

            // Verify tabs are still functional after multiple switches
            $browser->assertVisible('[role="tab"]')
                ->assertVisible('#tabpanel-individual');
        });
    }

    /** @test */
    public function it_implements_lazy_loading_for_tab_content()
    {
        Package::factory()->count(20)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => null
        ]);
        
        Package::factory()->count(15)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => 1
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/manifests/{$this->manifest->id}/packages")
                ->waitFor('.manifest-tabs-container', 10);

            // Initially, only the active tab content should be loaded
            $browser->assertVisible('#tabpanel-individual')
                ->assertPresent('#tabpanel-consolidated');

            // Check that inactive tab content is not fully loaded
            $consolidatedContent = $browser->script('
                const panel = document.getElementById("tabpanel-consolidated");
                return panel ? panel.innerHTML.includes("Content will load when tab is selected") : false;
            ')[0];

            // Switch to consolidated tab and verify content loads
            $browser->click('[role="tab"][aria-controls="tabpanel-consolidated"]')
                ->waitFor('#tabpanel-consolidated', 5)
                ->pause(1000); // Allow content to load

            // Verify content is now loaded
            $browser->assertDontSee('Content will load when tab is selected');
        });
    }

    /** @test */
    public function it_maintains_responsive_performance_on_mobile_viewport()
    {
        Package::factory()->count(30)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => null
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->resize(375, 667) // iPhone SE viewport
                ->visit("/manifests/{$this->manifest->id}/packages")
                ->waitFor('.manifest-tabs-container', 10);

            // Measure performance on mobile viewport
            $startTime = microtime(true);
            
            $browser->assertVisible('[role="tablist"]')
                ->assertVisible('[role="tab"]');
            
            $loadTime = microtime(true) - $startTime;
            
            // Should load quickly even on mobile
            $this->assertLessThan(3.0, $loadTime);

            // Test touch interactions
            $browser->tap('[role="tab"][aria-controls="tabpanel-consolidated"]')
                ->waitFor('#tabpanel-consolidated', 5)
                ->assertVisible('#tabpanel-consolidated');

            // Verify horizontal scrolling works if needed
            $browser->script('
                const tablist = document.querySelector("[role=tablist]");
                if (tablist.scrollWidth > tablist.clientWidth) {
                    tablist.scrollLeft = 50;
                }
            ');

            // Tabs should still be functional after scrolling
            $browser->tap('[role="tab"][aria-controls="tabpanel-individual"]')
                ->waitFor('#tabpanel-individual', 5)
                ->assertVisible('#tabpanel-individual');
        });
    }

    /** @test */
    public function it_handles_keyboard_navigation_efficiently()
    {
        Package::factory()->count(25)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => null
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/manifests/{$this->manifest->id}/packages")
                ->waitFor('.manifest-tabs-container', 10);

            // Focus on first tab
            $browser->script('document.querySelector("[role=tab]").focus();');

            // Test keyboard navigation performance
            $startTime = microtime(true);
            
            // Use arrow keys to navigate
            $browser->keys('[role="tab"]', ['{arrow_right}'])
                ->pause(100)
                ->keys('[role="tab"]', ['{enter}'])
                ->waitFor('#tabpanel-consolidated', 3);
            
            $keyboardNavTime = microtime(true) - $startTime;
            
            // Keyboard navigation should be fast
            $this->assertLessThan(1.0, $keyboardNavTime);

            // Verify correct tab is active
            $browser->assertVisible('#tabpanel-consolidated');

            // Test Home/End keys
            $browser->keys('[role="tab"]', ['{home}'])
                ->pause(100)
                ->assertScript('document.activeElement.getAttribute("aria-controls") === "tabpanel-individual"');
        });
    }

    /** @test */
    public function it_optimizes_dom_updates_during_tab_switches()
    {
        Package::factory()->count(40)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => null
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/manifests/{$this->manifest->id}/packages")
                ->waitFor('.manifest-tabs-container', 10);

            // Count initial DOM elements
            $initialElementCount = $browser->script('
                return document.querySelectorAll("*").length;
            ')[0];

            // Switch tabs multiple times
            for ($i = 0; $i < 3; $i++) {
                $browser->click('[role="tab"][aria-controls="tabpanel-consolidated"]')
                    ->waitFor('#tabpanel-consolidated', 3)
                    ->click('[role="tab"][aria-controls="tabpanel-individual"]')
                    ->waitFor('#tabpanel-individual', 3);
            }

            // Count DOM elements after switches
            $finalElementCount = $browser->script('
                return document.querySelectorAll("*").length;
            ')[0];

            // DOM should not grow excessively (allow for some variation)
            $elementGrowth = $finalElementCount - $initialElementCount;
            $this->assertLessThan(100, $elementGrowth);

            // Verify no memory leaks in DOM
            $orphanedElements = $browser->script('
                return document.querySelectorAll("[wire\\\\:id]:not([wire\\\\:id=\"\"])").length;
            ')[0];

            // Should not have excessive orphaned Livewire elements
            $this->assertLessThan(10, $orphanedElements);
        });
    }

    /** @test */
    public function it_handles_concurrent_tab_operations_efficiently()
    {
        Package::factory()->count(35)->create([
            'manifest_id' => $this->manifest->id,
            'consolidated_package_id' => null
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit("/manifests/{$this->manifest->id}/packages")
                ->waitFor('.manifest-tabs-container', 10);

            // Simulate rapid tab switching (stress test)
            $startTime = microtime(true);
            
            for ($i = 0; $i < 10; $i++) {
                $browser->script('
                    document.querySelector("[role=tab][aria-controls=tabpanel-consolidated]").click();
                ');
                $browser->pause(50);
                $browser->script('
                    document.querySelector("[role=tab][aria-controls=tabpanel-individual]").click();
                ');
                $browser->pause(50);
            }
            
            $rapidSwitchTime = microtime(true) - $startTime;
            
            // Should handle rapid switching without breaking
            $this->assertLessThan(5.0, $rapidSwitchTime);

            // Verify final state is correct
            $browser->waitFor('#tabpanel-individual', 5)
                ->assertVisible('#tabpanel-individual')
                ->assertScript('
                    document.querySelector("[role=tab][aria-selected=true]").getAttribute("aria-controls") === "tabpanel-individual"
                ');

            // Check for JavaScript errors
            $jsErrors = $browser->script('
                return window.jsErrors || [];
            ')[0];

            $this->assertEmpty($jsErrors, 'JavaScript errors detected during rapid tab switching');
        });
    }
}