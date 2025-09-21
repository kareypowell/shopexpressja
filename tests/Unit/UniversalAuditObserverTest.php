<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\AuditLog;
use App\Observers\UniversalAuditObserver;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class UniversalAuditObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure audit configuration is loaded
        Config::set('audit.auditable_models', [
            'App\Models\User',
            'App\Models\Package',
        ]);
        
        Config::set('audit.excluded_fields', [
            'password',
            'remember_token',
            'api_token',
        ]);
    }

    /** @test */
    public function it_can_determine_if_model_should_be_audited()
    {
        $observer = app(UniversalAuditObserver::class);
        $user = new User();
        
        $this->assertTrue($observer->shouldAudit($user));
    }

    /** @test */
    public function it_respects_model_audit_disabled_property()
    {
        $observer = app(UniversalAuditObserver::class);
        $user = new User();
        $user->auditingDisabled = true;
        
        $this->assertFalse($observer->shouldAudit($user));
    }

    /** @test */
    public function it_respects_model_should_audit_method()
    {
        $observer = app(UniversalAuditObserver::class);
        
        // Create a mock model that implements shouldAudit method
        $model = new class extends User {
            public function shouldAudit(): bool
            {
                return false;
            }
        };
        
        $this->assertFalse($observer->shouldAudit($model));
    }

    /** @test */
    public function it_gets_auditable_models_from_config()
    {
        $observer = app(UniversalAuditObserver::class);
        $auditableModels = $observer->getAuditableModels();
        
        $this->assertContains('App\Models\User', $auditableModels);
        $this->assertContains('App\Models\Package', $auditableModels);
    }

    /** @test */
    public function it_gets_excluded_fields_from_config()
    {
        $observer = app(UniversalAuditObserver::class);
        $excludedFields = $observer->getExcludedFields();
        
        $this->assertContains('password', $excludedFields);
        $this->assertContains('remember_token', $excludedFields);
        $this->assertContains('api_token', $excludedFields);
    }

    /** @test */
    public function it_identifies_critical_models()
    {
        Config::set('audit.critical_models', ['App\Models\User']);
        
        $observer = app(UniversalAuditObserver::class);
        $user = new User();
        
        $this->assertTrue($observer->isCriticalModel($user));
    }

    /** @test */
    public function it_identifies_high_priority_models()
    {
        Config::set('audit.model_configs', [
            'App\Models\User' => [
                'high_priority' => true
            ]
        ]);
        
        $observer = app(UniversalAuditObserver::class);
        $observer->refreshConfig(); // Refresh config after setting
        $user = new User();
        
        $this->assertTrue($observer->isHighPriorityModel($user));
    }

    /** @test */
    public function it_filters_sensitive_fields_from_attributes()
    {
        $observer = app(UniversalAuditObserver::class);
        $user = new User([
            'password' => 'secret',
            'remember_token' => 'token123',
            'first_name' => 'John',
        ]);
        
        $filtered = $observer->getFilteredAttributes($user);
        
        $this->assertArrayNotHasKey('password', $filtered);
        $this->assertArrayNotHasKey('remember_token', $filtered);
        $this->assertArrayHasKey('first_name', $filtered);
        $this->assertEquals('John', $filtered['first_name']);
    }

    /** @test */
    public function it_gets_model_specific_excluded_fields()
    {
        Config::set('audit.model_configs', [
            'App\Models\User' => [
                'excluded_fields' => ['custom_field']
            ]
        ]);
        
        $observer = app(UniversalAuditObserver::class);
        $observer->refreshConfig(); // Refresh config after setting
        $user = new User();
        
        $excludedFields = $observer->getExcludedFieldsForModel($user);
        
        $this->assertContains('password', $excludedFields); // Global excluded field
        $this->assertContains('custom_field', $excludedFields); // Model-specific excluded field
    }

    /** @test */
    public function it_gets_original_values_for_changed_fields_only()
    {
        $user = new User();
        $user->fill([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        
        // Simulate the model being "saved" so we have original values
        $user->syncOriginal();

        // Simulate an update by setting new values
        $user->setAttribute('first_name', 'Jane');
        $user->setAttribute('password', 'new_password'); // This should be excluded

        $observer = app(UniversalAuditObserver::class);
        
        $originalValues = $observer->getOriginalValues($user);
        
        $this->assertArrayHasKey('first_name', $originalValues);
        $this->assertEquals('John', $originalValues['first_name']);
        $this->assertArrayNotHasKey('password', $originalValues); // Should be excluded
        $this->assertArrayNotHasKey('last_name', $originalValues); // Didn't change
    }

    /** @test */
    public function it_checks_async_logging_configuration()
    {
        Config::set('audit.observer.async_logging', true);
        
        $observer = app(UniversalAuditObserver::class);
        
        $this->assertTrue($observer->shouldUseAsyncLogging());
    }

    /** @test */
    public function it_checks_restoration_logging_configuration()
    {
        Config::set('audit.observer.log_restorations', false);
        
        $observer = app(UniversalAuditObserver::class);
        $observer->refreshConfig(); // Refresh config after setting
        
        $this->assertFalse($observer->shouldLogRestorations());
    }

    /** @test */
    public function it_checks_force_deletion_logging_configuration()
    {
        Config::set('audit.observer.log_force_deletions', false);
        
        $observer = app(UniversalAuditObserver::class);
        $observer->refreshConfig(); // Refresh config after setting
        
        $this->assertFalse($observer->shouldLogForceDeletions());
    }
}