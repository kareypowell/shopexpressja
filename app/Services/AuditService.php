<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditCacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AuditService
{
    protected ?AuditCacheService $cacheService = null;

    public function __construct(?AuditCacheService $cacheService = null)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get cache service instance
     */
    protected function getCacheService(): AuditCacheService
    {
        if (!$this->cacheService) {
            $this->cacheService = app(AuditCacheService::class);
        }
        
        return $this->cacheService;
    }
    /**
     * Create a standardized audit log entry
     */
    public function log(array $data): ?AuditLog
    {
        try {
            // Validate required fields
            $validated = $this->validateAuditData($data);
            
            // Add system context if not provided
            $validated = $this->addSystemContext($validated);
            
            return AuditLog::create($validated);
        } catch (\Exception $e) {
            // Log the error but don't break application flow
            Log::error('Audit logging failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return null;
        }
    }

    /**
     * Log model creation events
     */
    public function logModelCreated(Model $model, ?User $user = null): ?AuditLog
    {
        return $this->log([
            'user_id' => $user->id ?? Auth::id(),
            'event_type' => 'model_created',
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'action' => 'create',
            'new_values' => $this->getModelAttributes($model),
            'additional_data' => [
                'model_name' => class_basename($model),
                'created_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Log model update events
     */
    public function logModelUpdated(Model $model, array $oldValues, ?User $user = null): ?AuditLog
    {
        // Filter sensitive fields from both old and new values
        $filteredOldValues = $this->filterSensitiveFields($oldValues);
        $newValues = $this->getModelAttributes($model);
        $changes = $this->getChangedAttributes($filteredOldValues, $newValues);
        
        if (empty($changes)) {
            return null; // No actual changes to log
        }

        return $this->log([
            'user_id' => $user->id ?? Auth::id(),
            'event_type' => 'model_updated',
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'action' => 'update',
            'old_values' => $filteredOldValues,
            'new_values' => $newValues,
            'additional_data' => [
                'model_name' => class_basename($model),
                'changed_fields' => array_keys($changes),
                'updated_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Log model deletion events
     */
    public function logModelDeleted(Model $model, ?User $user = null): ?AuditLog
    {
        return $this->log([
            'user_id' => $user->id ?? Auth::id(),
            'event_type' => 'model_deleted',
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'action' => 'delete',
            'old_values' => $this->getModelAttributes($model),
            'additional_data' => [
                'model_name' => class_basename($model),
                'deleted_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Log authentication events
     */
    public function logAuthentication(string $action, ?User $user = null, array $additionalData = []): ?AuditLog
    {
        return $this->log([
            'user_id' => $user->id,
            'event_type' => 'authentication',
            'action' => $action,
            'additional_data' => array_merge([
                'timestamp' => now()->toISOString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip()
            ], $additionalData)
        ]);
    }

    /**
     * Log authorization events (role changes, permissions)
     */
    public function logAuthorization(string $action, ?User $user = null, array $oldValues = [], array $newValues = []): ?AuditLog
    {
        return $this->log([
            'user_id' => $user->id ?? Auth::id(),
            'event_type' => 'authorization',
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'additional_data' => [
                'timestamp' => now()->toISOString(),
                'performed_by' => Auth::user()->name ?? 'System'
            ]
        ]);
    }

    /**
     * Log business actions (consolidation, manifest operations, etc.)
     */
    public function logBusinessAction(string $action, ?Model $model = null, array $additionalData = []): ?AuditLog
    {
        return $this->log([
            'user_id' => Auth::id(),
            'event_type' => 'business_action',
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model ? $model->getKey() : null,
            'action' => $action,
            'additional_data' => array_merge([
                'timestamp' => now()->toISOString(),
                'performed_by' => Auth::user()->name ?? 'System'
            ], $additionalData)
        ]);
    }

    /**
     * Log financial transactions
     */
    public function logFinancialTransaction(string $action, array $transactionData, ?User $user = null): ?AuditLog
    {
        return $this->log([
            'user_id' => $user->id ?? Auth::id(),
            'event_type' => 'financial_transaction',
            'action' => $action,
            'new_values' => $transactionData,
            'additional_data' => [
                'timestamp' => now()->toISOString(),
                'amount' => $transactionData['amount'] ?? null,
                'currency' => $transactionData['currency'] ?? 'USD',
                'transaction_type' => $transactionData['type'] ?? null
            ]
        ]);
    }

    /**
     * Log security events
     */
    public function logSecurityEvent(string $action, array $eventData = []): ?AuditLog
    {
        // Ensure severity is set with a proper default
        $severity = $eventData['severity'] ?? 'medium';
        
        return $this->log([
            'user_id' => Auth::id(),
            'event_type' => 'security_event',
            'action' => $action,
            'additional_data' => array_merge([
                'timestamp' => now()->toISOString(),
                'severity' => $severity,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'risk_level' => $this->mapSeverityToRiskLevel($severity)
            ], $eventData)
        ]);
    }

    /**
     * Map severity to risk level for consistency
     */
    private function mapSeverityToRiskLevel(string $severity): string
    {
        switch ($severity) {
            case 'critical':
                return 'Critical';
            case 'high':
                return 'High';
            case 'medium':
                return 'Medium';
            case 'low':
                return 'Low';
            default:
                return 'Medium';
        }
    }

    /**
     * Log system events
     */
    public function logSystemEvent(string $action, array $eventData = []): ?AuditLog
    {
        return $this->log([
            'event_type' => 'system_event',
            'action' => $action,
            'additional_data' => array_merge([
                'timestamp' => now()->toISOString(),
                'system_user' => 'System'
            ], $eventData)
        ]);
    }

    /**
     * Batch audit operations for performance
     */
    public function logBatch(array $auditEntries): array
    {
        $results = [];
        $batchSize = 50; // Process in smaller batches for memory efficiency
        
        $chunks = array_chunk($auditEntries, $batchSize);
        
        foreach ($chunks as $chunk) {
            try {
                // Process each entry in the chunk without wrapping in transaction
                // since individual log() calls may already handle transactions
                foreach ($chunk as $entry) {
                    $results[] = $this->log($entry);
                }
            } catch (\Exception $e) {
                Log::error('Batch audit logging failed for chunk', [
                    'error' => $e->getMessage(),
                    'chunk_size' => count($chunk)
                ]);
                
                // Continue with other chunks even if one fails
                foreach ($chunk as $entry) {
                    $results[] = null;
                }
            }
        }
        
        return $results;
    }

    /**
     * High-performance bulk insert for large datasets
     */
    public function logBulk(array $auditEntries): int
    {
        if (empty($auditEntries)) {
            return 0;
        }

        try {
            $insertData = [];
            
            foreach ($auditEntries as $entry) {
                $validated = $this->validateAndPrepareForBulkInsert($entry);
                if ($validated) {
                    $insertData[] = $validated;
                }
            }

            if (empty($insertData)) {
                return 0;
            }

            // Use chunked bulk insert for very large datasets
            $chunks = array_chunk($insertData, 500);
            $totalInserted = 0;

            foreach ($chunks as $chunk) {
                AuditLog::insert($chunk);
                $totalInserted += count($chunk);
            }

            return $totalInserted;

        } catch (\Exception $e) {
            Log::error('Bulk audit logging failed', [
                'error' => $e->getMessage(),
                'total_entries' => count($auditEntries)
            ]);
            return 0;
        }
    }

    /**
     * Queue audit logging for asynchronous processing
     */
    public function logAsync(array $data): void
    {
        \App\Jobs\ProcessAuditLogJob::dispatch($data, false);
    }

    /**
     * Batch queue processing for multiple audit entries
     */
    public function logBatchAsync(array $auditEntries): void
    {
        if (count($auditEntries) > 100) {
            // Use bulk processing job for large batches
            \App\Jobs\BulkAuditProcessingJob::dispatch($auditEntries);
        } else {
            \App\Jobs\ProcessAuditLogJob::dispatch($auditEntries, true);
        }
    }

    /**
     * Optimized batch processing for model events
     */
    public function logModelEventsBatch(array $modelEvents): int
    {
        $auditEntries = [];
        
        foreach ($modelEvents as $event) {
            $entry = $this->prepareModelEventEntry($event);
            if ($entry) {
                $auditEntries[] = $entry;
            }
        }

        return $this->logBulk($auditEntries);
    }

    /**
     * Batch log authentication events (useful for import/migration scenarios)
     */
    public function logAuthenticationEventsBatch(array $authEvents): int
    {
        $auditEntries = [];
        
        foreach ($authEvents as $event) {
            $auditEntries[] = [
                'user_id' => $event['user_id'] ?? null,
                'event_type' => 'authentication',
                'action' => $event['action'],
                'ip_address' => $event['ip_address'] ?? null,
                'user_agent' => $event['user_agent'] ?? null,
                'created_at' => $event['timestamp'] ?? now(),
                'additional_data' => $event['additional_data'] ?? []
            ];
        }

        return $this->logBulk($auditEntries);
    }

    /**
     * Helper method for package status changes
     */
    public function logPackageStatusChange(Model $package, string $oldStatus, string $newStatus, ?User $user = null): ?AuditLog
    {
        return $this->logBusinessAction('package_status_change', $package, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'package_tracking' => $package->tracking_number ?? null,
            'customer_id' => $package->user_id ?? null
        ]);
    }

    /**
     * Helper method for manifest operations
     */
    public function logManifestOperation(string $operation, Model $manifest, array $additionalData = []): ?AuditLog
    {
        return $this->logBusinessAction("manifest_{$operation}", $manifest, array_merge([
            'manifest_number' => $manifest->manifest_number ?? null,
            'manifest_type' => $manifest->type ?? null,
            'package_count' => $manifest->packages()->count() ?? 0
        ], $additionalData));
    }

    /**
     * Helper method for consolidation operations
     */
    public function logConsolidationOperation(string $operation, array $packageIds, ?int $consolidatedPackageId = null): ?AuditLog
    {
        return $this->logBusinessAction("package_{$operation}", null, [
            'package_ids' => $packageIds,
            'consolidated_package_id' => $consolidatedPackageId,
            'package_count' => count($packageIds)
        ]);
    }

    /**
     * Validate audit data structure
     */
    private function validateAuditData(array $data): array
    {
        // Required fields validation
        if (!isset($data['event_type']) || !isset($data['action'])) {
            throw new \InvalidArgumentException('event_type and action are required fields');
        }

        // Ensure JSON fields are properly formatted
        if (isset($data['old_values']) && !is_array($data['old_values'])) {
            $data['old_values'] = json_decode($data['old_values'], true);
        }

        if (isset($data['new_values']) && !is_array($data['new_values'])) {
            $data['new_values'] = json_decode($data['new_values'], true);
        }

        if (isset($data['additional_data']) && !is_array($data['additional_data'])) {
            $data['additional_data'] = json_decode($data['additional_data'], true);
        }

        return $data;
    }

    /**
     * Add system context to audit data
     */
    private function addSystemContext(array $data): array
    {
        $request = request();
        
        if ($request) {
            $data['url'] = $data['url'] ?? $request->fullUrl();
            $data['ip_address'] = $data['ip_address'] ?? $request->ip();
            $data['user_agent'] = $data['user_agent'] ?? $request->userAgent();
        }

        return $data;
    }

    /**
     * Get model attributes for audit logging
     */
    private function getModelAttributes(Model $model): array
    {
        $attributes = $model->getAttributes();
        return $this->filterSensitiveFields($attributes);
    }

    /**
     * Filter sensitive fields from attributes array
     */
    private function filterSensitiveFields(array $attributes): array
    {
        // Remove sensitive fields
        $sensitiveFields = ['password', 'remember_token', 'api_token'];
        foreach ($sensitiveFields as $field) {
            unset($attributes[$field]);
        }

        return $attributes;
    }

    /**
     * Get changed attributes between old and new values
     */
    private function getChangedAttributes(array $oldValues, array $newValues): array
    {
        $changes = [];
        
        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        return $changes;
    }

    /**
     * Log authentication events with enhanced context
     */
    public function logAuthenticationEvent(string $action, ?User $user = null, array $additionalData = []): ?AuditLog
    {
        return $this->log([
            'user_id' => $user->id ?? null,
            'event_type' => 'authentication',
            'action' => $action,
            'additional_data' => array_merge([
                'timestamp' => now()->toISOString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
                'session_id' => session()->getId()
            ], $additionalData)
        ]);
    }

    /**
     * Get user's recent IP addresses
     */
    public function getUserRecentIPs(int $userId, int $days = 30): array
    {
        return AuditLog::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('ip_address')
            ->distinct('ip_address')
            ->pluck('ip_address')
            ->toArray();
    }

    /**
     * Get user's recent login times
     */
    public function getUserRecentLogins(int $userId, int $minutes = 60): array
    {
        return AuditLog::where('user_id', $userId)
            ->where('event_type', 'authentication')
            ->where('action', 'login')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get last login time for user
     */
    public function getLastLoginTime(int $userId): ?Carbon
    {
        $lastLogin = AuditLog::where('user_id', $userId)
            ->where('event_type', 'authentication')
            ->where('action', 'login')
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastLogin ? $lastLogin->created_at : null;
    }

    /**
     * Get security events summary for dashboard
     */
    public function getSecurityEventsSummary(int $hours = 24): array
    {
        $since = now()->subHours($hours);
        
        $events = AuditLog::where('event_type', 'security_event')
            ->where('created_at', '>=', $since)
            ->get();

        return [
            'total_events' => $events->count(),
            'failed_logins' => $events->where('action', 'failed_authentication')->count(),
            'suspicious_activities' => $events->where('action', 'suspicious_activity_detected')->count(),
            'security_alerts' => $events->where('action', 'security_alert_generated')->count(),
            'unique_ips' => $events->pluck('ip_address')->filter()->unique()->count(),
            'events_by_severity' => $events->groupBy('additional_data.severity')->map->count(),
            'recent_events' => $events->sortByDesc('created_at')->take(10)->values()
        ];
    }

    /**
     * Get audit statistics for performance monitoring
     */
    public function getAuditStatistics(int $days = 7): array
    {
        $since = now()->subDays($days);
        
        return [
            'total_entries' => AuditLog::where('created_at', '>=', $since)->count(),
            'entries_by_type' => AuditLog::where('created_at', '>=', $since)
                ->groupBy('event_type')
                ->selectRaw('event_type, count(*) as count')
                ->pluck('count', 'event_type'),
            'entries_by_day' => AuditLog::where('created_at', '>=', $since)
                ->groupBy(\DB::raw('DATE(created_at)'))
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->pluck('count', 'date'),
            'top_users' => AuditLog::where('created_at', '>=', $since)
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->selectRaw('user_id, count(*) as count')
                ->orderByDesc('count')
                ->limit(10)
                ->with('user:id,first_name,last_name,email')
                ->get()
        ];
    }

    /**
     * Validate and prepare entry for bulk insert
     */
    private function validateAndPrepareForBulkInsert(array $entry): ?array
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
     * Prepare model event entry for batch processing
     */
    private function prepareModelEventEntry(array $event): ?array
    {
        if (!isset($event['model'], $event['action'])) {
            return null;
        }

        $model = $event['model'];
        
        return [
            'user_id' => $event['user_id'] ?? auth()->id(),
            'event_type' => 'model_' . $event['action'],
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'action' => $event['action'],
            'old_values' => $event['old_values'] ?? null,
            'new_values' => $event['new_values'] ?? $this->getModelAttributes($model),
            'created_at' => $event['timestamp'] ?? now(),
            'additional_data' => [
                'model_name' => class_basename($model),
                'batch_processed' => true
            ]
        ];
    }
}