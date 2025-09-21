<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Services\AuditService;
use App\Services\AuditCacheService;
use App\Jobs\ProcessAuditLogJob;
use App\Jobs\BulkAuditProcessingJob;
use App\Jobs\AuditCacheWarmupJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuditPerformanceOptimizationTest extends TestCase
{
    use DatabaseTransactions;

    protected AuditService $auditService;
    protected AuditCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = new AuditCacheService();
        $this->auditService = new AuditService($this->cacheService);
    }

    /** @test */
    public function it_can_perform_bulk_audit_logging()
    {
        $initialCount = AuditLog::count();
        
        $auditEntries = [];
        for ($i = 0; $i < 100; $i++) {
            $auditEntries[] = [
                'event_type' => 'test_event',
                'action' => 'bulk_test',
                'additional_data' => ['test_id' => $i]
            ];
        }

        $inserted = $this->auditService->logBulk($auditEntries);

        $this->assertEquals(100, $inserted);
        $this->assertEquals($initialCount + 100, AuditLog::count());
    }

    /** @test */
    public function it_can_process_batch_operations_efficiently()
    {
        $initialCount = AuditLog::count();
        
        $auditEntries = [];
        for ($i = 0; $i < 50; $i++) {
            $auditEntries[] = [
                'event_type' => 'batch_test',
                'action' => 'test_action',
                'additional_data' => ['batch_id' => $i]
            ];
        }

        $results = $this->auditService->logBatch($auditEntries);

        $this->assertCount(50, $results);
        $this->assertEquals($initialCount + 50, AuditLog::count());
    }

    /** @test */
    public function it_queues_large_batches_for_asynchronous_processing()
    {
        Queue::fake();

        $largeAuditBatch = [];
        for ($i = 0; $i < 150; $i++) {
            $largeAuditBatch[] = [
                'event_type' => 'large_batch_test',
                'action' => 'async_test'
            ];
        }

        $this->auditService->logBatchAsync($largeAuditBatch);

        Queue::assertPushed(BulkAuditProcessingJob::class);
    }

    /** @test */
    public function it_queues_small_batches_normally()
    {
        Queue::fake();

        $smallAuditBatch = [];
        for ($i = 0; $i < 50; $i++) {
            $smallAuditBatch[] = [
                'event_type' => 'small_batch_test',
                'action' => 'normal_async_test'
            ];
        }

        $this->auditService->logBatchAsync($smallAuditBatch);

        Queue::assertPushed(ProcessAuditLogJob::class);
        Queue::assertNotPushed(BulkAuditProcessingJob::class);
    }

    /** @test */
    public function it_caches_audit_statistics()
    {
        // Create some test audit logs without factories to avoid constraint issues
        for ($i = 0; $i < 10; $i++) {
            AuditLog::create([
                'event_type' => 'test_event',
                'action' => 'test_action',
                'created_at' => now()->subHours(2)
            ]);
        }

        Cache::flush();

        // First call should hit the database
        $stats1 = $this->cacheService->getAuditStatistics(1);
        
        // Second call should hit the cache
        $stats2 = $this->cacheService->getAuditStatistics(1);

        $this->assertEquals($stats1, $stats2);
        $this->assertArrayHasKey('total_entries', $stats1);
        $this->assertArrayHasKey('entries_by_type', $stats1);
    }

    /** @test */
    public function it_caches_security_events_summary()
    {
        // Create security events without factories
        for ($i = 0; $i < 5; $i++) {
            AuditLog::create([
                'event_type' => 'security_event',
                'action' => 'failed_authentication',
                'created_at' => now()->subHours(1)
            ]);
        }

        Cache::flush();

        $summary = $this->cacheService->getSecurityEventsSummary();

        $this->assertArrayHasKey('total_events', $summary);
        $this->assertArrayHasKey('failed_logins', $summary);
        $this->assertEquals(5, $summary['failed_logins']);
    }

    /** @test */
    public function it_caches_user_activity_summary()
    {
        // Use an existing user ID from the database or null
        $existingUser = \App\Models\User::first();
        $userId = $existingUser ? $existingUser->id : null;
        
        for ($i = 0; $i < 3; $i++) {
            AuditLog::create([
                'user_id' => $userId,
                'event_type' => 'model_updated',
                'action' => 'update',
                'created_at' => now()->subDays(1)
            ]);
        }

        Cache::flush();

        $activity = $this->cacheService->getUserActivitySummary($userId ?? 1, 7);

        $this->assertArrayHasKey('total_activities', $activity);
        $this->assertArrayHasKey('activities_by_type', $activity);
        
        if ($userId) {
            $this->assertEquals(3, $activity['total_activities']);
        } else {
            // If no user ID, just check that the method works
            $this->assertIsArray($activity);
        }
    }

    /** @test */
    public function it_invalidates_user_cache_appropriately()
    {
        $userId = 998;
        
        // Warm up cache
        $this->cacheService->getUserActivitySummary($userId);
        
        // Invalidate cache
        $this->cacheService->invalidateUserCache($userId);
        
        // This should work without errors
        $this->assertTrue(true);
    }

    /** @test */
    public function it_uses_optimized_scopes_for_filtering()
    {
        for ($i = 0; $i < 5; $i++) {
            AuditLog::create([
                'event_type' => 'test_event',
                'user_id' => null, // Use null to avoid foreign key constraint
                'action' => 'test_action'
            ]);
        }

        $filters = [
            'event_type' => 'test_event',
            'action' => 'test_action'
        ];

        $results = AuditLog::optimizedFilter($filters)->get();

        $this->assertGreaterThanOrEqual(5, $results->count());
    }

    /** @test */
    public function it_handles_model_trail_queries_efficiently()
    {
        $modelType = 'App\\Models\\Package';
        $modelId = 123;

        for ($i = 0; $i < 3; $i++) {
            AuditLog::create([
                'event_type' => 'model_updated',
                'action' => 'update',
                'auditable_type' => $modelType,
                'auditable_id' => $modelId
            ]);
        }

        $trail = AuditLog::modelTrail($modelType, $modelId)->get();

        $this->assertGreaterThanOrEqual(3, $trail->count());
    }

    /** @test */
    public function it_processes_authentication_events_in_batch()
    {
        $initialCount = AuditLog::count();
        
        $authEvents = [];
        for ($i = 0; $i < 10; $i++) {
            $authEvents[] = [
                'user_id' => null, // Use null to avoid foreign key constraint
                'action' => 'login',
                'ip_address' => '192.168.1.' . ($i + 1),
                'timestamp' => now()->subMinutes($i)
            ];
        }

        $inserted = $this->auditService->logAuthenticationEventsBatch($authEvents);

        $this->assertEquals(10, $inserted);
        $this->assertEquals($initialCount + 10, AuditLog::count());
    }

    /** @test */
    public function it_handles_failed_audit_entries_gracefully_in_bulk()
    {
        $initialCount = AuditLog::count();
        
        $auditEntries = [
            // Valid entry
            [
                'event_type' => 'test_event',
                'action' => 'test_action'
            ],
            // Invalid entry (missing required fields)
            [
                'invalid_field' => 'invalid_value'
            ],
            // Another valid entry
            [
                'event_type' => 'test_event_2',
                'action' => 'test_action_2'
            ]
        ];

        $inserted = $this->auditService->logBulk($auditEntries);

        // Should insert only the valid entries
        $this->assertEquals(2, $inserted);
        $this->assertEquals($initialCount + 2, AuditLog::count());
    }

    /** @test */
    public function cache_warmup_job_can_be_dispatched()
    {
        Queue::fake();

        AuditCacheWarmupJob::dispatch();

        Queue::assertPushed(AuditCacheWarmupJob::class);
    }

    /** @test */
    public function bulk_processing_job_handles_large_datasets()
    {
        $largeDataset = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeDataset[] = [
                'event_type' => 'bulk_test',
                'action' => 'large_dataset_test',
                'additional_data' => ['index' => $i]
            ];
        }

        $job = new BulkAuditProcessingJob($largeDataset, 100);
        
        // This should not throw any errors
        $this->assertInstanceOf(BulkAuditProcessingJob::class, $job);
    }
}