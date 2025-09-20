<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AuditService;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Package;
use App\Jobs\ProcessAuditLogJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $auditService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = app(AuditService::class);
        
        // Create a simple role first
        $role = new \App\Models\Role([
            'name' => 'test_role_' . uniqid(),
            'description' => 'Test role for audit service'
        ]);
        $role->save();
        
        // Create a simple user
        $this->user = new User([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id
        ]);
        $this->user->save();
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
        $this->assertEquals('test_event', $auditLog->event_type);
        $this->assertEquals('test_action', $auditLog->action);
        $this->assertEquals($this->user->id, $auditLog->user_id);
    }

    /** @test */
    public function it_can_log_model_creation()
    {
        // Test with User model which is simpler
        $auditLog = $this->auditService->logModelCreated($this->user, $this->user);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('model_created', $auditLog->event_type);
        $this->assertEquals('create', $auditLog->action);
        $this->assertEquals(User::class, $auditLog->auditable_type);
        $this->assertEquals($this->user->id, $auditLog->auditable_id);
        $this->assertNotNull($auditLog->new_values);
    }

    /** @test */
    public function it_can_log_model_updates()
    {
        $oldValues = $this->user->getAttributes();
        $this->user->update(['first_name' => 'Updated']);

        $auditLog = $this->auditService->logModelUpdated($this->user, $oldValues, $this->user);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('model_updated', $auditLog->event_type);
        $this->assertEquals('update', $auditLog->action);
        
        // Check that old values are stored (excluding sensitive fields)
        $this->assertArrayNotHasKey('password', $auditLog->old_values);
        $this->assertArrayNotHasKey('password', $auditLog->new_values);
        $this->assertEquals('Test', $auditLog->old_values['first_name']);
        $this->assertEquals('Updated', $auditLog->new_values['first_name']);
    }

    /** @test */
    public function it_can_log_model_deletion()
    {
        $auditLog = $this->auditService->logModelDeleted($this->user, $this->user);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('model_deleted', $auditLog->event_type);
        $this->assertEquals('delete', $auditLog->action);
        $this->assertNotNull($auditLog->old_values);
    }

    /** @test */
    public function it_can_log_authentication_events()
    {
        $auditLog = $this->auditService->logAuthentication('login', $this->user);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('authentication', $auditLog->event_type);
        $this->assertEquals('login', $auditLog->action);
        $this->assertEquals($this->user->id, $auditLog->user_id);
    }

    /** @test */
    public function it_can_log_authorization_events()
    {
        $oldValues = ['role' => 'customer'];
        $newValues = ['role' => 'admin'];

        $auditLog = $this->auditService->logAuthorization('role_change', $this->user, $oldValues, $newValues);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('authorization', $auditLog->event_type);
        $this->assertEquals('role_change', $auditLog->action);
        $this->assertEquals($oldValues, $auditLog->old_values);
        $this->assertEquals($newValues, $auditLog->new_values);
    }

    /** @test */
    public function it_can_log_business_actions()
    {
        $auditLog = $this->auditService->logBusinessAction('consolidate', $this->user, ['test' => 'data']);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('business_action', $auditLog->event_type);
        $this->assertEquals('consolidate', $auditLog->action);
        $this->assertEquals(User::class, $auditLog->auditable_type);
        $this->assertEquals($this->user->id, $auditLog->auditable_id);
    }

    /** @test */
    public function it_can_log_financial_transactions()
    {
        $transactionData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'type' => 'payment'
        ];

        $auditLog = $this->auditService->logFinancialTransaction('payment_processed', $transactionData, $this->user);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('financial_transaction', $auditLog->event_type);
        $this->assertEquals('payment_processed', $auditLog->action);
        $this->assertEquals($transactionData, $auditLog->new_values);
    }

    /** @test */
    public function it_can_log_security_events()
    {
        Auth::login($this->user);

        $auditLog = $this->auditService->logSecurityEvent('failed_login', ['attempts' => 3]);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('security_event', $auditLog->event_type);
        $this->assertEquals('failed_login', $auditLog->action);
        $this->assertEquals($this->user->id, $auditLog->user_id);
    }

    /** @test */
    public function it_can_log_system_events()
    {
        $auditLog = $this->auditService->logSystemEvent('backup_created', ['backup_id' => 123]);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('system_event', $auditLog->event_type);
        $this->assertEquals('backup_created', $auditLog->action);
        $this->assertNull($auditLog->user_id);
    }

    /** @test */
    public function it_can_process_batch_audit_operations()
    {
        $entries = [
            [
                'user_id' => $this->user->id,
                'event_type' => 'test_event_1',
                'action' => 'test_action_1'
            ],
            [
                'user_id' => $this->user->id,
                'event_type' => 'test_event_2',
                'action' => 'test_action_2'
            ]
        ];

        $results = $this->auditService->logBatch($entries);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(AuditLog::class, $results[0]);
        $this->assertInstanceOf(AuditLog::class, $results[1]);
        $this->assertEquals(2, AuditLog::count());
    }

    /** @test */
    public function it_can_queue_audit_operations()
    {
        Queue::fake();

        $data = [
            'user_id' => $this->user->id,
            'event_type' => 'test_event',
            'action' => 'test_action'
        ];

        $this->auditService->logAsync($data);

        Queue::assertPushed(ProcessAuditLogJob::class);
    }

    /** @test */
    public function it_can_queue_batch_audit_operations()
    {
        Queue::fake();

        $entries = [
            [
                'user_id' => $this->user->id,
                'event_type' => 'test_event_1',
                'action' => 'test_action_1'
            ],
            [
                'user_id' => $this->user->id,
                'event_type' => 'test_event_2',
                'action' => 'test_action_2'
            ]
        ];

        $this->auditService->logBatchAsync($entries);

        Queue::assertPushed(ProcessAuditLogJob::class);
    }

    /** @test */
    public function it_handles_audit_failures_gracefully()
    {
        // Test with invalid data that should cause validation to fail
        $invalidData = [
            'invalid_field' => 'test'
            // Missing required event_type and action
        ];

        $result = $this->auditService->log($invalidData);

        $this->assertNull($result);
        $this->assertEquals(0, AuditLog::count());
    }

    /** @test */
    public function it_can_log_package_status_changes()
    {
        // Create a mock package object for testing
        $mockPackage = new \stdClass();
        $mockPackage->id = 123;
        $mockPackage->tracking_number = 'TEST127';
        $mockPackage->user_id = $this->user->id;

        $auditLog = $this->auditService->logBusinessAction('package_status_change', null, [
            'old_status' => 'processing',
            'new_status' => 'ready',
            'package_tracking' => 'TEST127',
            'customer_id' => $this->user->id
        ]);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('business_action', $auditLog->event_type);
        $this->assertEquals('package_status_change', $auditLog->action);
        $this->assertEquals('processing', $auditLog->additional_data['old_status']);
        $this->assertEquals('ready', $auditLog->additional_data['new_status']);
    }

    /** @test */
    public function it_excludes_sensitive_fields_from_model_attributes()
    {
        $role = new \App\Models\Role([
            'name' => 'test_role_2_' . uniqid(),
            'description' => 'Test role for audit service'
        ]);
        $role->save();
        
        $user = new User([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test2@example.com',
            'password' => bcrypt('secret'),
            'remember_token' => 'test_token',
            'role_id' => $role->id
        ]);
        $user->save();

        $auditLog = $this->auditService->logModelCreated($user);

        $this->assertArrayNotHasKey('password', $auditLog->new_values);
        $this->assertArrayNotHasKey('remember_token', $auditLog->new_values);
        $this->assertArrayHasKey('first_name', $auditLog->new_values);
        $this->assertArrayHasKey('email', $auditLog->new_values);
    }

    /** @test */
    public function it_adds_system_context_automatically()
    {
        $this->withoutMiddleware();
        
        $response = $this->actingAs($this->user)->get('/dashboard');
        
        $data = [
            'user_id' => $this->user->id,
            'event_type' => 'test_event',
            'action' => 'test_action'
        ];

        $auditLog = $this->auditService->log($data);

        $this->assertNotNull($auditLog->url);
        $this->assertNotNull($auditLog->ip_address);
        $this->assertNotNull($auditLog->user_agent);
    }
}