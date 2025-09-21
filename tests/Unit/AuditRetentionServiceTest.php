<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AuditRetentionService;
use App\Models\AuditLog;
use App\Models\AuditSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;

class AuditRetentionServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $auditRetentionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditRetentionService = new AuditRetentionService();
        
        // Clear existing audit logs for clean tests
        AuditLog::query()->delete();
    }

    /** @test */
    public function it_can_get_storage_statistics()
    {
        // Create some test audit logs without user dependencies
        AuditLog::create([
            'event_type' => 'authentication',
            'action' => 'login',
            'created_at' => now()->subDays(10)
        ]);

        AuditLog::create([
            'event_type' => 'authentication',
            'action' => 'logout',
            'created_at' => now()->subDays(9)
        ]);

        AuditLog::create([
            'event_type' => 'model_created',
            'action' => 'create',
            'created_at' => now()->subDays(5)
        ]);

        $stats = $this->auditRetentionService->getStorageStatistics();

        $this->assertArrayHasKey('total_records', $stats);
        $this->assertArrayHasKey('records_by_type', $stats);
        $this->assertArrayHasKey('estimated_storage_mb', $stats);
        $this->assertEquals(3, $stats['total_records']);
        $this->assertEquals(2, $stats['records_by_type']['authentication']);
        $this->assertEquals(1, $stats['records_by_type']['model_created']);
    }

    /** @test */
    public function it_can_get_cleanup_preview()
    {
        // Create old logs that should be cleaned up (use specific dates that are definitely old enough)
        AuditLog::create([
            'event_type' => 'authentication',
            'action' => 'login',
            'created_at' => Carbon::parse('2023-01-01') // Definitely older than 365 days
        ]);

        AuditLog::create([
            'event_type' => 'model_created',
            'action' => 'create',
            'created_at' => Carbon::parse('2024-01-01') // Definitely older than 180 days
        ]);

        // Create recent logs that should not be cleaned up
        AuditLog::create([
            'event_type' => 'authentication',
            'action' => 'login',
            'created_at' => now()->subDays(100)
        ]);

        $preview = $this->auditRetentionService->getCleanupPreview();

        $this->assertArrayHasKey('total_to_delete', $preview);
        $this->assertArrayHasKey('by_event_type', $preview);
        $this->assertEquals(2, $preview['total_to_delete']);
        $this->assertEquals(1, $preview['by_event_type']['authentication']['count']);
        $this->assertEquals(1, $preview['by_event_type']['model_created']['count']);
    }

    /** @test */
    public function it_can_cleanup_event_type()
    {
        // Create old authentication logs
        for ($i = 0; $i < 3; $i++) {
            AuditLog::create([
                'event_type' => 'authentication',
                'action' => 'login',
                'created_at' => Carbon::parse('2023-01-01')
            ]);
        }

        // Create recent authentication logs
        for ($i = 0; $i < 2; $i++) {
            AuditLog::create([
                'event_type' => 'authentication',
                'action' => 'login',
                'created_at' => now()->subDays(100)
            ]);
        }

        // Create other event type logs
        for ($i = 0; $i < 2; $i++) {
            AuditLog::create([
                'event_type' => 'model_created',
                'action' => 'create',
                'created_at' => Carbon::parse('2023-01-01')
            ]);
        }

        $deleted = $this->auditRetentionService->cleanupEventType('authentication', 365);

        $this->assertEquals(3, $deleted);
        $this->assertEquals(2, AuditLog::where('event_type', 'authentication')->count());
        $this->assertEquals(2, AuditLog::where('event_type', 'model_created')->count());
    }

    /** @test */
    public function it_can_validate_retention_policy()
    {
        $validPolicy = [
            'authentication' => 365,
            'model_created' => 180,
            'financial_transaction' => 2555,
        ];

        $validation = $this->auditRetentionService->validateRetentionPolicy($validPolicy);

        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);

        $invalidPolicy = [
            'authentication' => 0, // Invalid: less than 1 day
            'model_created' => 'invalid', // Invalid: not numeric
        ];

        $validation = $this->auditRetentionService->validateRetentionPolicy($invalidPolicy);

        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
    }

    /** @test */
    public function it_can_get_retention_status()
    {
        // Create logs of different ages
        AuditLog::create([
            'event_type' => 'authentication',
            'action' => 'login',
            'created_at' => Carbon::parse('2023-01-01') // Expired
        ]);

        AuditLog::create([
            'event_type' => 'authentication',
            'action' => 'login',
            'created_at' => now()->subDays(100) // Current
        ]);

        $status = $this->auditRetentionService->getRetentionStatus();

        $this->assertArrayHasKey('total_records', $status);
        $this->assertArrayHasKey('by_event_type', $status);
        $this->assertArrayHasKey('cleanup_needed', $status);
        $this->assertTrue($status['cleanup_needed']);
        $this->assertEquals(1, $status['by_event_type']['authentication']['expired_records']);
    }

    /** @test */
    public function it_can_run_automated_cleanup()
    {
        // Create old logs for different event types
        AuditLog::create([
            'event_type' => 'authentication',
            'action' => 'login',
            'created_at' => Carbon::parse('2023-01-01')
        ]);

        AuditLog::create([
            'event_type' => 'model_created',
            'action' => 'create',
            'created_at' => Carbon::parse('2024-01-01')
        ]);

        AuditLog::create([
            'event_type' => 'system_event',
            'action' => 'backup',
            'created_at' => Carbon::parse('2024-01-01')
        ]);

        $results = $this->auditRetentionService->runAutomatedCleanup();

        $this->assertArrayHasKey('total_deleted', $results);
        $this->assertArrayHasKey('deleted_by_type', $results);
        $this->assertEquals(3, $results['total_deleted']);
        $this->assertEquals(1, $results['deleted_by_type']['authentication']);
        $this->assertEquals(1, $results['deleted_by_type']['model_created']);
        $this->assertEquals(1, $results['deleted_by_type']['system_event']);
    }

    /** @test */
    public function it_can_optimize_retention_policies()
    {
        // Create a reasonable number of logs for testing
        for ($i = 0; $i < 1000; $i++) {
            AuditLog::create([
                'event_type' => 'model_created',
                'action' => 'create',
                'created_at' => now()->subDays(rand(1, 100))
            ]);
        }

        for ($i = 0; $i < 50; $i++) {
            AuditLog::create([
                'event_type' => 'security_event',
                'action' => 'alert',
                'created_at' => now()->subDays(rand(1, 100))
            ]);
        }

        $optimizations = $this->auditRetentionService->optimizeRetentionPolicies();

        $this->assertArrayHasKey('optimizations', $optimizations);
        $this->assertArrayHasKey('recommended_policies', $optimizations);
        $this->assertArrayHasKey('current_policies', $optimizations);
    }
}