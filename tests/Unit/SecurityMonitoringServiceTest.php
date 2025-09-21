<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use App\Services\SecurityMonitoringService;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Carbon\Carbon;

class SecurityMonitoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $securityService;
    protected $user;
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->securityService = new SecurityMonitoringService();
        
        // Create test users
        $customerRole = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer role']);
        $adminRole = Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Admin role']);
        
        $this->user = User::factory()->create(['role_id' => $customerRole->id]);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
    }

    /** @test */
    public function it_can_analyze_user_activity_with_no_risk()
    {
        $analysis = $this->securityService->analyzeUserActivity($this->user, '192.168.1.1');

        $this->assertEquals(0, $analysis['risk_score']);
        $this->assertEquals('minimal', $analysis['risk_level']);
        $this->assertEmpty($analysis['alerts']);
        $this->assertEquals($this->user->id, $analysis['user_id']);
    }

    /** @test */
    public function it_detects_multiple_failed_login_attempts()
    {
        // Create multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            AuditLog::create([
                'user_id' => $this->user->id,
                'event_type' => 'authentication',
                'action' => 'failed_login',
                'ip_address' => '192.168.1.1',
                'created_at' => now()->subMinutes(30)
            ]);
        }

        $analysis = $this->securityService->analyzeUserActivity($this->user, '192.168.1.1');

        $this->assertGreaterThanOrEqual(30, $analysis['risk_score']);
        $this->assertContains('Multiple failed login attempts detected (6 attempts)', $analysis['alerts']);
    }

    /** @test */
    public function it_detects_unusual_activity_volume()
    {
        // Create baseline activity (simulate average)
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(5.0); // Average of 5 activities per day

        // Create high volume of recent activity
        for ($i = 0; $i < 20; $i++) {
            AuditLog::create([
                'user_id' => $this->user->id,
                'event_type' => 'model_updated',
                'action' => 'update',
                'created_at' => now()->subMinutes(30)
            ]);
        }

        $analysis = $this->securityService->analyzeUserActivity($this->user);

        $this->assertGreaterThanOrEqual(25, $analysis['risk_score']);
        $this->assertContains('Unusually high activity volume detected', $analysis['alerts']);
    }

    /** @test */
    public function it_detects_access_from_multiple_ips()
    {
        // Create activity from multiple IP addresses
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
    }

    /** @test */
    public function it_detects_bulk_operations()
    {
        // Create multiple bulk operations
        for ($i = 0; $i < 12; $i++) {
            AuditLog::create([
                'user_id' => $this->user->id,
                'event_type' => 'business_action',
                'action' => 'bulk_update',
                'additional_data' => ['bulk_operation' => true],
                'created_at' => now()->subMinutes(30)
            ]);
        }

        $analysis = $this->securityService->analyzeUserActivity($this->user);

        $this->assertGreaterThanOrEqual(15, $analysis['risk_score']);
        $this->assertContains('Multiple bulk operations detected', $analysis['alerts']);
    }

    /** @test */
    public function it_detects_privilege_escalation_attempts()
    {
        // Create privilege escalation attempts
        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'security_event',
            'action' => 'unauthorized_access',
            'created_at' => now()->subMinutes(30)
        ]);

        $analysis = $this->securityService->analyzeUserActivity($this->user);

        $this->assertGreaterThanOrEqual(40, $analysis['risk_score']);
        $this->assertContains('Privilege escalation attempts detected', $analysis['alerts']);
    }

    /** @test */
    public function it_can_analyze_ip_activity_with_no_risk()
    {
        $analysis = $this->securityService->analyzeIPActivity('192.168.1.1');

        $this->assertEquals(0, $analysis['risk_score']);
        $this->assertEquals('minimal', $analysis['risk_level']);
        $this->assertEmpty($analysis['alerts']);
        $this->assertEquals('192.168.1.1', $analysis['ip_address']);
    }

    /** @test */
    public function it_detects_multiple_users_from_same_ip()
    {
        $ipAddress = '192.168.1.1';
        
        // Create activity from multiple users on same IP
        for ($i = 0; $i < 7; $i++) {
            $user = User::factory()->create();
            AuditLog::create([
                'user_id' => $user->id,
                'event_type' => 'authentication',
                'action' => 'login',
                'ip_address' => $ipAddress,
                'created_at' => now()->subMinutes(30)
            ]);
        }

        $analysis = $this->securityService->analyzeIPActivity($ipAddress);

        $this->assertGreaterThanOrEqual(35, $analysis['risk_score']);
        $this->assertContains('Multiple users accessing from same IP address', $analysis['alerts']);
    }

    /** @test */
    public function it_detects_rapid_fire_requests()
    {
        $ipAddress = '192.168.1.1';
        
        // Create high frequency requests
        for ($i = 0; $i < 120; $i++) {
            AuditLog::create([
                'event_type' => 'system_event',
                'action' => 'api_request',
                'ip_address' => $ipAddress,
                'created_at' => now()->subMinutes(5)
            ]);
        }

        $analysis = $this->securityService->analyzeIPActivity($ipAddress);

        $this->assertGreaterThanOrEqual(30, $analysis['risk_score']);
        $this->assertContains('High frequency requests detected', $analysis['alerts']);
    }

    /** @test */
    public function it_detects_failed_authentication_from_ip()
    {
        $ipAddress = '192.168.1.1';
        
        // Create multiple failed authentication attempts
        for ($i = 0; $i < 12; $i++) {
            AuditLog::create([
                'event_type' => 'authentication',
                'action' => 'failed_login',
                'ip_address' => $ipAddress,
                'created_at' => now()->subMinutes(30)
            ]);
        }

        $analysis = $this->securityService->analyzeIPActivity($ipAddress);

        $this->assertGreaterThanOrEqual(40, $analysis['risk_score']);
        $this->assertContains('Multiple failed authentication attempts from IP', $analysis['alerts']);
    }

    /** @test */
    public function it_can_detect_system_anomalies()
    {
        // Create mass deletion pattern
        for ($i = 0; $i < 25; $i++) {
            AuditLog::create([
                'user_id' => $this->user->id,
                'event_type' => 'model_deleted',
                'action' => 'delete',
                'created_at' => now()->subMinutes(30)
            ]);
        }

        // Create bulk modifications
        for ($i = 0; $i < 7; $i++) {
            AuditLog::create([
                'user_id' => $this->user->id,
                'event_type' => 'model_updated',
                'action' => 'update',
                'additional_data' => ['bulk_operation' => true],
                'created_at' => now()->subMinutes(30)
            ]);
        }

        // Create unauthorized access attempts
        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'security_event',
            'action' => 'unauthorized_access',
            'created_at' => now()->subMinutes(30)
        ]);

        $anomalies = $this->securityService->detectSystemAnomalies();

        $this->assertCount(3, $anomalies);
        
        // Check mass deletion anomaly
        $massDeletion = collect($anomalies)->firstWhere('type', 'mass_deletion');
        $this->assertNotNull($massDeletion);
        $this->assertEquals('high', $massDeletion['severity']);
        $this->assertEquals(25, $massDeletion['count']);

        // Check bulk modifications anomaly
        $bulkMods = collect($anomalies)->firstWhere('type', 'bulk_modifications');
        $this->assertNotNull($bulkMods);
        $this->assertEquals('medium', $bulkMods['severity']);

        // Check unauthorized access anomaly
        $unauthorizedAccess = collect($anomalies)->firstWhere('type', 'unauthorized_access');
        $this->assertNotNull($unauthorizedAccess);
        $this->assertEquals('critical', $unauthorizedAccess['severity']);
    }

    /** @test */
    public function it_can_generate_security_alerts()
    {
        Notification::fake();

        $alertData = [
            'user_id' => $this->user->id,
            'risk_score' => 85,
            'risk_level' => 'high',
            'alerts' => ['Multiple failed login attempts'],
            'ip_address' => '192.168.1.1'
        ];

        $this->securityService->generateSecurityAlert($alertData);

        // Check that audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'security_event',
            'action' => 'security_alert_generated'
        ]);

        // Check that notifications were sent to admin users
        Notification::assertSentTo($this->adminUser, SecurityAlertNotification::class);
    }

    /** @test */
    public function it_correctly_maps_risk_levels()
    {
        $testCases = [
            [0, 'minimal'],
            [20, 'minimal'],
            [30, 'low'],
            [55, 'medium'],
            [80, 'high'],
            [95, 'critical']
        ];

        foreach ($testCases as [$score, $expectedLevel]) {
            $analysis = $this->securityService->analyzeUserActivity($this->user);
            
            // Use reflection to test private method
            $reflection = new \ReflectionClass($this->securityService);
            $method = $reflection->getMethod('getRiskLevel');
            $method->setAccessible(true);
            
            $actualLevel = $method->invoke($this->securityService, $score);
            $this->assertEquals($expectedLevel, $actualLevel, "Score {$score} should map to {$expectedLevel}");
        }
    }

    /** @test */
    public function it_caches_security_alerts_for_dashboard()
    {
        Cache::shouldReceive('get')
            ->once()
            ->with('security_alerts_' . now()->format('Y-m-d'), [])
            ->andReturn([]);

        Cache::shouldReceive('put')
            ->once()
            ->with('security_alerts_' . now()->format('Y-m-d'), \Mockery::type('array'), 86400);

        $alertData = [
            'user_id' => $this->user->id,
            'risk_score' => 75,
            'risk_level' => 'high',
            'alerts' => ['Test alert']
        ];

        $this->securityService->generateSecurityAlert($alertData);
    }

    /** @test */
    public function it_handles_time_windows_correctly()
    {
        // Create old activity (outside time window)
        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'authentication',
            'action' => 'failed_login',
            'created_at' => now()->subHours(2) // Outside 1-hour window
        ]);

        // Create recent activity (within time window)
        for ($i = 0; $i < 6; $i++) {
            AuditLog::create([
                'user_id' => $this->user->id,
                'event_type' => 'authentication',
                'action' => 'failed_login',
                'created_at' => now()->subMinutes(30) // Within 1-hour window
            ]);
        }

        $analysis = $this->securityService->analyzeUserActivity($this->user);

        // Should only count the 6 recent attempts, not the old one
        $this->assertGreaterThanOrEqual(30, $analysis['risk_score']);
        $this->assertContains('Multiple failed login attempts detected (6 attempts)', $analysis['alerts']);
    }
}