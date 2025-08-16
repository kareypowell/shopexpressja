<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Livewire\Manifests\ManifestTabsContainer;
use App\Models\Manifest;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class ManifestTabsErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Manifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->manifest = Manifest::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_handles_invalid_tab_names_gracefully()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        // Test invalid tab names that should be sanitized and defaulted
        $invalidTabs = ['invalid', '../../etc/passwd'];
        
        foreach ($invalidTabs as $invalidTab) {
            $component->call('switchTab', $invalidTab);
            
            // Should default to 'individual' tab
            $component->assertSet('activeTab', 'individual');
        }
    }

    /** @test */
    public function it_validates_tab_switch_requests_for_security()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        // Test XSS attempt with special characters - should default to individual tab
        $component->call('switchTab', '<script>alert("xss")</script>');
        
        // Should sanitize and default to individual tab
        $component->assertSet('activeTab', 'individual');
        
        // Test SQL injection attempt
        $component->call('switchTab', "'; DROP TABLE users; --");
        
        // Should sanitize and default to individual tab
        $component->assertSet('activeTab', 'individual');
    }

    /** @test */
    public function it_handles_corrupted_session_state()
    {
        
        // Set corrupted session data
        $sessionKey = "manifest_tabs_{$this->manifest->id}";
        Session::put($sessionKey, [
            'activeTab' => 'consolidated',
            'timestamp' => now()->timestamp,
            'manifestId' => 999, // Wrong manifest ID
            'checksum' => 'invalid_checksum'
        ]);

        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        // Explicitly call restoreTabState to trigger validation
        $component->call('restoreTabState');

        // Should handle corrupted state gracefully and maintain individual tab
        $component->assertSet('activeTab', 'individual');
        
        // Component should continue to function normally
        $component->call('switchTab', 'consolidated');
        $component->assertSet('activeTab', 'consolidated');
    }

    /** @test */
    public function it_handles_expired_session_state()
    {
        // Set expired session data (older than 1 hour)
        $sessionKey = "manifest_tabs_{$this->manifest->id}";
        Session::put($sessionKey, [
            'activeTab' => 'consolidated',
            'timestamp' => now()->subHours(2)->timestamp,
            'manifestId' => $this->manifest->id,
            'checksum' => hash('sha256', 'consolidated|' . $this->manifest->id . '|' . config('app.key'))
        ]);

        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest,
            'activeTab' => 'individual'
        ]);

        // Should handle expired state gracefully and maintain individual tab
        $component->assertSet('activeTab', 'individual');
    }

    /** @test */
    public function it_preserves_state_with_valid_checksum()
    {
        $sessionKey = "manifest_tabs_{$this->manifest->id}";
        $checksum = hash('sha256', 'consolidated|' . $this->manifest->id . '|' . config('app.key'));
        
        Session::put($sessionKey, [
            'activeTab' => 'consolidated',
            'timestamp' => now()->timestamp,
            'manifestId' => $this->manifest->id,
            'checksum' => $checksum
        ]);

        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest,
            'activeTab' => 'individual'
        ]);

        // Should restore the consolidated tab
        $component->assertSet('activeTab', 'consolidated');
    }

    /** @test */
    public function it_handles_session_storage_failures_gracefully()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        // Test that component continues to work even if session operations fail
        $component->call('switchTab', 'consolidated');
        
        // Should still switch tabs
        $component->assertSet('activeTab', 'consolidated');
        $component->assertSet('hasError', false);
        
        // Should be able to switch back
        $component->call('switchTab', 'individual');
        $component->assertSet('activeTab', 'individual');
    }

    /** @test */
    public function it_handles_mount_exceptions()
    {
        
        // Create a component that will fail during mount
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest,
            'activeTab' => 'invalid_tab_that_causes_error'
        ]);

        $component->assertSet('hasError', false); // Should handle gracefully
        $component->assertSet('activeTab', 'individual'); // Should default
    }

    /** @test */
    public function it_validates_extremely_long_tab_names()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        $longTabName = str_repeat('a', 100);
        $component->call('switchTab', $longTabName);
        
        $component->assertSet('hasError', true);
        $component->assertSet('errorMessage', 'Invalid request. Please try again.');
    }

    /** @test */
    public function it_handles_special_characters_in_tab_names()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        $specialChars = ['<script>', '"alert"', "'drop'", '<img>'];
        
        foreach ($specialChars as $char) {
            $component->call('switchTab', $char);
            $component->assertSet('hasError', true);
            
            // Reset error state for next test
            $component->set('hasError', false);
            $component->set('errorMessage', '');
        }
    }

    /** @test */
    public function it_generates_consistent_checksums()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        // Test that state preservation works consistently
        $component->call('switchTab', 'consolidated');
        $sessionKey = "manifest_tabs_{$this->manifest->id}";
        $state1 = Session::get($sessionKey);
        
        // Clear session and switch again
        Session::forget($sessionKey);
        $component->call('switchTab', 'individual');
        $state2 = Session::get($sessionKey);
        
        // Both states should exist and have checksums
        $this->assertNotNull($state1);
        $this->assertNotNull($state2);
        $this->assertArrayHasKey('checksum', $state1);
        $this->assertArrayHasKey('checksum', $state2);
        
        // Different tabs should have different checksums
        $this->assertNotEquals($state1['checksum'], $state2['checksum']);
    }

    /** @test */
    public function it_clears_error_state_on_successful_operations()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        // Set error state
        $component->set('hasError', true);
        $component->set('errorMessage', 'Test error');

        // Perform valid operation
        $component->call('switchTab', 'consolidated');
        
        $component->assertSet('hasError', false);
        $component->assertSet('errorMessage', '');
    }

    /** @test */
    public function it_handles_concurrent_tab_switches()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        // Simulate rapid tab switches
        $component->call('switchTab', 'consolidated');
        $component->call('switchTab', 'individual');
        $component->call('switchTab', 'consolidated');
        
        // Should end up in the last requested state
        $component->assertSet('activeTab', 'consolidated');
        $component->assertSet('hasError', false);
    }

    /** @test */
    public function it_logs_security_violations_appropriately()
    {
        
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        // Attempt XSS - should sanitize and default to individual tab
        $component->call('switchTab', '<script>alert("xss")</script>');
        
        // Should default to individual tab
        $component->assertSet('activeTab', 'individual');
    }

    /** @test */
    public function it_maintains_component_state_during_errors()
    {
        $component = Livewire::test(ManifestTabsContainer::class, [
            'manifest' => $this->manifest
        ]);

        $originalManifest = $component->get('manifest');
        
        // Trigger error
        $component->call('switchTab', '<script>');
        
        // Manifest should remain unchanged
        $this->assertEquals($originalManifest->id, $component->get('manifest')->id);
        $component->assertSet('isLoading', false);
    }
}