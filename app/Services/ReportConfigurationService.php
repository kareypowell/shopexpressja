<?php

namespace App\Services;

use App\Models\SavedReportFilter;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ReportConfigurationService
{
    /**
     * Create a new saved filter configuration
     */
    public function createSavedFilter(User $user, array $data): SavedReportFilter
    {
        $filter = SavedReportFilter::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'report_type' => $data['report_type'],
            'filter_config' => $data['filter_config'],
            'is_shared' => $data['is_shared'] ?? false,
            'shared_with_roles' => $data['shared_with_roles'] ?? []
        ]);

        // Clear user's saved filters cache
        Cache::forget("user_saved_filters_{$user->id}");

        Log::info('Saved report filter created', [
            'user_id' => $user->id,
            'filter_id' => $filter->id,
            'name' => $filter->name,
            'report_type' => $filter->report_type
        ]);

        return $filter;
    }

    /**
     * Update an existing saved filter
     */
    public function updateSavedFilter(SavedReportFilter $filter, array $data): SavedReportFilter
    {
        $originalData = $filter->toArray();
        
        $filter->update([
            'name' => $data['name'],
            'filter_config' => $data['filter_config'],
            'is_shared' => $data['is_shared'] ?? false,
            'shared_with_roles' => $data['shared_with_roles'] ?? []
        ]);

        // Clear related caches
        Cache::forget("user_saved_filters_{$filter->user_id}");
        if ($filter->is_shared) {
            Cache::forget("shared_filters_{$filter->report_type}");
        }

        Log::info('Saved report filter updated', [
            'filter_id' => $filter->id,
            'changes' => $filter->getChanges(),
            'original' => $originalData
        ]);

        return $filter;
    }

    /**
     * Delete a saved filter
     */
    public function deleteSavedFilter(SavedReportFilter $filter): bool
    {
        $userId = $filter->user_id;
        $reportType = $filter->report_type;
        $isShared = $filter->is_shared;
        
        $deleted = $filter->delete();

        if ($deleted) {
            // Clear related caches
            Cache::forget("user_saved_filters_{$userId}");
            if ($isShared) {
                Cache::forget("shared_filters_{$reportType}");
            }

            Log::info('Saved report filter deleted', [
                'filter_id' => $filter->id,
                'user_id' => $userId
            ]);
        }

        return $deleted;
    }

    /**
     * Get saved filters for a user
     */
    public function getUserSavedFilters(User $user, string $reportType = null)
    {
        $cacheKey = "user_saved_filters_{$user->id}" . ($reportType ? "_{$reportType}" : '');
        
        return Cache::remember($cacheKey, 3600, function () use ($user, $reportType) {
            $query = SavedReportFilter::where('user_id', $user->id);
            
            if ($reportType) {
                $query->where('report_type', $reportType);
            }
            
            return $query->orderBy('name')->get();
        });
    }

    /**
     * Get shared filters for a report type
     */
    public function getSharedFilters(string $reportType, User $user = null)
    {
        $cacheKey = "shared_filters_{$reportType}";
        
        return Cache::remember($cacheKey, 3600, function () use ($reportType, $user) {
            $query = SavedReportFilter::where('report_type', $reportType)
                                   ->where('is_shared', true)
                                   ->with('user:id,first_name,last_name'); // Eager load user relationship
            
            // Filter by user role if provided
            if ($user && $user->role) {
                $query->where(function ($q) use ($user) {
                    $q->whereJsonContains('shared_with_roles', $user->role->name)
                      ->orWhereJsonLength('shared_with_roles', 0);
                });
            }
            
            return $query->orderBy('name')->get();
        });
    }

    /**
     * Apply default filters from a template
     */
    public function applyTemplateDefaults(ReportTemplate $template, array $currentFilters = []): array
    {
        $defaults = $template->default_filters ?? [];
        
        // Merge template defaults with current filters, giving priority to current filters
        $mergedFilters = array_merge($defaults, $currentFilters);
        
        // Remove empty values
        return array_filter($mergedFilters, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * Validate filter configuration
     */
    public function validateFilterConfig(array $config, string $reportType): array
    {
        $validFilters = $this->getValidFiltersForType($reportType);
        $errors = [];

        foreach ($config as $key => $value) {
            if (!in_array($key, $validFilters)) {
                $errors[] = "Invalid filter '{$key}' for report type '{$reportType}'";
                continue;
            }

            // Validate specific filter values
            switch ($key) {
                case 'date_range':
                    if (!in_array($value, ['today', 'yesterday', 'last_7_days', 'last_30_days', 'this_month', 'last_month', 'this_year', 'custom'])) {
                        $errors[] = "Invalid date range value: {$value}";
                    }
                    break;
                    
                case 'manifest_type':
                    if (!in_array($value, ['all', 'air', 'sea'])) {
                        $errors[] = "Invalid manifest type value: {$value}";
                    }
                    break;
                    
                case 'start_date':
                case 'end_date':
                    if (!strtotime($value)) {
                        $errors[] = "Invalid date format for {$key}: {$value}";
                    }
                    break;
            }
        }

        return $errors;
    }

    /**
     * Get valid filters for a report type
     */
    protected function getValidFiltersForType(string $reportType): array
    {
        $commonFilters = ['date_range', 'start_date', 'end_date', 'office_id'];
        
        switch ($reportType) {
            case 'sales':
                return array_merge($commonFilters, ['manifest_type', 'customer_id', 'status']);
                
            case 'manifest':
                return array_merge($commonFilters, ['manifest_type', 'status']);
                
            case 'customer':
                return array_merge($commonFilters, ['customer_id', 'status']);
                
            case 'financial':
                return array_merge($commonFilters, ['manifest_type', 'customer_id']);
                
            default:
                return $commonFilters;
        }
    }

    /**
     * Create default filter configurations for new users
     */
    public function createDefaultFiltersForUser(User $user): void
    {
        $defaultConfigs = [
            [
                'name' => 'Last 30 Days Sales',
                'report_type' => 'sales',
                'filter_config' => [
                    'date_range' => 'last_30_days',
                    'manifest_type' => 'all'
                ]
            ],
            [
                'name' => 'This Month Manifests',
                'report_type' => 'manifest',
                'filter_config' => [
                    'date_range' => 'this_month',
                    'manifest_type' => 'all'
                ]
            ]
        ];

        foreach ($defaultConfigs as $config) {
            $this->createSavedFilter($user, $config);
        }
    }

    /**
     * Share a filter with specific roles
     */
    public function shareFilterWithRoles(SavedReportFilter $filter, array $roles): SavedReportFilter
    {
        $filter->update([
            'is_shared' => true,
            'shared_with_roles' => $roles
        ]);

        // Clear shared filters cache
        Cache::forget("shared_filters_{$filter->report_type}");

        Log::info('Report filter shared', [
            'filter_id' => $filter->id,
            'shared_with_roles' => $roles
        ]);

        return $filter;
    }

    /**
     * Duplicate a saved filter
     */
    public function duplicateFilter(SavedReportFilter $originalFilter, User $user, string $newName = null): SavedReportFilter
    {
        $newFilter = $originalFilter->replicate();
        $newFilter->user_id = $user->id;
        $newFilter->name = $newName ?? ($originalFilter->name . ' (Copy)');
        $newFilter->is_shared = false;
        $newFilter->shared_with_roles = [];
        $newFilter->save();

        // Clear user's saved filters cache
        Cache::forget("user_saved_filters_{$user->id}");

        Log::info('Report filter duplicated', [
            'original_filter_id' => $originalFilter->id,
            'new_filter_id' => $newFilter->id,
            'user_id' => $user->id
        ]);

        return $newFilter;
    }

    /**
     * Get filter usage statistics
     */
    public function getFilterUsageStats(SavedReportFilter $filter): array
    {
        // This would be implemented with actual usage tracking
        // For now, return placeholder data
        return [
            'total_uses' => 0,
            'last_used' => null,
            'users_count' => $filter->is_shared ? 0 : 1
        ];
    }

    /**
     * Clean up unused filters
     */
    public function cleanupUnusedFilters(int $daysUnused = 90): int
    {
        $cutoffDate = now()->subDays($daysUnused);
        
        // This would require usage tracking implementation
        // For now, just clean up filters that haven't been updated
        $deletedCount = SavedReportFilter::where('updated_at', '<', $cutoffDate)
                                       ->where('is_shared', false)
                                       ->delete();

        if ($deletedCount > 0) {
            Log::info('Cleaned up unused report filters', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate
            ]);
        }

        return $deletedCount;
    }
}