<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class UniversalAuditObserver
{
    protected AuditService $auditService;
    protected array $config;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->refreshConfig();
    }

    /**
     * Refresh the configuration (useful for testing)
     */
    public function refreshConfig(): void
    {
        $this->config = Config::get('audit', []);
    }

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        if (!$this->shouldAudit($model)) {
            return;
        }

        try {
            if ($this->shouldUseAsyncLogging()) {
                $this->auditService->logAsync([
                    'event_type' => 'model_created',
                    'auditable_type' => get_class($model),
                    'auditable_id' => $model->getKey(),
                    'action' => 'create',
                    'new_values' => $this->getFilteredAttributes($model),
                    'additional_data' => [
                        'model_name' => class_basename($model),
                        'created_at' => now()->toISOString()
                    ]
                ]);
            } else {
                $this->auditService->logModelCreated($model);
            }
        } catch (\Exception $e) {
            $this->logAuditError('created', $model, $e);
        }
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        if (!$this->shouldAudit($model)) {
            return;
        }

        try {
            // Capture the original values before the update
            $originalValues = $this->getOriginalValues($model);
            
            if (!empty($originalValues)) {
                if ($this->shouldUseAsyncLogging()) {
                    $this->auditService->logAsync([
                        'event_type' => 'model_updated',
                        'auditable_type' => get_class($model),
                        'auditable_id' => $model->getKey(),
                        'action' => 'update',
                        'old_values' => $originalValues,
                        'new_values' => $this->getFilteredAttributes($model),
                        'additional_data' => [
                            'model_name' => class_basename($model),
                            'changed_fields' => array_keys($originalValues),
                            'updated_at' => now()->toISOString()
                        ]
                    ]);
                } else {
                    $this->auditService->logModelUpdated($model, $originalValues);
                }
            }
        } catch (\Exception $e) {
            $this->logAuditError('updated', $model, $e);
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        if (!$this->shouldAudit($model)) {
            return;
        }

        try {
            if ($this->shouldUseAsyncLogging()) {
                $this->auditService->logAsync([
                    'event_type' => 'model_deleted',
                    'auditable_type' => get_class($model),
                    'auditable_id' => $model->getKey(),
                    'action' => 'delete',
                    'old_values' => $this->getFilteredAttributes($model),
                    'additional_data' => [
                        'model_name' => class_basename($model),
                        'deleted_at' => now()->toISOString()
                    ]
                ]);
            } else {
                $this->auditService->logModelDeleted($model);
            }
        } catch (\Exception $e) {
            $this->logAuditError('deleted', $model, $e);
        }
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored(Model $model): void
    {
        if (!$this->shouldAudit($model) || !$this->shouldLogRestorations()) {
            return;
        }

        try {
            // Log restoration as a special business action
            if ($this->shouldUseAsyncLogging()) {
                $this->auditService->logAsync([
                    'event_type' => 'business_action',
                    'auditable_type' => get_class($model),
                    'auditable_id' => $model->getKey(),
                    'action' => 'restore',
                    'new_values' => $this->getFilteredAttributes($model),
                    'additional_data' => [
                        'model_name' => class_basename($model),
                        'restored_at' => now()->toISOString(),
                    ]
                ]);
            } else {
                $this->auditService->logBusinessAction('restore', $model, [
                    'model_name' => class_basename($model),
                    'restored_at' => now()->toISOString(),
                ]);
            }
        } catch (\Exception $e) {
            $this->logAuditError('restored', $model, $e);
        }
    }

    /**
     * Handle the model "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        if (!$this->shouldAudit($model) || !$this->shouldLogForceDeletions()) {
            return;
        }

        try {
            // Log force deletion as a special security event
            if ($this->shouldUseAsyncLogging()) {
                $this->auditService->logAsync([
                    'event_type' => 'security_event',
                    'action' => 'force_delete',
                    'additional_data' => [
                        'model_type' => get_class($model),
                        'model_id' => $model->getKey(),
                        'model_name' => class_basename($model),
                        'severity' => 'high',
                        'force_deleted_at' => now()->toISOString(),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent()
                    ]
                ]);
            } else {
                $this->auditService->logSecurityEvent('force_delete', [
                    'model_type' => get_class($model),
                    'model_id' => $model->getKey(),
                    'model_name' => class_basename($model),
                    'severity' => 'high',
                    'force_deleted_at' => now()->toISOString(),
                ]);
            }
        } catch (\Exception $e) {
            $this->logAuditError('forceDeleted', $model, $e);
        }
    }

    /**
     * Determine if the model should be audited
     */
    public function shouldAudit(Model $model): bool
    {
        $modelClass = get_class($model);
        
        // Check if model is in auditable list from config
        $auditableModels = $this->config['auditable_models'] ?? [];
        if (!in_array($modelClass, $auditableModels)) {
            return false;
        }

        // Check if model has audit disabled via trait or property
        if (method_exists($model, 'shouldAudit') && !$model->shouldAudit()) {
            return false;
        }

        // Check if model has auditing disabled property
        if (property_exists($model, 'auditingDisabled') && $model->auditingDisabled === true) {
            return false;
        }

        return true;
    }

    /**
     * Get the original values for the model before update
     */
    public function getOriginalValues(Model $model): array
    {
        $original = $model->getOriginal();
        $changes = $model->getChanges();
        $excludedFields = $this->getExcludedFieldsForModel($model);
        
        // Only return original values for fields that actually changed
        $originalValues = [];
        foreach ($changes as $field => $newValue) {
            if (!in_array($field, $excludedFields)) {
                $originalValues[$field] = $original[$field] ?? null;
            }
        }

        return $originalValues;
    }

    /**
     * Get filtered attributes for a model (excluding sensitive fields)
     */
    public function getFilteredAttributes(Model $model): array
    {
        $attributes = $model->getAttributes();
        $excludedFields = $this->getExcludedFieldsForModel($model);
        
        foreach ($excludedFields as $field) {
            unset($attributes[$field]);
        }

        return $attributes;
    }

    /**
     * Get excluded fields for a specific model
     */
    public function getExcludedFieldsForModel(Model $model): array
    {
        $modelClass = get_class($model);
        $globalExcluded = $this->config['excluded_fields'] ?? [];
        
        // Check for model-specific configuration
        $modelConfigs = $this->config['model_configs'] ?? [];
        if (isset($modelConfigs[$modelClass]['excluded_fields'])) {
            return array_merge($globalExcluded, $modelConfigs[$modelClass]['excluded_fields']);
        }

        return $globalExcluded;
    }

    /**
     * Check if async logging should be used
     */
    public function shouldUseAsyncLogging(): bool
    {
        return $this->config['observer']['async_logging'] ?? true;
    }

    /**
     * Check if restorations should be logged
     */
    public function shouldLogRestorations(): bool
    {
        return $this->config['observer']['log_restorations'] ?? true;
    }

    /**
     * Check if force deletions should be logged
     */
    public function shouldLogForceDeletions(): bool
    {
        return $this->config['observer']['log_force_deletions'] ?? true;
    }

    /**
     * Log audit errors without breaking application flow
     */
    protected function logAuditError(string $event, Model $model, \Exception $e): void
    {
        // Only log if we should fail gracefully (default behavior)
        if ($this->shouldFailGracefully()) {
            Log::error('Universal audit observer failed', [
                'event' => $event,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } else {
            // Re-throw the exception if graceful failure is disabled
            throw $e;
        }
    }

    /**
     * Check if the observer should fail gracefully
     */
    protected function shouldFailGracefully(): bool
    {
        return $this->config['integration']['fail_gracefully'] ?? true;
    }

    /**
     * Log integration events if enabled
     */
    protected function logIntegrationEvent(string $event, Model $model, array $data = []): void
    {
        if ($this->config['integration']['log_integration_events'] ?? false) {
            Log::info('Universal audit observer integration event', array_merge([
                'event' => $event,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
            ], $data));
        }
    }

    /**
     * Get auditable models configuration
     */
    public function getAuditableModels(): array
    {
        return $this->config['auditable_models'] ?? [];
    }

    /**
     * Get excluded fields configuration
     */
    public function getExcludedFields(): array
    {
        return $this->config['excluded_fields'] ?? [];
    }

    /**
     * Check if a model is considered critical for extended retention
     */
    public function isCriticalModel(Model $model): bool
    {
        $criticalModels = $this->config['critical_models'] ?? [];
        $modelClass = get_class($model);
        
        // Check global critical models list
        if (in_array($modelClass, $criticalModels)) {
            return true;
        }
        
        // Check model-specific configuration
        $modelConfigs = $this->config['model_configs'] ?? [];
        if (isset($modelConfigs[$modelClass]['critical'])) {
            return $modelConfigs[$modelClass]['critical'];
        }
        
        return false;
    }

    /**
     * Check if a model has high priority for audit processing
     */
    public function isHighPriorityModel(Model $model): bool
    {
        $modelClass = get_class($model);
        $modelConfigs = $this->config['model_configs'] ?? [];
        
        return $modelConfigs[$modelClass]['high_priority'] ?? false;
    }

    /**
     * Get the configuration for the observer
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}