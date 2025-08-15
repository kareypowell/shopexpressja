<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Livewire\Livewire;
use App\Http\Livewire\ConsolidationToggle;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsolidationToggleTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin role and user
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
    }

    /** @test */
    public function it_can_render_consolidation_toggle_component()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ConsolidationToggle::class)
            ->assertStatus(200)
            ->assertSee('Package Consolidation')
            ->assertSee('OFF')
            ->assertViewIs('livewire.consolidation-toggle');
    }

    /** @test */
    public function it_initializes_with_consolidation_mode_disabled_by_default()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ConsolidationToggle::class)
            ->assertSet('consolidationMode', false)
            ->assertSee('OFF')
            ->assertSee('View packages individually');
    }

    /** @test */
    public function it_can_toggle_consolidation_mode_on()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ConsolidationToggle::class)
            ->assertSet('consolidationMode', false)
            ->call('toggleConsolidationMode')
            ->assertSet('consolidationMode', true)
            ->assertSee('ON')
            ->assertSee('Group multiple packages together')
            ->assertEmitted('consolidationModeChanged', true);
    }

    /** @test */
    public function it_can_toggle_consolidation_mode_off()
    {
        $this->actingAs($this->adminUser);

        // Start with consolidation mode enabled
        session(['consolidation_mode' => true]);

        Livewire::test(ConsolidationToggle::class)
            ->assertSet('consolidationMode', true)
            ->call('toggleConsolidationMode')
            ->assertSet('consolidationMode', false)
            ->assertSee('OFF')
            ->assertSee('View packages individually')
            ->assertEmitted('consolidationModeChanged', false);
    }

    /** @test */
    public function it_persists_consolidation_mode_in_session()
    {
        $this->actingAs($this->adminUser);

        // Toggle consolidation mode on
        Livewire::test(ConsolidationToggle::class)
            ->call('toggleConsolidationMode')
            ->assertSet('consolidationMode', true);

        // Verify session was updated
        $this->assertTrue(session('consolidation_mode'));

        // Create new component instance to test persistence
        Livewire::test(ConsolidationToggle::class)
            ->assertSet('consolidationMode', true);
    }

    /** @test */
    public function it_initializes_from_existing_session_state()
    {
        $this->actingAs($this->adminUser);

        // Set session state before component initialization
        session(['consolidation_mode' => true]);

        Livewire::test(ConsolidationToggle::class)
            ->assertSet('consolidationMode', true)
            ->assertSee('ON');
    }

    /** @test */
    public function it_shows_flash_message_when_toggling_mode()
    {
        $this->actingAs($this->adminUser);

        // Test enabling consolidation mode
        Livewire::test(ConsolidationToggle::class)
            ->call('toggleConsolidationMode')
            ->assertDispatchedBrowserEvent('show-message', ['message' => 'Consolidation mode enabled']);

        // Test disabling consolidation mode
        Livewire::test(ConsolidationToggle::class)
            ->call('toggleConsolidationMode')
            ->assertDispatchedBrowserEvent('show-message', ['message' => 'Consolidation mode disabled']);
    }

    /** @test */
    public function it_emits_consolidation_mode_changed_event_with_correct_payload()
    {
        $this->actingAs($this->adminUser);

        // Test enabling
        Livewire::test(ConsolidationToggle::class)
            ->call('toggleConsolidationMode')
            ->assertEmitted('consolidationModeChanged', true);

        // Test disabling - start with enabled state
        session(['consolidation_mode' => true]);
        Livewire::test(ConsolidationToggle::class)
            ->call('toggleConsolidationMode')
            ->assertEmitted('consolidationModeChanged', false);
    }

    /** @test */
    public function it_displays_correct_status_indicator()
    {
        $this->actingAs($this->adminUser);

        // Test OFF state
        Livewire::test(ConsolidationToggle::class)
            ->assertSee('OFF')
            ->assertSeeHtml('bg-gray-300') // Gray indicator dot
            ->assertSeeHtml('bg-gray-200'); // Gray toggle background

        // Test ON state
        Livewire::test(ConsolidationToggle::class)
            ->call('toggleConsolidationMode')
            ->assertSee('ON')
            ->assertSeeHtml('bg-green-500') // Green indicator dot
            ->assertSeeHtml('bg-blue-600'); // Blue toggle background
    }

    /** @test */
    public function it_shows_consolidation_mode_description_when_active()
    {
        $this->actingAs($this->adminUser);

        // Initially should not show consolidation description
        Livewire::test(ConsolidationToggle::class)
            ->assertDontSee('Consolidation mode is active');

        // After enabling, should show description
        Livewire::test(ConsolidationToggle::class)
            ->call('toggleConsolidationMode')
            ->assertSee('Consolidation mode is active')
            ->assertSee('You can now select multiple packages');
    }

    /** @test */
    public function it_has_proper_accessibility_attributes()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ConsolidationToggle::class)
            ->assertSeeHtml('role="switch"')
            ->assertSeeHtml('aria-checked="false"')
            ->assertSeeHtml('aria-labelledby="consolidation-toggle-label"');

        // Test after toggling
        Livewire::test(ConsolidationToggle::class)
            ->call('toggleConsolidationMode')
            ->assertSeeHtml('aria-checked="true"');
    }

    /** @test */
    public function it_maintains_state_across_multiple_toggles()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(ConsolidationToggle::class);

        // Multiple toggles
        $component->call('toggleConsolidationMode') // ON
            ->assertSet('consolidationMode', true)
            ->call('toggleConsolidationMode') // OFF
            ->assertSet('consolidationMode', false)
            ->call('toggleConsolidationMode') // ON
            ->assertSet('consolidationMode', true);

        // Verify final session state
        $this->assertTrue(session('consolidation_mode'));
    }

    /** @test */
    public function it_handles_session_cleanup_properly()
    {
        $this->actingAs($this->adminUser);

        // Enable consolidation mode
        Livewire::test(ConsolidationToggle::class)
            ->call('toggleConsolidationMode');

        $this->assertTrue(session('consolidation_mode'));

        // Disable consolidation mode
        Livewire::test(ConsolidationToggle::class)
            ->call('toggleConsolidationMode');

        $this->assertFalse(session('consolidation_mode'));
    }
}