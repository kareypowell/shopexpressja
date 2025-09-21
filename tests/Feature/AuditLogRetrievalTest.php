<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Package;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AuditLogRetrievalTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $adminUser;
    protected $superAdminUser;
    protected $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $customerRole = Role::factory()->create(['name' => 'customer']);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $superAdminRole = Role::factory()->create(['name' => 'superadmin']);
        
        $this->user = User::factory()->create(['role_id' => $customerRole->id]);
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->superAdminUser = User::factory()->create(['role_id' => $superAdminRole->id]);
        
        $this->auditService = app(AuditService::class);
        
        $this->createTestAuditData();
    }

    protected function createTestAuditData()
    {
        // Create various audit log entries for testing
        
        // Authentication events
        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'authentication',
            'action' => 'login',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'created_at' => now()->subDays(1)
        ]);

        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'authentication',
            'action' => 'logout',
            'ip_address' => '192.168.1.1',
            'created_at' => now()->subHours(2)
        ]);

        // Security events
        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'security_event',
            'action' => 'failed_authentication',
            'ip_address' => '192.168.1.100',
            'additional_data' => ['severity' => 'medium', 'attempt_count' => 3],
            'created_at' => now()->subHours(6)
        ]);

        // Business actions
        $package = Package::factory()->create(['user_id' => $this->user->id]);
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'event_type' => 'business_action',
            'action' => 'package_status_change',
            'auditable_type' => Package::class,
            'auditable_id' => $package->id,
            'old_values' => ['status' => 'processing'],
            'new_values' => ['status' => 'ready'],
            'additional_data' => ['package_tracking' => $package->tracking_number],
            'created_at' => now()->subHours(4)
        ]);

        // Financial transactions
        AuditLog::create([
            'user_id' => $this->user->id,
            'event_type' => 'financial_transaction',
            'action' => 'charge_applied',
            'new_values' => ['amount' => 75.50, 'currency' => 'USD'],
            'additional_data' => ['transaction_type' => 'freight_charge'],
            'created_at' => now()->subHours(8)
        ]);

        // System events
        AuditLog::create([
            'event_type' => 'system_event',
            'action' => 'backup_completed',
            'additional_data' => ['backup_size' => '2.5GB', 'duration' => '45 minutes'],
            'created_at' => now()->subDays(2)
        ]);
    }

    /** @test */
    public function superadmin_can_access_audit_log_management_page()
    {
        $this->actingAs($this->superAdminUser);
        
        $response = $this->get('/admin/audit-logs');
        
        $response->assertStatus(200);
        $response->assertSee('Audit Logs');
        $response->assertSee('Search audit logs');
    }

    /** @test */
    public function admin_can_access_audit_log_management_page()
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/admin/audit-logs');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function customer_cannot_access_audit_log_management_page()
    {
        $this->actingAs($this->user);
        
        $response = $this->get('/admin/audit-logs');
        
        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_retrieve_audit_logs_by_event_type()
    {
        $this->actingAs($this->superAdminUser);
        
        $authenticationLogs = AuditLog::where('event_type', 'authentication')->get();
        $securityLogs = AuditLog::where('event_type', 'security_event')->get();
        
        $this->assertGreaterThan(0, $authenticationLogs->count());
        $this->assertGreaterThan(0, $securityLogs->count());
        
        foreach ($authenticationLogs as $log) {
            $this->assertEquals('authentication', $log->event_type);
        }
    }

    /** @test */
    public function it_can_retrieve_audit_logs_by_action()
    {
        $this->actingAs($this->superAdminUser);
        
        $loginLogs = AuditLog::where('action', 'login')->get();
        $statusChangeLogs = AuditLog::where('action', 'package_status_change')->get();
        
        $this->assertGreaterThan(0, $loginLogs->count());
        $this->assertGreaterThan(0, $statusChangeLogs->count());
        
        foreach ($loginLogs as $log) {
            $this->assertEquals('login', $log->action);
        }
    }

    /** @test */
    public function it_can_retrieve_audit_logs_by_user()
    {
        $this->actingAs($this->superAdminUser);
        
        $userLogs = AuditLog::where('user_id', $this->user->id)->get();
        $adminLogs = AuditLog::where('user_id', $this->adminUser->id)->get();
        
        $this->assertGreaterThan(0, $userLogs->count());
        $this->assertGreaterThan(0, $adminLogs->count());
        
        foreach ($userLogs as $log) {
            $this->assertEquals($this->user->id, $log->user_id);
        }
    }

    /** @test */
    public function it_can_retrieve_audit_logs_by_date_range()
    {
        $this->actingAs($this->superAdminUser);
        
        $yesterday = now()->subDay()->startOfDay();
        $today = now()->endOfDay();
        
        $recentLogs = AuditLog::whereBetween('created_at', [$yesterday, $today])->get();
        $oldLogs = AuditLog::where('created_at', '<', $yesterday)->get();
        
        $this->assertGreaterThan(0, $recentLogs->count());
        $this->assertGreaterThan(0, $oldLogs->count());
        
        foreach ($recentLogs as $log) {
            $this->assertTrue($log->created_at->between($yesterday, $today));
        }
    }

    /** @test */
    public function it_can_retrieve_audit_logs_by_ip_address()
    {
        $this->actingAs($this->superAdminUser);
        
        $ipLogs = AuditLog::where('ip_address', '192.168.1.1')->get();
        $suspiciousIpLogs = AuditLog::where('ip_address', '192.168.1.100')->get();
        
        $this->assertGreaterThan(0, $ipLogs->count());
        $this->assertGreaterThan(0, $suspiciousIpLogs->count());
        
        foreach ($ipLogs as $log) {
            $this->assertEquals('192.168.1.1', $log->ip_address);
        }
    }

    /** @test */
    public function it_can_retrieve_audit_logs_by_auditable_model()
    {
        $this->actingAs($this->superAdminUser);
        
        $packageLogs = AuditLog::where('auditable_type', Package::class)->get();
        
        $this->assertGreaterThan(0, $packageLogs->count());
        
        foreach ($packageLogs as $log) {
            $this->assertEquals(Package::class, $log->auditable_type);
            $this->assertNotNull($log->auditable_id);
        }
    }

    /** @test */
    public function it_can_search_audit_logs_with_text_search()
    {
        $this->actingAs($this->superAdminUser);
        
        // Search in action field
        $loginLogs = AuditLog::where('action', 'like', '%login%')->get();
        $this->assertGreaterThan(0, $loginLogs->count());
        
        // Search in event_type field
        $authLogs = AuditLog::where('event_type', 'like', '%authentication%')->get();
        $this->assertGreaterThan(0, $authLogs->count());
    }

    /** @test */
    public function it_can_retrieve_audit_logs_with_user_relationships()
    {
        $this->actingAs($this->superAdminUser);
        
        $logsWithUsers = AuditLog::with('user')->whereNotNull('user_id')->get();
        
        $this->assertGreaterThan(0, $logsWithUsers->count());
        
        foreach ($logsWithUsers as $log) {
            $this->assertNotNull($log->user);
            $this->assertInstanceOf(User::class, $log->user);
        }
    }

    /** @test */
    public function it_can_retrieve_security_events_summary()
    {
        $summary = $this->auditService->getSecurityEventsSummary(24);
        
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_events', $summary);
        $this->assertArrayHasKey('failed_logins', $summary);
        $this->assertArrayHasKey('suspicious_activities', $summary);
        $this->assertArrayHasKey('unique_ips', $summary);
        $this->assertArrayHasKey('recent_events', $summary);
        
        $this->assertGreaterThan(0, $summary['total_events']);
    }

    /** @test */
    public function it_can_retrieve_audit_statistics()
    {
        $stats = $this->auditService->getAuditStatistics(7);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_entries', $stats);
        $this->assertArrayHasKey('entries_by_type', $stats);
        $this->assertArrayHasKey('entries_by_day', $stats);
        
        $this->assertGreaterThan(0, $stats['total_entries']);
        $this->assertIsArray($stats['entries_by_type']);
    }

    /** @test */
    public function it_can_retrieve_user_recent_ips()
    {
        $recentIPs = $this->auditService->getUserRecentIPs($this->user->id, 30);
        
        $this->assertIsArray($recentIPs);
        $this->assertContains('192.168.1.1', $recentIPs);
        $this->assertContains('192.168.1.100', $recentIPs);
    }

    /** @test */
    public function it_can_retrieve_user_recent_logins()
    {
        $recentLogins = $this->auditService->getUserRecentLogins($this->user->id, 1440); // 24 hours
        
        $this->assertIsArray($recentLogins);
        $this->assertGreaterThan(0, count($recentLogins));
    }

    /** @test */
    public function it_can_retrieve_last_login_time()
    {
        $lastLogin = $this->auditService->getLastLoginTime($this->user->id);
        
        $this->assertInstanceOf(Carbon::class, $lastLogin);
        $this->assertTrue($lastLogin->lessThan(now()));
    }

    /** @test */
    public function it_can_filter_audit_logs_by_multiple_criteria()
    {
        $this->actingAs($this->superAdminUser);
        
        $filteredLogs = AuditLog::where('user_id', $this->user->id)
            ->where('event_type', 'authentication')
            ->where('created_at', '>=', now()->subDays(2))
            ->get();
        
        $this->assertGreaterThan(0, $filteredLogs->count());
        
        foreach ($filteredLogs as $log) {
            $this->assertEquals($this->user->id, $log->user_id);
            $this->assertEquals('authentication', $log->event_type);
            $this->assertTrue($log->created_at->greaterThanOrEqualTo(now()->subDays(2)));
        }
    }

    /** @test */
    public function it_can_sort_audit_logs_by_different_fields()
    {
        $this->actingAs($this->superAdminUser);
        
        // Sort by created_at descending (newest first)
        $logsByDateDesc = AuditLog::orderBy('created_at', 'desc')->get();
        $this->assertTrue($logsByDateDesc->first()->created_at->greaterThanOrEqualTo($logsByDateDesc->last()->created_at));
        
        // Sort by event_type ascending
        $logsByTypeAsc = AuditLog::orderBy('event_type', 'asc')->get();
        $this->assertTrue($logsByTypeAsc->first()->event_type <= $logsByTypeAsc->last()->event_type);
    }

    /** @test */
    public function it_can_paginate_audit_logs()
    {
        $this->actingAs($this->superAdminUser);
        
        $paginatedLogs = AuditLog::paginate(3);
        
        $this->assertLessThanOrEqual(3, $paginatedLogs->count());
        $this->assertGreaterThan(0, $paginatedLogs->total());
    }

    /** @test */
    public function it_can_retrieve_audit_logs_for_specific_models()
    {
        $package = Package::first();
        
        $packageAuditLogs = AuditLog::where('auditable_type', Package::class)
            ->where('auditable_id', $package->id)
            ->get();
        
        $this->assertGreaterThan(0, $packageAuditLogs->count());
        
        foreach ($packageAuditLogs as $log) {
            $this->assertEquals(Package::class, $log->auditable_type);
            $this->assertEquals($package->id, $log->auditable_id);
        }
    }

    /** @test */
    public function it_can_search_in_json_fields()
    {
        $this->actingAs($this->superAdminUser);
        
        // Search in additional_data JSON field
        $logsWithSeverity = AuditLog::whereRaw("JSON_EXTRACT(additional_data, '$.severity') = ?", ['medium'])->get();
        $this->assertGreaterThan(0, $logsWithSeverity->count());
        
        // Search in new_values JSON field
        $logsWithAmount = AuditLog::whereRaw("JSON_EXTRACT(new_values, '$.amount') > ?", [50])->get();
        $this->assertGreaterThan(0, $logsWithAmount->count());
    }

    /** @test */
    public function it_handles_empty_search_results_gracefully()
    {
        $this->actingAs($this->superAdminUser);
        
        $nonExistentLogs = AuditLog::where('action', 'non_existent_action')->get();
        $this->assertEquals(0, $nonExistentLogs->count());
        
        $futureLogs = AuditLog::where('created_at', '>', now()->addDays(1))->get();
        $this->assertEquals(0, $futureLogs->count());
    }

    /** @test */
    public function it_can_retrieve_audit_logs_with_complex_queries()
    {
        $this->actingAs($this->superAdminUser);
        
        // Complex query: Authentication events from last 24 hours excluding system user
        $complexQuery = AuditLog::where('event_type', 'authentication')
            ->where('created_at', '>=', now()->subDay())
            ->whereNotNull('user_id')
            ->whereHas('user', function ($query) {
                $query->where('email', '!=', 'system@example.com');
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        
        foreach ($complexQuery as $log) {
            $this->assertEquals('authentication', $log->event_type);
            $this->assertNotNull($log->user_id);
            $this->assertTrue($log->created_at->greaterThanOrEqualTo(now()->subDay()));
        }
    }
}