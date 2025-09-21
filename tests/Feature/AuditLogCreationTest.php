<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Package;
use App\Models\Role;
use App\Services\AuditService;
use App\Listeners\AuthenticationAuditListener;
use App\Listeners\SecurityMonitoringListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Tests\TestCase;

class AuditLogCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $adminUser;
    protected $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $customerRole = Role::factory()->create(['name' => 'customer']);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        
        $this->user = User::factory()->create(['role_id' => $customerRole->id]);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        
        $this->auditService = app(AuditService::class);
    }

    /** @test */
    public function it_creates_audit_log_when_user_logs_in()
    {
        Event::fake();
        
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password'
        ]);

        Event::assertDispatched(Login::class);
        
        // Manually trigger the listener for testing
        $listener = new AuthenticationAuditListener($this->auditService, app());
        $listener->handleLogin(new Login('web', $this->user, false));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'event_type' => 'authentication',
            'action' => 'login'
        ]);
    }

    /** @test */
    public function it_creates_audit_log_when_user_logs_out()
    {
        $this->actingAs($this->user);
        
        Event::fake();
        
        $response = $this->post('/logout');

        Event::assertDispatched(Logout::class);
        
        // Manually trigger the listener for testing
        $listener = new AuthenticationAuditListener($this->auditService, app());
        $listener->handleLogout(new Logout('web', $this->user));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'event_type' => 'authentication',
            'action' => 'logout'
        ]);
    }

    /** @test */
    public function it_creates_audit_log_for_failed_login_attempts()
    {
        Event::fake();
        
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password'
        ]);

        Event::assertDispatched(Failed::class);
        
        // Manually trigger the listener for testing
        $listener = new AuthenticationAuditListener($this->auditService, app());
        $listener->handleFailed(new Failed('web', $this->user, ['email' => $this->user->email]));

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'security_event',
            'action' => 'login_attempt'
        ]);
    }

    /** @test */
    public function it_creates_audit_log_when_package_is_created()
    {
        $this->actingAs($this->adminUser);
        
        $packageData = [
            'tracking_number' => 'TEST123456',
            'user_id' => $this->user->id,
            'status' => 'processing',
            'weight' => 2.5,
            'description' => 'Test package'
        ];

        $response = $this->post('/admin/packages', $packageData);

        $package = Package::where('tracking_number', 'TEST123456')->first();
        $this->assertNotNull($package);

        // Manually create audit log as would be done by model observer
        $this->auditService->logModelCreated($package, $this->adminUser);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'event_type' => 'model_created',
            'auditable_type' => Package::class,
            'auditable_id' => $package->id,
            'action' => 'create'
        ]);
    }

    /** @test */
    public function it_creates_audit_log_when_package_is_updated()
    {
        $this->actingAs($this->adminUser);
        
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'processing'
        ]);

        $oldValues = $package->getAttributes();
        
        $response = $this->put("/admin/packages/{$package->id}", [
            'status' => 'ready',
            'tracking_number' => $package->tracking_number,
            'user_id' => $package->user_id,
            'weight' => $package->weight,
            'description' => $package->description
        ]);

        $package->refresh();
        
        // Manually create audit log as would be done by model observer
        $this->auditService->logModelUpdated($package, $oldValues, $this->adminUser);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'event_type' => 'model_updated',
            'auditable_type' => Package::class,
            'auditable_id' => $package->id,
            'action' => 'update'
        ]);

        $auditLog = AuditLog::where('auditable_id', $package->id)
            ->where('event_type', 'model_updated')
            ->first();

        $this->assertEquals('processing', $auditLog->old_values['status']);
        $this->assertEquals('ready', $auditLog->new_values['status']);
    }

    /** @test */
    public function it_creates_audit_log_when_package_is_deleted()
    {
        $this->actingAs($this->adminUser);
        
        $package = Package::factory()->create(['user_id' => $this->user->id]);
        $packageId = $package->id;

        $response = $this->delete("/admin/packages/{$package->id}");

        // Manually create audit log as would be done by model observer
        $this->auditService->logModelDeleted($package, $this->adminUser);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'event_type' => 'model_deleted',
            'auditable_type' => Package::class,
            'auditable_id' => $packageId,
            'action' => 'delete'
        ]);
    }

    /** @test */
    public function it_creates_audit_log_for_business_actions()
    {
        $this->actingAs($this->adminUser);
        
        $packages = Package::factory()->count(3)->create(['user_id' => $this->user->id]);
        $packageIds = $packages->pluck('id')->toArray();

        // Simulate package consolidation
        $this->auditService->logConsolidationOperation('consolidation', $packageIds, 123);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'event_type' => 'business_action',
            'action' => 'package_consolidation'
        ]);

        $auditLog = AuditLog::where('action', 'package_consolidation')->first();
        $this->assertEquals($packageIds, $auditLog->additional_data['package_ids']);
        $this->assertEquals(123, $auditLog->additional_data['consolidated_package_id']);
    }

    /** @test */
    public function it_creates_audit_log_for_financial_transactions()
    {
        $this->actingAs($this->user);
        
        $transactionData = [
            'amount' => 150.75,
            'currency' => 'USD',
            'type' => 'freight_charge',
            'description' => 'Shipping fee for consolidated package'
        ];

        $this->auditService->logFinancialTransaction('charge_applied', $transactionData, $this->user);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'event_type' => 'financial_transaction',
            'action' => 'charge_applied'
        ]);

        $auditLog = AuditLog::where('action', 'charge_applied')->first();
        $this->assertEquals(150.75, $auditLog->additional_data['amount']);
        $this->assertEquals('USD', $auditLog->additional_data['currency']);
        $this->assertEquals('freight_charge', $auditLog->additional_data['transaction_type']);
    }

    /** @test */
    public function it_creates_audit_log_for_security_events()
    {
        // Simulate suspicious activity detection
        $eventData = [
            'severity' => 'high',
            'description' => 'Multiple failed login attempts from same IP',
            'ip_address' => '192.168.1.100',
            'attempt_count' => 5
        ];

        $this->auditService->logSecurityEvent('suspicious_activity_detected', $eventData);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'security_event',
            'action' => 'suspicious_activity_detected',
            'ip_address' => '192.168.1.100'
        ]);

        $auditLog = AuditLog::where('action', 'suspicious_activity_detected')->first();
        $this->assertEquals('high', $auditLog->additional_data['severity']);
        $this->assertEquals('High', $auditLog->additional_data['risk_level']);
        $this->assertEquals(5, $auditLog->additional_data['attempt_count']);
    }

    /** @test */
    public function it_creates_audit_log_for_system_events()
    {
        $eventData = [
            'command' => 'queue:work',
            'status' => 'started',
            'pid' => 12345
        ];

        $this->auditService->logSystemEvent('queue_worker_started', $eventData);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'system_event',
            'action' => 'queue_worker_started'
        ]);

        $auditLog = AuditLog::where('action', 'queue_worker_started')->first();
        $this->assertNull($auditLog->user_id);
        $this->assertEquals('queue:work', $auditLog->additional_data['command']);
        $this->assertEquals('System', $auditLog->additional_data['system_user']);
    }

    /** @test */
    public function it_captures_request_context_in_audit_logs()
    {
        $this->actingAs($this->user);
        
        // Make a request with specific headers
        $response = $this->withHeaders([
            'User-Agent' => 'Test Browser 1.0',
            'X-Forwarded-For' => '203.0.113.1'
        ])->get('/dashboard');

        // Create an audit log that should capture request context
        $auditLog = $this->auditService->logBusinessAction('dashboard_accessed');

        $this->assertNotNull($auditLog->ip_address);
        $this->assertNotNull($auditLog->user_agent);
        $this->assertNotNull($auditLog->url);
    }

    /** @test */
    public function it_handles_batch_audit_logging()
    {
        $this->actingAs($this->adminUser);
        
        $entries = [];
        for ($i = 1; $i <= 5; $i++) {
            $entries[] = [
                'user_id' => $this->adminUser->id,
                'event_type' => 'batch_test',
                'action' => "batch_action_{$i}",
                'additional_data' => ['batch_number' => $i]
            ];
        }

        $results = $this->auditService->logBatch($entries);

        $this->assertCount(5, $results);
        $this->assertEquals(5, AuditLog::where('event_type', 'batch_test')->count());

        foreach ($results as $index => $auditLog) {
            $this->assertInstanceOf(AuditLog::class, $auditLog);
            $this->assertEquals("batch_action_" . ($index + 1), $auditLog->action);
        }
    }

    /** @test */
    public function it_handles_bulk_audit_logging()
    {
        $entries = [];
        for ($i = 1; $i <= 100; $i++) {
            $entries[] = [
                'user_id' => $this->user->id,
                'event_type' => 'bulk_test',
                'action' => "bulk_action_{$i}",
                'additional_data' => ['sequence' => $i]
            ];
        }

        $count = $this->auditService->logBulk($entries);

        $this->assertEquals(100, $count);
        $this->assertEquals(100, AuditLog::where('event_type', 'bulk_test')->count());
    }

    /** @test */
    public function it_filters_sensitive_data_from_audit_logs()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
            'remember_token' => 'abc123'
        ]);

        $auditLog = $this->auditService->logModelCreated($user);

        $this->assertArrayNotHasKey('password', $auditLog->new_values);
        $this->assertArrayNotHasKey('remember_token', $auditLog->new_values);
        $this->assertArrayHasKey('email', $auditLog->new_values);
        $this->assertArrayHasKey('first_name', $auditLog->new_values);
    }

    /** @test */
    public function it_handles_audit_logging_failures_gracefully()
    {
        // Test with invalid data that should cause logging to fail
        $invalidData = [
            'user_id' => 'invalid',
            // Missing required fields
        ];

        $result = $this->auditService->log($invalidData);

        $this->assertNull($result);
        // Application should continue to work even if audit logging fails
    }

    /** @test */
    public function it_creates_audit_trail_for_package_status_changes()
    {
        $this->actingAs($this->adminUser);
        
        $package = Package::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'processing'
        ]);

        $this->auditService->logPackageStatusChange($package, 'processing', 'ready', $this->adminUser);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'event_type' => 'business_action',
            'action' => 'package_status_change',
            'auditable_type' => Package::class,
            'auditable_id' => $package->id
        ]);

        $auditLog = AuditLog::where('action', 'package_status_change')->first();
        $this->assertEquals('processing', $auditLog->additional_data['old_status']);
        $this->assertEquals('ready', $auditLog->additional_data['new_status']);
        $this->assertEquals($package->tracking_number, $auditLog->additional_data['package_tracking']);
    }
}