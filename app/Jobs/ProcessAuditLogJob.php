<?php

namespace App\Jobs;

use App\Services\AuditService;
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

    /**
     * Create a new job instance.
     */
    public function __construct(array $auditData, bool $isBatch = false)
    {
        $this->auditData = $auditData;
        $this->isBatch = $isBatch;
    }

    /**
     * Execute the job.
     */
    public function handle(AuditService $auditService): void
    {
        try {
            if ($this->isBatch) {
                $auditService->logBatch($this->auditData);
            } else {
                $auditService->log($this->auditData);
            }
        } catch (\Exception $e) {
            Log::error('Audit log job failed', [
                'error' => $e->getMessage(),
                'data' => $this->auditData,
                'is_batch' => $this->isBatch
            ]);
            
            // Don't fail the job, just log the error
            // Audit logging should not break application flow
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
            'is_batch' => $this->isBatch
        ]);
    }
}