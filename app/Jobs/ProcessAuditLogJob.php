<?php

namespace App\Jobs;

use App\Services\AuditService;
use App\Services\AuditCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $auditData;
    protected bool $isBatch;
    protected bool $invalidateCache;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(array $auditData, bool $isBatch = false, bool $invalidateCache = true)
    {
        $this->auditData = $auditData;
        $this->isBatch = $isBatch;
        $this->invalidateCache = $invalidateCache;
        
        // Set queue priority based on event type
        $this->onQueue($this->determineQueue());
    }

    /**
     * Execute the job.
     */
    public function handle(AuditService $auditService, AuditCacheService $cacheService): void
    {
        try {
            if ($this->isBatch) {
                $results = $auditService->logBatch($this->auditData);
                $this->handleBatchResults($results, $cacheService);
            } else {
                $result = $auditService->log($this->auditData);
                $this->handleSingleResult($result, $cacheService);
            }
        } catch (\Exception $e) {
            Log::error('Audit log job failed', [
                'error' => $e->getMessage(),
                'data' => $this->auditData,
                'is_batch' => $this->isBatch,
                'attempt' => $this->attempts()
            ]);
            
            // Don't fail the job on first attempts, just log the error
            if ($this->attempts() < $this->tries) {
                $this->release(30); // Retry after 30 seconds
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Audit log job permanently failed', [
            'error' => $exception->getMessage(),
            'data' => $this->auditData,
            'is_batch' => $this->isBatch,
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Determine which queue to use based on event type
     */
    private function determineQueue(): string
    {
        if ($this->isBatch) {
            return 'audit-batch';
        }

        $eventType = $this->auditData['event_type'] ?? 'default';
        
        return match ($eventType) {
            'security_event' => 'audit-security',
            'authentication' => 'audit-auth',
            'financial_transaction' => 'audit-financial',
            default => 'audit-default'
        };
    }

    /**
     * Handle batch processing results
     */
    private function handleBatchResults(array $results, AuditCacheService $cacheService): void
    {
        if (!$this->invalidateCache) {
            return;
        }

        $userIds = [];
        $modelTypes = [];
        
        foreach ($this->auditData as $entry) {
            if (isset($entry['user_id'])) {
                $userIds[] = $entry['user_id'];
            }
            
            if (isset($entry['auditable_type'], $entry['auditable_id'])) {
                $modelTypes[] = [
                    'type' => $entry['auditable_type'],
                    'id' => $entry['auditable_id']
                ];
            }
        }

        // Invalidate relevant caches
        foreach (array_unique($userIds) as $userId) {
            $cacheService->invalidateUserCache($userId);
        }

        foreach ($modelTypes as $model) {
            $cacheService->invalidateModelCache($model['type'], $model['id']);
        }

        // Invalidate statistics caches for batch operations
        $cacheService->invalidateStatisticsCaches();
    }

    /**
     * Handle single audit log result
     */
    private function handleSingleResult($result, AuditCacheService $cacheService): void
    {
        if (!$this->invalidateCache || !$result) {
            return;
        }

        // Invalidate user-specific cache
        if (isset($this->auditData['user_id'])) {
            $cacheService->invalidateUserCache($this->auditData['user_id']);
        }

        // Invalidate model-specific cache
        if (isset($this->auditData['auditable_type'], $this->auditData['auditable_id'])) {
            $cacheService->invalidateModelCache(
                $this->auditData['auditable_type'],
                $this->auditData['auditable_id']
            );
        }

        // For security events, invalidate security caches immediately
        if ($this->auditData['event_type'] === 'security_event') {
            $cacheService->invalidateStatisticsCaches();
        }
    }
}