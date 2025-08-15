<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use App\Models\User;
use App\Models\Role;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ConsolidationToggleBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin role and user
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
    }

    /** @test */
    public function user_can_toggle_consolidation_mode_with_visual_feedback()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/test-consolidation-toggle') // We'll need to create this route
                ->assertSee('Package Consolidation')
                ->assertSee('OFF')
                ->click('.consolidation-toggle')
                ->waitForText('ON')
                ->assertSee('Consolidation mode is active')
                ->click('.consolidation-toggle')
                ->waitForText('OFF')
                ->assertDontSee('Consolidation mode is active');
        });
    }

    /** @test */
    public function toggle_shows_message_feedback()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                ->visit('/test-consolidation-toggle')
                ->click('.consolidation-toggle')
                ->waitFor('#consolidation-message:not(.hidden)')
                ->assertSeeIn('#consolidation-message-text', 'Consolidation mode enabled')
                ->pause(3500) // Wait for auto-hide
                ->assertPresent('#consolidation-message.hidden');
        });
    }
}