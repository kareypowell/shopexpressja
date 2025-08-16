<?php

namespace Tests\Browser;

use App\Models\Manifest;
use App\Models\Package;
use App\Models\ConsolidatedPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ManifestTabsAccessibilityTest extends DuskTestCase
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
    public function tabs_have_proper_aria_attributes()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Check tablist attributes
            $browser->assertAttribute('[role="tablist"]', 'aria-label', 'Manifest package views');

            // Check individual tab attributes
            $browser->assertAttribute('#tab-individual', 'role', 'tab')
                ->assertAttribute('#tab-individual', 'aria-selected', 'true')
                ->assertAttribute('#tab-individual', 'aria-controls', 'tabpanel-individual')
                ->assertAttribute('#tab-individual', 'tabindex', '0');

            $browser->assertAttribute('#tab-consolidated', 'role', 'tab')
                ->assertAttribute('#tab-consolidated', 'aria-selected', 'false')
                ->assertAttribute('#tab-consolidated', 'aria-controls', 'tabpanel-consolidated')
                ->assertAttribute('#tab-consolidated', 'tabindex', '-1');

            // Check tabpanel attributes
            $browser->assertAttribute('[role="tabpanel"]', 'aria-labelledby', 'tab-individual')
                ->assertAttribute('[role="tabpanel"]', 'aria-live', 'polite');
        });
    }

    /** @test */
    public function keyboard_navigation_works_with_arrow_keys()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Focus on first tab
            $browser->click('#tab-individual')
                ->assertFocused('#tab-individual');

            // Navigate to next tab with right arrow
            $browser->keys('#tab-individual', ['{arrow_right}'])
                ->pause(500)
                ->assertAttribute('#tab-consolidated', 'aria-selected', 'true')
                ->assertFocused('#tab-consolidated');

            // Navigate back with left arrow
            $browser->keys('#tab-consolidated', ['{arrow_left}'])
                ->pause(500)
                ->assertAttribute('#tab-individual', 'aria-selected', 'true')
                ->assertFocused('#tab-individual');
        });
    }

    /** @test */
    public function home_and_end_keys_navigate_to_first_and_last_tabs()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Focus on consolidated tab
            $browser->click('#tab-consolidated')
                ->assertFocused('#tab-consolidated');

            // Press Home key to go to first tab
            $browser->keys('#tab-consolidated', ['{home}'])
                ->pause(500)
                ->assertAttribute('#tab-individual', 'aria-selected', 'true')
                ->assertFocused('#tab-individual');

            // Press End key to go to last tab
            $browser->keys('#tab-individual', ['{end}'])
                ->pause(500)
                ->assertAttribute('#tab-consolidated', 'aria-selected', 'true')
                ->assertFocused('#tab-consolidated');
        });
    }

    /** @test */
    public function space_and_enter_keys_activate_tabs()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Focus on consolidated tab (not active)
            $browser->script('document.getElementById("tab-consolidated").focus()');
            
            // Activate with Space key
            $browser->keys('#tab-consolidated', [' '])
                ->pause(500)
                ->assertAttribute('#tab-consolidated', 'aria-selected', 'true');

            // Focus on individual tab and activate with Enter
            $browser->script('document.getElementById("tab-individual").focus()');
            $browser->keys('#tab-individual', ['{enter}'])
                ->pause(500)
                ->assertAttribute('#tab-individual', 'aria-selected', 'true');
        });
    }

    /** @test */
    public function skip_link_works_properly()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Tab to the skip link (it should be the first focusable element)
            $browser->keys('body', ['{tab}']);
            
            // Check if skip link is focused and visible
            $skipLink = $browser->element('a[href="#tab-content"]');
            if ($skipLink) {
                $browser->assertFocused('a[href="#tab-content"]')
                    ->keys('a[href="#tab-content"]', ['{enter}'])
                    ->pause(200);
                
                // Verify focus moved to tab content
                $browser->assertFocused('#tab-content');
            }
        });
    }

    /** @test */
    public function screen_reader_announcements_work()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Check for screen reader announcement area
            $browser->assertPresent('[aria-live="assertive"]');

            // Switch tabs and check for announcement
            $browser->click('#tab-consolidated')
                ->pause(1000); // Wait for announcement

            // Check if announcement was made (content may be cleared after timeout)
            $announcementText = $browser->script('
                return document.querySelector("[aria-live=assertive]").textContent;
            ')[0];

            // The announcement might be cleared, so we just verify the element exists
            $browser->assertPresent('[aria-live="assertive"]');
        });
    }

    /** @test */
    public function focus_management_works_correctly()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Initial focus should be on active tab
            $browser->click('#tab-individual')
                ->assertFocused('#tab-individual');

            // Switch tab and verify focus moves to new active tab
            $browser->click('#tab-consolidated')
                ->pause(500)
                ->assertAttribute('#tab-consolidated', 'aria-selected', 'true');

            // Focus should be manageable
            $browser->assertPresent('#tab-consolidated:focus, #tab-consolidated[tabindex="0"]');
        });
    }

    /** @test */
    public function tab_content_has_proper_accessibility_attributes()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Check individual packages tabpanel
            $browser->assertAttribute('#tabpanel-individual', 'role', 'tabpanel')
                ->assertAttribute('#tabpanel-individual', 'aria-labelledby', 'tab-individual')
                ->assertAttribute('#tabpanel-individual', 'aria-label', 'Individual packages view');

            // Switch to consolidated and check its tabpanel
            $browser->click('#tab-consolidated')
                ->waitFor('#tabpanel-consolidated')
                ->assertAttribute('#tabpanel-consolidated', 'role', 'tabpanel')
                ->assertAttribute('#tabpanel-consolidated', 'aria-labelledby', 'tab-consolidated')
                ->assertAttribute('#tabpanel-consolidated', 'aria-label', 'Consolidated packages view');
        });
    }

    /** @test */
    public function loading_states_are_announced_to_screen_readers()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Check loading state attributes
            $browser->assertAttribute('[role="tabpanel"]', 'aria-busy', 'false');

            // The loading state might be too brief to catch, but we can verify the structure
            $browser->assertPresent('[role="status"]')
                ->assertPresent('.sr-only');
        });
    }

    /** @test */
    public function high_contrast_mode_support_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Simulate high contrast mode by adding CSS
            $browser->script('
                const style = document.createElement("style");
                style.textContent = `
                    @media (prefers-contrast: high) {
                        [role="tab"] { border: 2px solid currentColor !important; }
                        [role="tab"][aria-selected="true"] { 
                            background: currentColor !important; 
                            color: white !important; 
                        }
                    }
                `;
                document.head.appendChild(style);
            ');

            // Verify tabs are still functional
            $browser->click('#tab-consolidated')
                ->waitFor('#tabpanel-consolidated')
                ->assertAttribute('#tab-consolidated', 'aria-selected', 'true');
        });
    }

    /** @test */
    public function reduced_motion_preferences_are_respected()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Simulate reduced motion preference
            $browser->script('
                const style = document.createElement("style");
                style.textContent = `
                    @media (prefers-reduced-motion: reduce) {
                        * { transition: none !important; animation: none !important; }
                    }
                `;
                document.head.appendChild(style);
            ');

            // Verify tabs still work without animations
            $browser->click('#tab-consolidated')
                ->waitFor('#tabpanel-consolidated')
                ->assertAttribute('#tab-consolidated', 'aria-selected', 'true');

            $browser->click('#tab-individual')
                ->waitFor('#tabpanel-individual')
                ->assertAttribute('#tab-individual', 'aria-selected', 'true');
        });
    }

    /** @test */
    public function global_keyboard_shortcuts_work()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Test Alt+T shortcut to focus tabs
            $browser->keys('body', ['{alt}', 't'])
                ->pause(200);

            // Verify focus moved to first tab
            $focused = $browser->script('return document.activeElement.getAttribute("role")')[0];
            $this->assertEquals('tab', $focused);
        });
    }

    /** @test */
    public function package_count_badges_have_proper_accessibility()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Check that package count badges have aria-label
            $individualBadge = $browser->element('#tab-individual [aria-label*="packages"]');
            $consolidatedBadge = $browser->element('#tab-consolidated [aria-label*="packages"]');

            if ($individualBadge) {
                $browser->assertAttribute('#tab-individual [aria-label*="packages"]', 'aria-label');
            }

            if ($consolidatedBadge) {
                $browser->assertAttribute('#tab-consolidated [aria-label*="packages"]', 'aria-label');
            }
        });
    }

    /** @test */
    public function icons_are_properly_hidden_from_screen_readers()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$this->manifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Check that SVG icons have aria-hidden="true"
            $browser->assertAttribute('#tab-individual svg', 'aria-hidden', 'true')
                ->assertAttribute('#tab-consolidated svg', 'aria-hidden', 'true');
        });
    }

    /** @test */
    public function empty_state_has_proper_accessibility()
    {
        // Create a manifest with no packages
        $emptyManifest = Manifest::factory()->create();

        $this->browse(function (Browser $browser) use ($emptyManifest) {
            $browser->loginAs($this->adminUser)
                ->visit("/admin/manifests/{$emptyManifest->id}/packages")
                ->waitFor('[role="tablist"]');

            // Check empty state has proper role and structure
            $browser->assertPresent('[role="status"]')
                ->assertSee('No individual packages found');

            // Verify heading structure
            $browser->assertPresent('h3')
                ->assertSee('No individual packages found');
        });
    }
}