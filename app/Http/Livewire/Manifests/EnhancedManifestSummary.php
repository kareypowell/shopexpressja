<?php

namespace App\Http\Livewire\Manifests;

use App\Models\Manifest;
use App\Services\ManifestSummaryService;
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
        $summaryService = app(ManifestSummaryService::class);
        
        // Get display-ready summary data
        $displaySummary = $summaryService->getDisplaySummary($this->manifest);
        
        $this->manifestType = $displaySummary['manifest_type'];
        $this->hasIncompleteData = $displaySummary['incomplete_data'];
        
        // Format summary for the view
        $this->summary = [
            'package_count' => $displaySummary['package_count'],
            'total_value' => (float) str_replace(',', '', $displaySummary['total_value']),
            'incomplete_data' => $displaySummary['incomplete_data'],
        ];

        // Add primary metric data based on manifest type
        if (isset($displaySummary['primary_metric'])) {
            $metric = $displaySummary['primary_metric'];
            
            if ($metric['type'] === 'weight') {
                $this->summary['weight'] = [
                    'lbs' => $metric['value'], // Already formatted with units
                    'kg' => $metric['secondary'], // Already formatted with units
                ];
            } elseif ($metric['type'] === 'volume') {
                $this->summary['volume'] = $metric['value']; // Already formatted with units
            }
        }
    }

    public function render()
    {
        return view('livewire.manifests.enhanced-manifest-summary');
    }
}