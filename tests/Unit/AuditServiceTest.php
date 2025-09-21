<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Package;
use App\Models\Role;
use App\Services\AuditService;
use App\Services\AuditCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $auditService;
    protected $mockCacheService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockCacheService = Mockery::mock(AuditCacheService::class);
        $this->auditService = new AuditService($this->mockCacheService);
        
        // Create or find existing role
        $role = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer role']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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
    public function it_handles_invalid_audit_data_gracefully()
    {
        Log::shouldReceive('error')->once();

        $invalidData = [
            'user_id' => $this->user->id,
            // Missing required fields
        ];

        $result = $this->auditService->log($invalidData);

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_log_model_creation()
    {
        $package = Package::factory()->create();

        $auditLog = $this->auditService->logModelCreated($package, $this->user);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('model_created', $auditLog->event_type);
        $this->assertEquals('create', $auditLog->action);
        $this->assertEquals(Package::class, $auditLog->auditable_type);
        $this->assertEquals($package->id, $auditLog->auditable_id);
        $this->assertNotNull($auditLog->new_values);
    }

    /** @test */
    public function it_can_log_model_updates()
    {
        $package = Package::factory()->create(['status' => 'processing']);
        $oldValues = $package->getAttributes();
        
        $package->update(['status' => 'ready']);

        $auditLog = $this->auditService->logModelUpdated($package, $oldValues, $this->user);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('model_updated', $auditLog->event_type);
        $this->assertEquals('update', $auditLog->action);
        $this->assertEquals(Package::class, $auditLog->auditable_type);
        $this->assertEquals($package->id, $auditLog->auditable_id);
        $this->assertArrayHasKey('status', $auditLog->old_values);
        $this->assertEquals('processing', $auditLog->old_values['status']);
        $this->assertEquals('ready', $auditLog->new_values['status']);
    }

    /** @test */
    public function it_skips_logging_when_no_changes_detected()
    {
        $package = Package::factory()->create(['status' => 'processing']);
        $oldValues = $package->getAttributes();
        
        // No actual changes made
        $auditLog = $this->auditService->logModelUpdated($package, $oldValues, $this->user);

        $this->assertNull($auditLog);
    }

    /** @test */
    public function it_can_log_model_deletion()
    {
        $package = Package::factory()->create();

        $auditLog = $this->auditService->logModelDeleted($package, $this->user);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('model_deleted', $auditLog->event_type);
        $this->assertEquals('delete', $auditLog->action);
        $this->assertEquals(Package::class, $auditLog->auditable_type);
        $this->assertEquals($package->id, $auditLog->auditable_id);
        $this->assertNotNull($auditLog->old_values);
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
    public function it_can_log_authorization_events()
    {
        $oldValues = ['role' => 'customer'];
        $newValues = ['role' => 'admin'];

        $auditLog = $this->auditService->logAuthorization('role_change', $this->user, $oldValues, $newValues);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('authorization', $auditLog->event_type);
        $this->assertEquals('role_change', $auditLog->action);
        $this->assertEquals($oldValues, $auditLog->old_values);
        $this->assertEquals($newValues, $auditLog->new_values);
    }

    /** @test */
    public function it_can_log_business_actions()
    {
        $package = Package::factory()->create();

        $auditLog = $this->auditService->logBusinessAction('package_consolidation', $package, [
            'consolidated_with' => [1, 2, 3]
        ]);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('business_action', $auditLog->event_type);
        $this->assertEquals('package_consolidation', $auditLog->action);
        $this->assertEquals(Package::class, $auditLog->auditable_type);
        $this->assertEquals($package->id, $auditLog->auditable_id);
        $this->assertArrayHasKey('consolidated_with', $auditLog->additional_data);
    }

    /** @test */
    public function it_can_log_financial_transactions()
    {
        $transactionData = [
            'amount' => 100.50,
            'currency' => 'USD',
            'type' => 'payment'
        ];

        $auditLog = $this->auditService->logFinancialTransaction('payment_processed', $transactionData, $this->user);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('financial_transaction', $auditLog->event_type);
        $this->assertEquals('payment_processed', $auditLog->action);
        $this->assertEquals($transactionData, $auditLog->new_values);
        $this->assertEquals(100.50, $auditLog->additional_data['amount']);
        $this->assertEquals('USD', $auditLog->additional_data['currency']);
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

    /** @test */
    public function it_can_log_system_events()
    {
        $eventData = [
            'command' => 'backup:create',
            'status' => 'success'
        ];

        $auditLog = $this->auditService->logSystemEvent('backup_created', $eventData);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertNull($auditLog->user_id);
        $this->assertEquals('system_event', $auditLog->event_type);
        $this->assertEquals('backup_created', $auditLog->action);
        $this->assertEquals('backup:create', $auditLog->additional_data['command']);
        $this->assertEquals('System', $auditLog->additional_data['system_user']);
    }

    /** @test */
    public function it_can_process_batch_audit_entries()
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
        $this->assertEquals('test_event_1', $results[0]->event_type);
        $this->assertEquals('test_event_2', $results[1]->event_type);
    }

    /** @test */
    public function it_can_process_bulk_audit_entries()
    {
        $entries = [];
        for ($i = 1; $i <= 10; $i++) {
            $entries[] = [
                'user_id' => $this->user->id,
                'event_type' => 'bulk_test',
                'action' => "action_{$i}"
            ];
        }

        $count = $this->auditService->logBulk($entries);

        $this->assertEquals(10, $count);
        $this->assertEquals(10, AuditLog::where('event_type', 'bulk_test')->count());
    }

    /** @test */
    public function it_filters_sensitive_fields_from_model_attributes()
    {
        $user = User::factory()->create([
            'password' => 'secret',
            'remember_token' => 'token123'
        ]);

        $auditLog = $this->auditService->logModelCreated($user);

        $this->assertArrayNotHasKey('password', $auditLog->new_values);
        $this->assertArrayNotHasKey('remember_token', $auditLog->new_values);
        $this->assertArrayHasKey('email', $auditLog->new_values);
    }

    /** @test */
    public function it_can_get_user_recent_ips()
    {
        // Create audit logs with different IPs
        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'ip_address' => '192.168.1.1',
            'created_at' => now()->subDays(5)
        ]);

        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'ip_address' => '192.168.1.2',
            'created_at' => now()->subDays(10)
        ]);

        $recentIPs = $this->auditService->getUserRecentIPs($this->user->id, 30);

        $this->assertCount(2, $recentIPs);
        $this->assertContains('192.168.1.1', $recentIPs);
        $this->assertContains('192.168.1.2', $recentIPs);
    }

    /** @test */
    public function it_can_get_user_recent_logins()
    {
        // Create recent login entries
        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'created_at' => now()->subMinutes(5)
        ]);

        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'created_at' => now()->subMinutes(30)
        ]);

        $recentLogins = $this->auditService->getUserRecentLogins($this->user->id, 60);

        $this->assertCount(2, $recentLogins);
    }

    /** @test */
    public function it_can_get_security_events_summary()
    {
        // Create various security events
        AuditLog::create([
            'event_type' => 'security_event',
            'action' => 'failed_authentication',
            'ip_address' => '192.168.1.1',
            'additional_data' => ['severity' => 'medium'],
            'created_at' => now()->subHours(2)
        ]);

        AuditLog::create([
            'event_type' => 'security_event',
            'action' => 'suspicious_activity_detected',
            'ip_address' => '192.168.1.2',
            'additional_data' => ['severity' => 'high'],
            'created_at' => now()->subHours(1)
        ]);

        $summary = $this->auditService->getSecurityEventsSummary(24);

        $this->assertEquals(2, $summary['total_events']);
        $this->assertEquals(1, $summary['failed_logins']);
        $this->assertEquals(1, $summary['suspicious_activities']);
        $this->assertEquals(2, $summary['unique_ips']);
    }

    /** @test */
    public function it_can_get_audit_statistics()
    {
        // Create test audit entries
        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'created_at' => now()->subDays(2)
        ]);

        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'model_updated',
            'action' => 'update',
            'created_at' => now()->subDays(1)
        ]);

        $stats = $this->auditService->getAuditStatistics(7);

        $this->assertEquals(2, $stats['total_entries']);
        $this->assertArrayHasKey('authentication', $stats['entries_by_type']);
        $this->assertArrayHasKey('model_updated', $stats['entries_by_type']);
    }
}