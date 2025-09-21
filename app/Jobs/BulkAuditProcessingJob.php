<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Services\AuditService;
use App\Services\AuditCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkAuditProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $auditEntries;
    protected int $batchSize;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300; // 5 minutes for bulk operations

    /**
     * Create a new job instance.
     */
    public function __construct(array $auditEntries, int $batchSize = 100)
    {
        $this->auditEntries = $auditEntries;
        $this->batchSize = $batchSize;
        $this->onQueue('audit-bulk');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $auditService = app(AuditService::class);
            $cacheService = app(AuditCacheService::class);
            
            $chunks = array_chunk($this->auditEntries, $this->batchSize);
            $totalProcessed = 0;
            
            foreach ($chunks as $chunk) {
                $this->processBatch($chunk, $auditService);
                $totalProcessed += count($chunk);
            }

            // Invalidate all statistics caches after bulk processing
            $cacheService->invalidateStatisticsCaches();

            Log::info('Bulk audit processing completed', [
                'total_entries' => count($this->auditEntries),
                'processed' => $totalProcessed,
                'batch_size' => $this->batchSize
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk audit processing failed', [
                'error' => $e->getMessage(),
                'total_entries' => count($this->auditEntries),
                'batch_size' => $this->batchSize,
                'attempt' => $this->attempts()
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release(60); // Retry after 1 minute
            }
        }
    }

    /**
     * Process a batch of audit entries
     */
    private function processBatch(array $batch, AuditService $auditService): void
    {
        $insertData = [];
        
        foreach ($batch as $entry) {
            $validated = $this->validateAndPrepareEntry($entry);
            if ($validated) {
                $insertData[] = $validated;
            }
        }

        if (!empty($insertData)) {
            AuditLog::insert($insertData);
        }
    }

    /**
     * Validate and prepare audit entry for bulk insert
     */
    private function validateAndPrepareEntry(array $entry): ?array
    {
        try {
            // Required fields validation
            if (!isset($entry['event_type']) || !isset($entry['action'])) {
                return null;
            }

            return [
                'user_id' => $entry['user_id'] ?? null,
                'event_type' => $entry['event_type'],
                'auditable_type' => $entry['auditable_type'] ?? null,
                'auditable_id' => $entry['auditable_id'] ?? null,
                'action' => $entry['action'],
                'old_values' => isset($entry['old_values']) ? json_encode($entry['old_values']) : null,
                'new_values' => isset($entry['new_values']) ? json_encode($entry['new_values']) : null,
                'url' => $entry['url'] ?? null,
                'ip_address' => $entry['ip_address'] ?? null,
                'user_agent' => $entry['user_agent'] ?? null,
                'additional_data' => isset($entry['additional_data']) ? json_encode($entry['additional_data']) : null,
                'created_at' => $entry['created_at'] ?? now(),
            ];
        } catch (\Exception $e) {
            Log::warning('Invalid audit entry in bulk processing', [
                'entry' => $entry,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk audit processing job permanently failed', [
            'error' => $exception->getMessage(),
            'total_entries' => count($this->auditEntries),
            'batch_size' => $this->batchSize,
            'attempts' => $this->attempts()
        ]);
    }
}