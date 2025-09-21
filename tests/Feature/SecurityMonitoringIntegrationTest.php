<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use App\Services\SecurityMonitoringService;
use App\Services\AuditService;
use App\Listeners\SecurityMonitoringListener;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Tests\TestCase;
use Carbon\Carbon;

class SecurityMonitoringIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $securityService;
    protected $auditService;
    protected $user;
    protected $adminUser;
    protected $superAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->securityService = app(SecurityMonitoringService::class);
        $this->auditService = app(AuditService::class);
        
        $this->createTestUsers();
    }

    protected function createTestUsers()
    {
        $customerRole = Role::factory()->create(['name' => 'customer']);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $superAdminRole = Role::factory()->create(['name' => 'superadmin']);
        
        $this->user = User::factory()->create(['role_id' => $customerRole->id]);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->superAdminUser = User::factory()->create(['role_id' => $superAdminRole->id]);
    }

    /** @test */
    public function it_integrates_authentication_events_with_security_monitoring()
    {
        Notification::fake();
        
        // Simulate multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email' => $this->user->email,
                'password' => 'wrong-password'
            ]);
        }

        // Manually trigger security analysis
        $analysis = $this->securityService->analyzeUserActivity($this->user, '127.0.0.1');

        $this->assertGreaterThan(0, $analysis['risk_score']);
        $this->assertContains('Multiple failed login attempts detected', $analysis['alerts']);

        // Check that security alert was generated
        if ($analysis['risk_score'] >= SecurityMonitoringService::MEDIUM_RISK) {
            $this->securityService->generateSecurityAlert($analysis);
            
            $this->assertDatabaseHas('audit_logs', [
                'event_type' => 'security_event',
                'action' => 'security_alert_generated'
            ]);

            Notification::assertSentTo($this->superAdminUser, SecurityAlertNotification::class);
        }
    }

    /** @test */
    public function it_detects_and_responds_to_suspicious_login_patterns()
    {
        Notification::fake();
        
        // Create multiple login attempts from different IPs in short time
        $ips = ['192.168.1.1', '192.168.1.2', '192.168.1.3', '192.168.1.4'];
        
        foreach ($ips as $ip) {
            AuditLog::create([
                'user_id' => $this->user->id,
                'event_type' => 'authentication',
                'action' => 'login',
                'ip_address' => $ip,
                'created_at' => now()->subMinutes(30)
            ]);
        }

        $analysis = $this->securityService->analyzeUserActivity($this->user);

        $this->assertGreaterThanOrEqual(20, $analysis['risk_score']);
        $this->assertContains('Access from multiple IP addresses detected', $analysis['alerts']);

        // Generate security alert
        $this->securityService->generateSecurityAlert($analysis);

        // Verify alert was logged and notifications sent
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'security_event',
            'action' => 'security_alert_generated',
            'user_id' => $this->user->id
        ]);

        Notification::assertSentTo($this->superAdminUser, SecurityAlertNotification::class);
    }

    /** @test */
    public function it_monitors_ip_based_attack_patterns()
    {
        $attackerIP = '203.0.113.1';
        
        // Simulate brute force attack from single IP
        for ($i = 0; $i < 15; $i++) {
            AuditLog::create([
                'event_type' => 'authentication',
                'action' => 'failed_login',
                'ip_address' => $attackerIP,
                'created_at' => now()->subMinutes(rand(1, 30))
            ]);
        }

        $ipAnalysis = $this->securityService->analyzeIPActivity($attackerIP);

        $this->assertGreaterThanOrEqual(40, $ipAnalysis['risk_score']);
        $this->assertContains('Multiple failed authentication attempts from IP', $ipAnalysis['alerts']);
        $this->assertEquals('high', $ipAnalysis['risk_level']);
    }

    /** @test */
    public function it_detects_system_wide_anomalies()
    {
        // Create mass deletion pattern
        for ($i = 0; $i < 25; $i++) {
            AuditLog::create([
                'user_id' => $this->adminUser->id,
                'event_type' => 'model_deleted',
                'action' => 'delete',
                'auditable_type' => 'App\\Models\\Package',
                'auditable_id' => $i + 1,
                'created_at' => now()->subMinutes(rand(1, 30))
            ]);
        }

        // Create unauthorized access attempts
        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'security_event',
            'action' => 'unauthorized_access',
            'created_at' => now()->subMinutes(15)
        ]);

        $anomalies = $this->securityService->detectSystemAnomalies();

        $this->assertGreaterThan(0, count($anomalies));
        
        $massDeletion = collect($anomalies)->firstWhere('type', 'mass_deletion');
        $this->assertNotNull($massDeletion);
        $this->assertEquals('high', $massDeletion['severity']);

        $unauthorizedAccess = collect($anomalies)->firstWhere('type', 'unauthorized_access');
        $this->assertNotNull($unauthorizedAccess);
        $this->assertEquals('critical', $unauthorizedAccess['severity']);
    }

    /** @test */
    public function it_integrates_with_audit_service_for_comprehensive_monitoring()
    {
        // Create various audit events
        $this->auditService->logAuthentication('login', $this->user, ['ip_address' => '192.168.1.1']);
        $this->auditService->logSecurityEvent('failed_authentication', ['severity' => 'medium']);
        $this->auditService->logBusinessAction('bulk_update', null, ['bulk_operation' => true]);

        // Get security summary
        $summary = $this->auditService->getSecurityEventsSummary(24);

        $this->assertArrayHasKey('total_events', $summary);
        $this->assertArrayHasKey('failed_logins', $summary);
        $this->assertArrayHasKey('unique_ips', $summary);
        $this->assertGreaterThan(0, $summary['total_events']);
    }

    /** @test */
    public function it_caches_security_alerts_for_dashboard_display()
    {
        $alertData = [
            'user_id' => $this->user->id,
            'risk_score' => 85,
            'risk_level' => 'high',
            'alerts' => ['Multiple failed login attempts'],
            'ip_address' => '192.168.1.1'
        ];

        $this->securityService->generateSecurityAlert($alertData);

        // Check that alert was cached
        $cacheKey = 'security_alerts_' . now()->format('Y-m-d');
        $cachedAlerts = Cache::get($cacheKey, []);

        $this->assertNotEmpty($cachedAlerts);
        $this->assertEquals(85, $cachedAlerts[0]['risk_score']);
        $this->assertEquals('high', $cachedAlerts[0]['risk_level']);
    }

    /** @test */
    public function it_handles_concurrent_security_events()
    {
        Notification::fake();
        
        // Simulate concurrent events from multiple users
        $users = [$this->user, $this->adminUser];
        
        foreach ($users as $user) {
            for ($i = 0; $i < 8; $i++) {
                AuditLog::create([
                    'user_id' => $user->id,
                    'event_type' => 'authentication',
                    'action' => 'failed_login',
                    'ip_address' => '192.168.1.' . ($user->id + 100),
                    'created_at' => now()->subMinutes(rand(1, 45))
                ]);
            }
        }

        // Analyze each user
        foreach ($users as $user) {
            $analysis = $this->securityService->analyzeUserActivity($user);
            
            if ($analysis['risk_score'] >= SecurityMonitoringService::MEDIUM_RISK) {
                $this->securityService->generateSecurityAlert($analysis);
            }
        }

        // Verify multiple alerts were generated
        $alertCount = AuditLog::where('event_type', 'security_event')
            ->where('action', 'security_alert_generated')
            ->count();

        $this->assertGreaterThan(0, $alertCount);
    }

    /** @test */
    public function it_tracks_user_behavior_patterns_over_time()
    {
        // Create historical activity pattern
        for ($day = 7; $day >= 1; $day--) {
            for ($i = 0; $i < 5; $i++) { // Normal activity: 5 actions per day
                AuditLog::create([
                    'user_id' => $this->user->id,
                    'event_type' => 'business_action',
                    'action' => 'view_package',
                    'created_at' => now()->subDays($day)->addHours(rand(8, 18))
                ]);
            }
        }

        // Create unusual spike in activity
        for ($i = 0; $i < 25; $i++) {
            AuditLog::create([
                'user_id' => $this->user->id,
                'event_type' => 'business_action',
                'action' => 'bulk_update',
                'created_at' => now()->subMinutes(rand(1, 30))
            ]);
        }

        $analysis = $this->securityService->analyzeUserActivity($this->user);

        $this->assertGreaterThan(0, $analysis['risk_score']);
        $this->assertContains('Unusually high activity volume detected', $analysis['alerts']);
    }

    /** @test */
    public function it_integrates_with_authentication_listeners()
    {
        Event::fake();
        
        $listener = new SecurityMonitoringListener($this->securityService, $this->auditService);

        // Simulate successful login
        $loginEvent = new Login('web', $this->user, false);
        $listener->handleLogin($loginEvent);

        // Verify audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'event_type' => 'authentication',
            'action' => 'login'
        ]);
    }

    /** @test */
    public function it_monitors_privilege_escalation_attempts()
    {
        // Simulate privilege escalation attempts
        for ($i = 0; $i < 3; $i++) {
            AuditLog::create([
                'user_id' => $this->user->id,
                'event_type' => 'security_event',
                'action' => 'unauthorized_access',
                'additional_data' => [
                    'attempted_resource' => '/admin/users',
                    'required_permission' => 'admin'
                ],
                'created_at' => now()->subMinutes(rand(1, 30))
            ]);
        }

        $analysis = $this->securityService->analyzeUserActivity($this->user);

        $this->assertGreaterThanOrEqual(40, $analysis['risk_score']);
        $this->assertContains('Privilege escalation attempts detected', $analysis['alerts']);
    }

    /** @test */
    public function it_provides_security_dashboard_metrics()
    {
        // Create diverse security events
        AuditLog::create([
            'event_type' => 'security_event',
            'action' => 'failed_authentication',
            'ip_address' => '192.168.1.1',
            'additional_data' => ['severity' => 'medium']
        ]);

        AuditLog::create([
            'event_type' => 'security_event',
            'action' => 'suspicious_activity_detected',
            'ip_address' => '192.168.1.2',
            'additional_data' => ['severity' => 'high']
        ]);

        AuditLog::create([
            'event_type' => 'security_event',
            'action' => 'security_alert_generated',
            'additional_data' => ['severity' => 'critical']
        ]);

        $summary = $this->auditService->getSecurityEventsSummary(24);

        $this->assertEquals(3, $summary['total_events']);
        $this->assertEquals(1, $summary['failed_logins']);
        $this->assertEquals(1, $summary['suspicious_activities']);
        $this->assertEquals(1, $summary['security_alerts']);
        $this->assertEquals(2, $summary['unique_ips']);
        $this->assertArrayHasKey('events_by_severity', $summary);
    }

    /** @test */
    public function it_handles_bulk_security_event_processing()
    {
        // Create large number of security events
        $events = [];
        for ($i = 0; $i < 50; $i++) {
            $events[] = [
                'event_type' => 'security_event',
                'action' => 'automated_scan_detected',
                'ip_address' => '203.0.113.' . ($i % 10),
                'additional_data' => ['scan_type' => 'port_scan'],
                'created_at' => now()->subMinutes(rand(1, 60))
            ];
        }

        $count = $this->auditService->logBulk($events);

        $this->assertEquals(50, $count);

        // Analyze system-wide anomalies
        $anomalies = $this->securityService->detectSystemAnomalies();

        // Should detect the bulk security events as an anomaly
        $this->assertGreaterThan(0, count($anomalies));
    }

    /** @test */
    public function it_maintains_audit_trail_for_security_responses()
    {
        Notification::fake();
        
        $alertData = [
            'user_id' => $this->user->id,
            'risk_score' => 90,
            'risk_level' => 'critical',
            'alerts' => ['Critical security threat detected'],
            'ip_address' => '203.0.113.1'
        ];

        $this->securityService->generateSecurityAlert($alertData);

        // Verify complete audit trail
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'security_event',
            'action' => 'security_alert_generated',
            'user_id' => $this->user->id
        ]);

        $auditLog = AuditLog::where('action', 'security_alert_generated')->first();
        $this->assertEquals(90, $auditLog->additional_data['risk_score']);
        $this->assertEquals('critical', $auditLog->additional_data['risk_level']);
        $this->assertEquals(['Critical security threat detected'], $auditLog->additional_data['alerts']);

        // Verify notification was sent
        Notification::assertSentTo($this->superAdminUser, SecurityAlertNotification::class);
    }
}