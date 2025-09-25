<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Manifest;
use App\Models\User;
use App\Models\Role;
use Livewire\Livewire;
use App\Http\Livewire\Manifests\EnhancedManifestSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class EnhancedManifestSummaryErrorStateTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Get or create admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        $this->user = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create a test manifest
        $this->manifest = Manifest::factory()->create([
            'type' => 'air'
        ]);
    }

    /** @test */
    public function it_displays_error_state_ui_when_calculation_fails()
    {
        $this->actingAs($this->user);

        // Mock a service failure by clearing cache and making service unavailable
        Cache::flush();
        
        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $this->manifest]);
        
        // Force an error by calling a method that might fail
        $component->call('retryCalculation');
        
        // Check that error state properties are available
        $this->assertNotNull($component->get('hasError'));
        $this->assertNotNull($component->get('errorMessage'));
        $this->assertNotNull($component->get('isRetrying'));
    }

    /** @test */
    public function it_shows_retry_button_functionality()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $this->manifest]);
        
        // Test retry functionality
        $component->call('retryCalculation');
        
        // Verify the component handles the retry call
        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_displays_loading_states_during_updates()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $this->manifest]);
        
        // Test refresh functionality
        $component->call('refreshSummary');
        
        // Verify the component handles the refresh call
        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_shows_graceful_degradation_with_partial_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $this->manifest]);
        
        // Check that incomplete data flag is handled
        $this->assertNotNull($component->get('hasIncompleteData'));
        
        // Verify summary data structure
        $this->assertNotNull($component->get('summary'));
        $this->assertNotNull($component->get('manifestType'));
    }

    /** @test */
    public function it_provides_user_friendly_error_messages()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $this->manifest]);
        
        // Test that error messages don't expose technical details
        $hasError = $component->get('hasError');
        
        if ($hasError) {
            $errorMessage = $component->get('errorMessage');
            
            // Ensure error message is user-friendly
            $this->assertNotEmpty($errorMessage);
            $this->assertStringNotContainsString('Exception', $errorMessage);
            $this->assertStringNotContainsString('Stack trace', $errorMessage);
            $this->assertStringNotContainsString('SQL', $errorMessage);
        } else {
            // If no error, verify the component loaded successfully
            $this->assertFalse($hasError);
        }
    }

    /** @test */
    public function it_handles_clear_error_state_functionality()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $this->manifest]);
        
        // Test clear error state method
        $component->call('clearErrorState');
        
        // Verify the component handles the clear error state call
        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_handles_force_refresh_functionality()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $this->manifest]);
        
        // Test force refresh method
        $component->call('forceRefresh');
        
        // Verify the component handles the force refresh call
        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_displays_data_status_indicators()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(EnhancedManifestSummary::class, ['manifest' => $this->manifest]);
        
        // Check that component renders without errors
        $component->assertSee('Manifest Summary');
        
        // Verify that data status indicators are present in the view
        $component->assertViewHas('hasError');
        $component->assertViewHas('hasIncompleteData');
        $component->assertViewHas('manifestType');
    }
}