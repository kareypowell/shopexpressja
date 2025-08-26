<?php

namespace App\Http\Livewire\Manifests;

use App\Models\Manifest;
use App\Services\ManifestSummaryService;
use App\Services\ManifestSummaryCacheService;
use Livewire\Component;

class EnhancedManifestSummary extends Component
{
    public Manifest $manifest;
    public array $summary = [];
    public string $manifestType = '';
    public bool $hasIncompleteData = false;

    protected $listeners = [
        'packageAdded' => 'refreshSummary',
        'packageRemoved' => 'refreshSummary',
        'packageUpdated' => 'refreshSummary',
        'packagesChanged' => 'refreshSummary',
    ];

    public function mount(Manifest $manifest)
    {
        $this->manifest = $manifest;
        $this->calculateSummary();
    }

    public function refreshSummary()
    {
        // Refresh the manifest from database to get latest data
        $this->manifest = $this->manifest->fresh();
        $this->calculateSummary();
    }

    protected function calculateSummary()
    {
        try {
            $cacheService = app(ManifestSummaryCacheService::class);
            
            // Get cached display-ready summary data
            $displaySummary = $cacheService->getCachedDisplaySummary($this->manifest);
            
            $this->manifestType = $displaySummary['manifest_type'];
            $this->hasIncompleteData = $displaySummary['incomplete_data'];
            
            // Format summary for the view with validation
            $this->summary = [
                'package_count' => max(0, (int) $displaySummary['package_count']),
                'total_value' => max(0, (float) str_replace(',', '', $displaySummary['total_value'])),
                'incomplete_data' => (bool) $displaySummary['incomplete_data'],
            ];

            // Add primary metric data based on manifest type with validation
            if (isset($displaySummary['primary_metric'])) {
                $metric = $displaySummary['primary_metric'];
                
                if ($metric['type'] === 'weight') {
                    $this->summary['weight'] = [
                        'lbs' => $metric['value'] ?? '0.0 lbs',
                        'kg' => $metric['secondary'] ?? '0.0 kg',
                    ];
                } elseif ($metric['type'] === 'volume') {
                    $this->summary['volume'] = $metric['value'] ?? '0.0 ft³';
                }
            }
        } catch (\Exception $e) {
            // Log error and provide fallback data
            \Log::error('Failed to calculate manifest summary', [
                'manifest_id' => $this->manifest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Provide safe fallback data
            $this->manifestType = $this->manifest->type ?? 'unknown';
            $this->hasIncompleteData = true;
            $this->summary = [
                'package_count' => 0,
                'total_value' => 0.0,
                'incomplete_data' => true,
            ];
            
            // Add fallback metric based on manifest type
            if ($this->manifestType === 'air') {
                $this->summary['weight'] = [
                    'lbs' => '0.0 lbs',
                    'kg' => '0.0 kg',
                ];
            } elseif ($this->manifestType === 'sea') {
                $this->summary['volume'] = '0.0 ft³';
            }
            
            // Emit error event for user notification
            $this->dispatchBrowserEvent('toastr:warning', [
                'message' => 'Summary data temporarily unavailable. Please refresh the page.'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.manifests.enhanced-manifest-summary');
    }
}