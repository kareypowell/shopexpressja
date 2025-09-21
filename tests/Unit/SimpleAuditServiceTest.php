<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $auditService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->auditService = app(AuditService::class);
        
        // Create or find existing role
        $role = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer role']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    /** @test */
    public function it_can_create_basic_audit_log_entry()
    {
        $data = [
            'user_id' => $this->user->id,
            'event_type' => 'test_event',
            'action' => 'test_action',
            'additional_data' => ['test' => 'data']
        ];

        $auditLog = $this->auditService->log($data);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('test_event', $auditLog->event_type);
        $this->assertEquals('test_action', $auditLog->action);
        $this->assertEquals(['test' => 'data'], $auditLog->additional_data);
    }

    /** @test */
    public function it_can_log_authentication_events()
    {
        $auditLog = $this->auditService->logAuthentication('login', $this->user, [
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser'
        ]);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('authentication', $auditLog->event_type);
        $this->assertEquals('login', $auditLog->action);
        $this->assertArrayHasKey('ip_address', $auditLog->additional_data);
        $this->assertArrayHasKey('user_agent', $auditLog->additional_data);
    }

    /** @test */
    public function it_can_log_security_events()
    {
        $eventData = [
            'severity' => 'high',
            'description' => 'Multiple failed login attempts'
        ];

        $auditLog = $this->auditService->logSecurityEvent('failed_authentication', $eventData);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('security_event', $auditLog->event_type);
        $this->assertEquals('failed_authentication', $auditLog->action);
        $this->assertEquals('high', $auditLog->additional_data['severity']);
        $this->assertEquals('High', $auditLog->additional_data['risk_level']);
    }
}