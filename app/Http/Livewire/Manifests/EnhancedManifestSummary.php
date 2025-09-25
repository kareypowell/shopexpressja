<?php

namespace App\Http\Livewire\Manifests;

use App\Models\Manifest;
use App\Services\ManifestSummaryService;
use App\Services\ManifestSummaryCacheService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class EnhancedManifestSummary extends Component
{
    public int $manifestId;
    public array $summary = [];
    public string $manifestType = '';
    public bool $hasIncompleteData = false;
    
    // Enhanced error handling properties
    public bool $hasError = false;
    public string $errorMessage = '';
    public bool $isRetrying = false;

    protected $listeners = [
        'packageAdded' => 'refreshSummary',
        'packageRemoved' => 'refreshSummary',
        'packageUpdated' => 'refreshSummary',
        'packagesChanged' => 'refreshSummary',
    ];

    public function mount(Manifest $manifest = null)
    {
        if (!$manifest || !$manifest->id) {
            Log::error('EnhancedManifestSummary: Invalid manifest passed to mount', [
                'manifest' => $manifest ? $manifest->toArray() : null,
                'component' => 'EnhancedManifestSummary'
            ]);
            
            $this->hasError = true;
            $this->errorMessage = 'Invalid manifest data. Please refresh the page.';
            $this->manifestId = 0; // Set to 0 instead of null to prevent hydration issues
            return;
        }
        
        $this->manifestId = $manifest->id;
        
        Log::info('EnhancedManifestSummary mounted successfully', [
            'manifest_id' => $this->manifestId,
            'component' => 'EnhancedManifestSummary'
        ]);
        
        $this->calculateSummary();
    }

    public function hydrate()
    {
        // CRITICAL FIX: Ensure manifestId is never null
        if (!$this->manifestId) {
            // Try to get manifestId from the parent component or route
            $this->manifestId = request()->route('manifest') ?? request()->get('manifest_id');
            
            if (!$this->manifestId) {
                Log::error('EnhancedManifestSummary: manifestId is null during hydration', [
                    'route_params' => request()->route()->parameters ?? [],
                    'request_params' => request()->all(),
                    'component' => 'EnhancedManifestSummary'
                ]);
                
                // Set error state to prevent blank screen
                $this->hasError = true;
                $this->errorMessage = 'Manifest ID is missing. Please refresh the page.';
                return;
            }
        }
        
        // Ensure data persistence across Livewire requests
        if (empty($this->summary) && $this->manifestId) {
            $this->calculateSummary();
        }
    }

    protected function getManifest(): ?Manifest
    {
        if (!$this->manifestId) {
            Log::error('EnhancedManifestSummary: Attempting to get manifest with null ID', [
                'component' => 'EnhancedManifestSummary',
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
            return null;
        }
        
        return Manifest::find($this->manifestId);
    }

    public function refreshSummary()
    {
        Log::info('refreshSummary called', [
            'manifest_id' => $this->manifestId,
            'user_id' => auth()->id(),
            'component' => 'EnhancedManifestSummary'
        ]);
        
        try {
            $this->calculateSummary();
        } catch (\Exception $e) {
            Log::error('refreshSummary failed', [
                'manifest_id' => $this->manifestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->handleCalculationError($e);
        }
    }

    protected function calculateSummary()
    {
        // Reset error state at the beginning of calculation
        $this->hasError = false;
        $this->errorMessage = '';
        
        Log::info('calculateSummary started', [
            'manifest_id' => $this->manifestId,
            'component' => 'EnhancedManifestSummary'
        ]);
        
        try {
            $manifest = $this->getManifest();
            if (!$manifest) {
                throw new \Exception('Manifest not found');
            }

            $cacheService = app(ManifestSummaryCacheService::class);
            
            // Get cached display summary
            $displaySummary = $cacheService->getCachedDisplaySummary($manifest);
            
            // Extract manifest type
            $this->manifestType = $displaySummary['manifest_type'] ?? 'unknown';
            $this->hasIncompleteData = (bool) ($displaySummary['incomplete_data'] ?? false);
            
            // Format summary for the view
            $totalValue = $displaySummary['total_value'] ?? 0.0;
            
            // Clean the total_value if it's a formatted string (remove commas, etc.)
            if (is_string($totalValue)) {
                $totalValue = (float) str_replace([',', '$'], '', $totalValue);
            }
            
            $this->summary = [
                'package_count' => $displaySummary['package_count'] ?? 0,
                'total_value' => $totalValue,
                'incomplete_data' => $this->hasIncompleteData,
            ];

            // Add weight data for air manifests
            if ($this->manifestType === 'air' && isset($displaySummary['primary_metric'])) {
                $metric = $displaySummary['primary_metric'];
                $this->summary['weight'] = [
                    'lbs' => $metric['value'] ?? '0.0 lbs',
                    'kg' => $metric['secondary'] ?? '0.0 kg',
                ];
            }
            
            // Add volume data for sea manifests
            if ($this->manifestType === 'sea' && isset($displaySummary['primary_metric'])) {
                $metric = $displaySummary['primary_metric'];
                $this->summary['volume'] = $metric['value'] ?? '0.0 ft³';
            }
            
            Log::info('Manifest summary calculated successfully', [
                'manifest_id' => $this->manifestId,
                'manifest_type' => $this->manifestType,
                'package_count' => $this->summary['package_count'] ?? 0,
                'has_incomplete_data' => $this->hasIncompleteData,
            ]);
            
        } catch (\Exception $e) {
            $this->handleCalculationError($e);
        }
    }

    /**
     * Handle calculation errors and set appropriate error state
     */
    protected function handleCalculationError(\Exception $exception)
    {
        $this->hasError = true;
        $this->isRetrying = false;
        
        Log::error('Enhanced Manifest Summary calculation failed', [
            'component' => 'EnhancedManifestSummary',
            'manifest_id' => $this->manifestId,
            'error_message' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'user_id' => auth()->id(),
            'stack_trace' => $exception->getTraceAsString(),
        ]);
        
        $this->errorMessage = 'Unable to calculate summary at this time. Please try refreshing the page.';
        
        // Generate emergency fallback data
        $this->generateEmergencyFallbackData();
    }

    /**
     * Generate emergency fallback data when all else fails
     */
    protected function generateEmergencyFallbackData()
    {
        try {
            $this->manifestType = 'unknown';
            $this->hasIncompleteData = true;
            
            // Get basic package count directly from database
            $packageCount = 0;
            try {
                $manifest = $this->getManifest();
                if ($manifest) {
                    $packageCount = $manifest->packages()->count();
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get package count for fallback data', [
                    'manifest_id' => $this->manifestId,
                    'error' => $e->getMessage(),
                ]);
            }
            
            // Create minimal safe summary data
            $this->summary = [
                'package_count' => max(0, $packageCount),
                'total_value' => 0.0,
                'incomplete_data' => true,
            ];
            
        } catch (\Exception $e) {
            Log::critical('Failed to generate emergency fallback data', [
                'manifest_id' => $this->manifestId,
                'error' => $e->getMessage(),
            ]);
            
            // Absolute minimal fallback
            $this->manifestType = 'unknown';
            $this->hasIncompleteData = true;
            $this->summary = [
                'package_count' => 0,
                'total_value' => 0.0,
                'incomplete_data' => true,
            ];
        }
    }

    public function render()
    {
        // Add debug logging to help identify blank screen issues
        Log::info('EnhancedManifestSummary render called', [
            'manifest_id' => $this->manifestId,
            'has_error' => $this->hasError,
            'summary_keys' => array_keys($this->summary),
            'manifest_type' => $this->manifestType,
        ]);
        
        // CRITICAL: Prevent rendering with invalid manifestId
        if (!$this->manifestId || $this->manifestId <= 0) {
            Log::error('EnhancedManifestSummary: Attempting to render with invalid manifestId', [
                'manifest_id' => $this->manifestId,
                'component' => 'EnhancedManifestSummary'
            ]);
            
            return view('livewire.manifests.enhanced-manifest-summary-error', [
                'errorMessage' => 'Invalid manifest ID. Please refresh the page.'
            ]);
        }
        
        try {
            $manifest = $this->getManifest();
            if (!$manifest) {
                Log::error('EnhancedManifestSummary: Manifest not found', [
                    'manifest_id' => $this->manifestId,
                    'component' => 'EnhancedManifestSummary'
                ]);
                
                return view('livewire.manifests.enhanced-manifest-summary-error', [
                    'errorMessage' => 'Manifest not found. Please refresh the page.'
                ]);
            }
            
            return view('livewire.manifests.enhanced-manifest-summary', [
                'manifest' => $manifest
            ]);
        } catch (\Exception $e) {
            Log::error('EnhancedManifestSummary render failed', [
                'manifest_id' => $this->manifestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return a simple error view to prevent blank screen
            return view('livewire.manifests.enhanced-manifest-summary-error', [
                'errorMessage' => 'Unable to render manifest summary'
            ]);
        }
    }
}