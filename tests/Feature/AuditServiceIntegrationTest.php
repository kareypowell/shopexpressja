<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\AuditService;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class AuditServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = app(AuditService::class);
    }

    /** @test */
    public function audit_service_is_registered_as_singleton()
    {
        $service1 = app(AuditService::class);
        $service2 = app(AuditService::class);

        $this->assertSame($service1, $service2);
    }

    /** @test */
    public function audit_service_can_log_without_authenticated_user()
    {
        $auditLog = $this->auditService->logSystemEvent('test_system_event', [
            'test_data' => 'value'
        ]);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('system_event', $auditLog->event_type);
        $this->assertEquals('test_system_event', $auditLog->action);
        $this->assertNull($auditLog->user_id);
    }

    /** @test */
    public function audit_service_captures_request_context()
    {
        $response = $this->get('/');
        
        $auditLog = $this->auditService->log([
            'event_type' => 'test_event',
            'action' => 'test_action'
        ]);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertNotNull($auditLog->url);
        $this->assertNotNull($auditLog->ip_address);
        $this->assertNotNull($auditLog->user_agent);
    }

    /** @test */
    public function audit_service_handles_json_data_properly()
    {
        $complexData = [
            'nested' => [
                'array' => ['value1', 'value2'],
                'object' => ['key' => 'value']
            ],
            'simple' => 'string'
        ];

        $auditLog = $this->auditService->log([
            'event_type' => 'test_event',
            'action' => 'test_action',
            'additional_data' => $complexData
        ]);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($complexData, $auditLog->additional_data);
    }

    /** @test */
    public function audit_service_validates_required_fields()
    {
        // Missing required fields should return null
        $result = $this->auditService->log([
            'some_field' => 'value'
            // Missing event_type and action
        ]);

        $this->assertNull($result);
        $this->assertEquals(0, AuditLog::count());
    }

    /** @test */
    public function audit_service_helper_methods_work_correctly()
    {
        // Create a role and user for testing
        $role = new Role([
            'name' => 'test_role_' . uniqid(),
            'description' => 'Test role'
        ]);
        $role->save();

        $user = new User([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id
        ]);
        $user->save();

        Auth::login($user);

        // Test authentication logging
        $authLog = $this->auditService->logAuthentication('login', $user);
        $this->assertEquals('authentication', $authLog->event_type);
        $this->assertEquals('login', $authLog->action);

        // Test authorization logging
        $authzLog = $this->auditService->logAuthorization('role_change', $user, 
            ['old_role' => 'customer'], 
            ['new_role' => 'admin']
        );
        $this->assertEquals('authorization', $authzLog->event_type);
        $this->assertEquals('role_change', $authzLog->action);

        // Test financial transaction logging
        $finLog = $this->auditService->logFinancialTransaction('payment_processed', [
            'amount' => 100.00,
            'currency' => 'USD'
        ], $user);
        $this->assertEquals('financial_transaction', $finLog->event_type);
        $this->assertEquals('payment_processed', $finLog->action);

        // Test security event logging
        $secLog = $this->auditService->logSecurityEvent('failed_login', [
            'attempts' => 3
        ]);
        $this->assertEquals('security_event', $secLog->event_type);
        $this->assertEquals('failed_login', $secLog->action);

        // Verify all logs were created
        $this->assertEquals(4, AuditLog::count());
    }
}