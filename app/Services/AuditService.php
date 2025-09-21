<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AuditService
{
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
            'auditable_id' => $model->getKey(),
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
        return $this->log([
            'user_id' => Auth::id(),
            'event_type' => 'security_event',
            'action' => $action,
            'additional_data' => array_merge([
                'timestamp' => now()->toISOString(),
                'severity' => $eventData['severity'] ?? 'medium',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ], $eventData)
        ]);
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
        
        foreach ($auditEntries as $entry) {
            $results[] = $this->log($entry);
        }
        
        return $results;
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
        \App\Jobs\ProcessAuditLogJob::dispatch($auditEntries, true);
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
                ->with('user:id,name,email')
                ->get()
        ];
    }
}