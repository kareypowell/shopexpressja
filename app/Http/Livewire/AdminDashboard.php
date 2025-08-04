<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Services\DashboardAnalyticsService;
use App\Services\DashboardCacheService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Package;
use Carbon\Carbon;

class AdminDashboard extends Component
{
    // Filter state management
    public string $dateRange = '30';
    public string $customStartDate = '';
    public string $customEndDate = '';
    public array $activeFilters = [];
    
    // Dashboard state
    public bool $isLoading = false;
    public bool $isRefreshing = false;
    public ?string $error = null;
    
    // Prevent recursive calls
    private bool $isUpdatingFilters = false;
    
    // System status
    public array $systemStatus = [];
    
    // Widget layout and customization - MEMORY OPTIMIZED
    public array $dashboardLayout = [
        'system_status' => ['enabled' => true, 'order' => 0, 'size' => 'full'],
        'metrics' => ['enabled' => true, 'order' => 1, 'size' => 'full'],
        'customer_analytics' => ['enabled' => true, 'order' => 2, 'size' => 'half'],
        'shipment_analytics' => ['enabled' => true, 'order' => 3, 'size' => 'half'],
        'financial_analytics' => ['enabled' => true, 'order' => 4, 'size' => 'full'],
    ];
    
    // Performance optimization - DISABLED to prevent memory issues
    public bool $lazyLoadComponents = false;
    public array $loadedComponents = [];
    
    // Services
    protected DashboardAnalyticsService $analyticsService;
    protected DashboardCacheService $cacheService;

    protected $listeners = [
        'filtersUpdated' => 'handleFiltersUpdated',
        'componentLoaded' => 'handleComponentLoaded',
        'refreshRequested' => 'refreshDashboard',
        'layoutUpdated' => 'handleLayoutUpdated',
    ];

    public function boot(DashboardAnalyticsService $analyticsService, DashboardCacheService $cacheService)
    {
        $this->analyticsService = $analyticsService;
        $this->cacheService = $cacheService;
    }

    public function mount()
    {
        try {
            // Set higher memory limit to prevent exhaustion
            ini_set('memory_limit', '512M');
            
            $this->initializeFilters();
            $this->loadDashboardLayout();
            $this->initializeLazyLoading();
            $this->loadSystemStatus();
            
        } catch (\Exception $e) {
            Log::error('AdminDashboard mount error: ' . $e->getMessage());
            $this->error = 'Failed to initialize dashboard. Please refresh the page.';
        }
    }

    /**
     * Initialize default filters and load from session if available
     */
    protected function initializeFilters(): void
    {
        // Load saved filters from session
        $savedFilters = Session::get('admin_dashboard_filters', []);
        
        $this->dateRange = $savedFilters['date_range'] ?? '30';
        $this->customStartDate = $savedFilters['custom_start'] ?? '';
        $this->customEndDate = $savedFilters['custom_end'] ?? '';
        
        $this->activeFilters = array_merge([
            'date_range' => $this->dateRange,
            'custom_start' => $this->customStartDate,
            'custom_end' => $this->customEndDate,
        ], $savedFilters);
    }

    /**
     * Load dashboard layout preferences
     */
    protected function loadDashboardLayout(): void
    {
        $savedLayout = Session::get('admin_dashboard_layout', []);
        
        if (!empty($savedLayout)) {
            $this->dashboardLayout = array_merge($this->dashboardLayout, $savedLayout);
        }
    }

    /**
     * Initialize lazy loading for performance optimization
     */
    protected function initializeLazyLoading(): void
    {
        // Disable lazy loading completely to prevent memory issues
        $this->lazyLoadComponents = false;
        
        // Load only enabled components
        $this->loadedComponents = array_keys(array_filter($this->dashboardLayout, function($config) {
            return $config['enabled'] ?? false;
        }));
    }

    /**
     * Load system status information
     */
    protected function loadSystemStatus(): void
    {
        try {
            $this->systemStatus = [
                'database' => $this->checkDatabaseConnection(),
                'cache' => $this->checkCacheConnection(),
                'memory_usage' => $this->getMemoryUsage(),
                'disk_space' => $this->getDiskSpace(),
                'last_updated' => now()->format('Y-m-d H:i:s'),
            ];
        } catch (\Exception $e) {
            Log::error('System status check failed: ' . $e->getMessage());
            $this->systemStatus = [
                'database' => ['status' => 'error', 'message' => 'Check failed'],
                'cache' => ['status' => 'error', 'message' => 'Check failed'],
                'memory_usage' => ['status' => 'unknown', 'usage' => 'N/A'],
                'disk_space' => ['status' => 'unknown', 'usage' => 'N/A'],
                'last_updated' => now()->format('Y-m-d H:i:s'),
            ];
        }
    }

    /**
     * Handle filter updates from child components
     */
    public function handleFiltersUpdated(array $filters): void
    {
        // Prevent recursive calls
        if ($this->isUpdatingFilters) {
            return;
        }
        
        try {
            $this->isUpdatingFilters = true;
            $this->isLoading = true;
            
            // Update local filter state
            $this->activeFilters = $filters;
            $this->dateRange = $filters['date_range'] ?? '30';
            $this->customStartDate = $filters['custom_start'] ?? '';
            $this->customEndDate = $filters['custom_end'] ?? '';
            
            // Save filters to session for persistence
            Session::put('admin_dashboard_filters', $this->activeFilters);
            
            // Propagate filters to all child components (this already emits the event)
            $this->propagateFiltersToComponents();
            
            $this->isLoading = false;
            
        } catch (\Exception $e) {
            Log::error('AdminDashboard filter update error: ' . $e->getMessage());
            $this->error = 'Failed to update filters. Please try again.';
            $this->isLoading = false;
        } finally {
            $this->isUpdatingFilters = false;
        }
    }

    /**
     * Propagate filter changes to all loaded child components
     */
    protected function propagateFiltersToComponents(): void
    {
        // Emit once to all components instead of in a loop
        if (!empty($this->loadedComponents)) {
            $this->emit('filtersUpdated', $this->activeFilters);
        }
    }

    /**
     * Handle component loaded event for lazy loading
     */
    public function handleComponentLoaded(string $componentName): void
    {
        if (!in_array($componentName, $this->loadedComponents)) {
            $this->loadedComponents[] = $componentName;
            
            // Send current filters to newly loaded component
            $this->emit('filtersUpdated', $this->activeFilters);
        }
    }

    /**
     * Refresh entire dashboard
     */
    public function refreshDashboard(): void
    {
        try {
            $this->isRefreshing = true;
            $this->error = null;
            
            // Clear relevant caches
            $this->clearDashboardCaches();
            
            // Reload system status
            $this->loadSystemStatus();
            
            // Emit refresh event to all components
            $this->emit('refreshDashboard');
            
            // Re-propagate filters
            $this->propagateFiltersToComponents();
            
            $this->isRefreshing = false;
            
            // Show success message
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Dashboard refreshed successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('AdminDashboard refresh error: ' . $e->getMessage());
            $this->error = 'Failed to refresh dashboard. Please try again.';
            $this->isRefreshing = false;
        }
    }

    /**
     * Clear dashboard-related caches
     */
    protected function clearDashboardCaches(): void
    {
        $cachePatterns = [
            'dashboard_metrics_*',
            'customer_analytics_*',
            'shipment_analytics_*',
            'financial_analytics_*',
            'dashboard_essential_metrics_*',
        ];
        
        foreach ($cachePatterns as $pattern) {
            $this->cacheService->flush($pattern);
        }
    }

    /**
     * Handle layout updates from customization
     */
    public function handleLayoutUpdated(array $layout): void
    {
        $this->dashboardLayout = $layout;
        Session::put('admin_dashboard_layout', $this->dashboardLayout);
        
        // Re-render dashboard with new layout
        $this->emit('layoutChanged', $this->dashboardLayout);
    }

    /**
     * Load a specific component (for lazy loading)
     */
    public function loadComponent(string $componentName): void
    {
        try {
            // Prevent loading too many components at once
            if (count($this->loadedComponents) >= 4) {
                $this->error = 'Maximum number of components loaded. Please refresh to load more.';
                return;
            }
            
            if (!in_array($componentName, $this->loadedComponents) && 
                isset($this->dashboardLayout[$componentName]) && 
                $this->dashboardLayout[$componentName]['enabled']) {
                
                $this->loadedComponents[] = $componentName;
                
                // Send current filters to newly loaded component
                $this->emit('filtersUpdated', $this->activeFilters);
            }
        } catch (\Exception $e) {
            Log::error('AdminDashboard loadComponent error: ' . $e->getMessage());
            $this->error = 'Failed to load component. Please try again.';
        }
    }

    /**
     * Check if a component should be loaded
     */
    public function shouldLoadComponent(string $componentName): bool
    {
        return in_array($componentName, $this->loadedComponents) && 
               isset($this->dashboardLayout[$componentName]) && 
               $this->dashboardLayout[$componentName]['enabled'];
    }

    /**
     * Get component configuration
     */
    public function getComponentConfig(string $componentName): array
    {
        return $this->dashboardLayout[$componentName] ?? [];
    }

    /**
     * Toggle component visibility
     */
    public function toggleComponent(string $componentName): void
    {
        if (isset($this->dashboardLayout[$componentName])) {
            $currentlyEnabled = $this->dashboardLayout[$componentName]['enabled'];
            $this->dashboardLayout[$componentName]['enabled'] = !$currentlyEnabled;
            
            // Add to loaded components if enabled, remove if disabled
            if ($this->dashboardLayout[$componentName]['enabled']) {
                if (!in_array($componentName, $this->loadedComponents)) {
                    $this->loadedComponents[] = $componentName;
                }
            } else {
                $this->loadedComponents = array_filter(
                    $this->loadedComponents, 
                    fn($comp) => $comp !== $componentName
                );
            }
            
            Session::put('admin_dashboard_layout', $this->dashboardLayout);
            
            $this->dispatchBrowserEvent('toastr:info', [
                'message' => ucwords(str_replace('_', ' ', $componentName)) . ' ' . 
                           ($this->dashboardLayout[$componentName]['enabled'] ? 'enabled' : 'disabled')
            ]);
        }
    }

    /**
     * Reset dashboard to default state
     */
    public function resetDashboard(): void
    {
        // Clear session data
        Session::forget(['admin_dashboard_filters', 'admin_dashboard_layout']);
        
        // Reset to defaults
        $this->initializeFilters();
        $this->loadDashboardLayout();
        $this->initializeLazyLoading();
        
        // Refresh dashboard
        $this->refreshDashboard();
        
        $this->dispatchBrowserEvent('toastr:success', [
            'message' => 'Dashboard reset to default settings'
        ]);
    }

    /**
     * Get sorted components for rendering
     */
    public function getSortedComponents(): array
    {
        $components = $this->dashboardLayout;
        
        // Sort by order
        uasort($components, function ($a, $b) {
            return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
        });
        
        return $components;
    }

    /**
     * Export dashboard data
     */
    public function exportDashboard(string $format = 'pdf'): void
    {
        try {
            $this->emit('exportRequested', [
                'format' => $format,
                'filters' => $this->activeFilters,
                'layout' => $this->dashboardLayout
            ]);
            
            $this->dispatchBrowserEvent('toastr:info', [
                'message' => 'Export started. Please wait...'
            ]);
            
        } catch (\Exception $e) {
            Log::error('AdminDashboard export error: ' . $e->getMessage());
            $this->error = 'Failed to export dashboard. Please try again.';
        }
    }

    // System status helper methods
    protected function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();
            $count = DB::table('users')->count();
            return [
                'status' => 'healthy',
                'message' => "Connected ({$count} users)",
                'response_time' => 'Fast'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Connection failed',
                'response_time' => 'N/A'
            ];
        }
    }

    protected function checkCacheConnection(): array
    {
        try {
            $testKey = 'dashboard_cache_test_' . time();
            Cache::put($testKey, 'test', 10);
            $result = Cache::get($testKey);
            Cache::forget($testKey);
            
            return [
                'status' => $result === 'test' ? 'healthy' : 'warning',
                'message' => $result === 'test' ? 'Working' : 'Partial failure',
                'driver' => config('cache.default')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache unavailable',
                'driver' => config('cache.default')
            ];
        }
    }

    protected function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        
        $usagePercent = $memoryLimitBytes > 0 ? ($memoryUsage / $memoryLimitBytes) * 100 : 0;
        
        return [
            'status' => $usagePercent > 80 ? 'warning' : ($usagePercent > 90 ? 'error' : 'healthy'),
            'usage' => $this->formatBytes($memoryUsage),
            'limit' => $memoryLimit,
            'percentage' => round($usagePercent, 1)
        ];
    }

    protected function getDiskSpace(): array
    {
        try {
            $bytes = disk_free_space(storage_path());
            $total = disk_total_space(storage_path());
            
            if ($bytes === false || $total === false) {
                return ['status' => 'unknown', 'usage' => 'N/A'];
            }
            
            $usagePercent = (($total - $bytes) / $total) * 100;
            
            return [
                'status' => $usagePercent > 90 ? 'error' : ($usagePercent > 80 ? 'warning' : 'healthy'),
                'free' => $this->formatBytes($bytes),
                'total' => $this->formatBytes($total),
                'percentage' => round($usagePercent, 1)
            ];
        } catch (\Exception $e) {
            return ['status' => 'unknown', 'usage' => 'N/A'];
        }
    }

    protected function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function render()
    {
        try {
            return view('livewire.admin-dashboard', [
                'sortedComponents' => $this->getSortedComponents(),
                'currentFilters' => $this->activeFilters,
                'user' => auth()->user(),
            ]);
        } catch (\Exception $e) {
            Log::error('AdminDashboard render error: ' . $e->getMessage());
            return view('livewire.admin-dashboard-error', [
                'error' => 'Dashboard temporarily unavailable. Please refresh the page.'
            ]);
        }
    }
}