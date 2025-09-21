<?php

namespace App\Jobs;

use App\Services\AuditCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AuditCacheWarmupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 180; // 3 minutes

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('audit-cache');
    }

    /**
     * Execute the job.
     */
    public function handle(AuditCacheService $cacheService): void
    {
        try {
            Log::info('Starting audit cache warmup');
            
            $startTime = microtime(true);
            
            // Warm up frequently accessed caches
            $cacheService->warmUpCaches();
            
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            Log::info('Audit cache warmup completed', [
                'duration_seconds' => $duration
            ]);
            
        } catch (\Exception $e) {
            Log::error('Audit cache warmup failed', [
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);
            
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
        Log::error('Audit cache warmup job permanently failed', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}