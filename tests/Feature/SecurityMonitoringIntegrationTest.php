<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\SecurityMonitoringService;
use App\Services\AuditService;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SecurityMonitoringIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected SecurityMonitoringService $securityService;
    protected AuditService $auditService;
    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->securityService = app(SecurityMonitoringService::class);
        $this->auditService = app(AuditService::class);
        
        // Create or get existing roles
        $customerRole = \App\Models\Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer role']);
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super admin role']);
        
        $this->user = User::factory()->create(['role_id' => $customerRole->id]);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        
        Notification::fake();
    }

    public function test_analyzes_user_activity_for_suspicious_patterns()
    {
        // Create multiple failed login attempts directly in audit_logs table
        for ($i = 0; $i < 6; $i++) {
            AuditLog::create([
                'user_id' => $this->user->id,
                'event_type' => 'authentication',
                'action' => 'failed_login',
                'ip_address' => '192.168.1.100',
                'additional_data' => ['severity' => 'medium'],
                'created_at' => now()
            ]);
        }

        $analysis = $this->securityService->analyzeUserActivity($this->user, '192.168.1.100');

        $this->assertGreaterThanOrEqual(30, $analysis['risk_score']);
        $this->assertContains('Multiple failed login attempts detected (6 attempts)', $analysis['alerts']);
        $this->assertEquals($this->user->id, $analysis['user_id']);
    }

    public function test_analyzes_ip_activity_for_suspicious_patterns()
    {
        $ipAddress = '192.168.1.200';
        
        // Create multiple failed attempts from same IP
        for ($i = 0; $i < 12; $i++) {
            $this->auditService->logSecurityEvent('failed_authentication', [
                'ip_address' => $ipAddress,
                'severity' => 'medium'
            ]);
        }

        $analysis = $this->securityService->analyzeIPActivity($ipAddress);

        $this->assertGreaterThanOrEqual(40, $analysis['risk_score']);
        $this->assertContains('Multiple failed authentication attempts from IP', $analysis['alerts']);
        $this->assertEquals($ipAddress, $analysis['ip_address']);
    }

    public function test_detects_system_anomalies()
    {
        // Create mass deletion pattern
        for ($i = 0; $i < 25; $i++) {
            AuditLog::create([
                'user_id' => $this->user->id,
                'event_type' => 'model_deleted',
                'action' => 'delete',
                'auditable_type' => 'App\\Models\\Package',
                'auditable_id' => $i + 1,
                'ip_address' => '192.168.1.100',
                'created_at' => now()
            ]);
        }

        $anomalies = $this->securityService->detectSystemAnomalies();

        $this->assertNotEmpty($anomalies);
        $this->assertEquals('mass_deletion', $anomalies[0]['type']);
        $this->assertEquals('high', $anomalies[0]['severity']);
    }

    public function test_generates_security_alerts()
    {
        $alertData = [
            'risk_score' => 85,
            'risk_level' => 'high',
            'alerts' => ['Test security alert'],
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.100',
            'analysis_type' => 'test'
        ];

        $this->securityService->generateSecurityAlert($alertData);

        // Check that audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'security_event',
            'action' => 'security_alert_generated',
            'user_id' => $this->user->id
        ]);

        // Check that notification was sent
        Notification::assertSentTo(
            [$this->admin],
            SecurityAlertNotification::class
        );

        // Check that alert was cached
        $cacheKey = 'security_alerts_' . now()->format('Y-m-d');
        $cachedAlerts = Cache::get($cacheKey);
        $this->assertNotEmpty($cachedAlerts);
    }

    public function test_security_monitoring_middleware_integration()
    {
        $this->actingAs($this->user);

        // Make multiple rapid requests to trigger activity monitoring
        for ($i = 0; $i < 5; $i++) {
            $response = $this->get('/dashboard');
            $response->assertStatus(200);
        }

        // Check that user activity was monitored (should create audit logs)
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'event_type' => 'authentication'
        ]);
    }

    public function test_failed_authentication_tracking()
    {
        // Simulate failed login attempts
        for ($i = 0; $i < 3; $i++) {
            $response = $this->post('/login', [
                'email' => $this->user->email,
                'password' => 'wrong-password'
            ]);
        }

        // Check that failed attempts were logged
        $failedAttempts = AuditLog::where('event_type', 'security_event')
            ->where('action', 'failed_authentication')
            ->count();

        $this->assertGreaterThan(0, $failedAttempts);
    }

    public function test_risk_level_calculation()
    {
        // Test different risk score thresholds
        $testCases = [
            ['score' => 95, 'expected' => 'critical'],
            ['score' => 80, 'expected' => 'high'],
            ['score' => 60, 'expected' => 'medium'],
            ['score' => 30, 'expected' => 'low'],
            ['score' => 10, 'expected' => 'minimal']
        ];

        foreach ($testCases as $case) {
            $reflection = new \ReflectionClass($this->securityService);
            $method = $reflection->getMethod('getRiskLevel');
            $method->setAccessible(true);
            
            $result = $method->invoke($this->securityService, $case['score']);
            $this->assertEquals($case['expected'], $result);
        }
    }

    public function test_security_dashboard_access_control()
    {
        // Test that regular users cannot access security dashboard
        $this->actingAs($this->user);
        $response = $this->get('/admin/security-dashboard');
        $response->assertStatus(403);

        // Test that admins can access security dashboard
        $this->actingAs($this->admin);
        $response = $this->get('/admin/security-dashboard');
        $response->assertStatus(200);
    }

    public function test_anomaly_detection_command()
    {
        // Create some anomalous data
        for ($i = 0; $i < 15; $i++) {
            AuditLog::create([
                'user_id' => $this->user->id,
                'event_type' => 'model_deleted',
                'action' => 'delete',
                'auditable_type' => 'App\\Models\\Package',
                'auditable_id' => $i + 1,
                'ip_address' => '192.168.1.100',
                'created_at' => now()
            ]);
        }

        $this->artisan('security:detect-anomalies --hours=1 --alert-threshold=medium')
            ->assertExitCode(0);

        // Check that alerts were generated
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'security_event',
            'action' => 'security_alert_generated'
        ]);
    }

    public function test_security_alert_notification_content()
    {
        $alertData = [
            'risk_score' => 90,
            'risk_level' => 'critical',
            'alerts' => ['Critical security event detected'],
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.100'
        ];

        $notification = new SecurityAlertNotification($alertData);
        $mailMessage = $notification->toMail($this->admin);

        $this->assertStringContainsString('[CRITICAL]', $mailMessage->subject);
        $this->assertStringContainsString('Risk Score: 90/100', $mailMessage->introLines[1]);
    }

    public function test_caching_of_security_metrics()
    {
        // Generate some security events
        $this->auditService->logSecurityEvent('failed_authentication', [
            'severity' => 'medium',
            'ip_address' => '192.168.1.100'
        ]);

        // First call should cache the results
        $summary1 = $this->auditService->getSecurityEventsSummary(24);
        
        // Second call should use cached results
        $summary2 = $this->auditService->getSecurityEventsSummary(24);
        
        $this->assertEquals($summary1, $summary2);
        $this->assertGreaterThan(0, $summary1['total_events']);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}