<div class="manifest-summary bg-base-100 p-6 rounded-lg shadow-sm" wire:poll.30s="refreshSummary">
    <h3 class="text-lg font-semibold mb-4" id="manifest-summary-title">Manifest Summary</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4" role="region" aria-labelledby="manifest-summary-title">
        <!-- Package Count -->
        <div class="stat bg-base-200 rounded-lg p-4">
            <div class="stat-title text-base-content/70">Total Packages</div>
            <div class="stat-value text-2xl font-bold text-primary" aria-label="Total packages: {{ $summary['package_count'] ?? 0 }}">
                {{ $summary['package_count'] ?? 0 }}
            </div>
        </div>
        
        <!-- Conditional Metrics Based on Manifest Type -->
        @if($manifestType === 'air')
            <!-- Weight Display for Air Manifests -->
            <div class="stat bg-base-200 rounded-lg p-4">
                <div class="stat-title text-base-content/70">Total Weight</div>
                <div class="stat-value text-2xl font-bold text-primary" 
                     aria-label="Total weight: {{ $summary['weight']['lbs'] ?? '0.0 lbs' }}">
                    {{ $summary['weight']['lbs'] ?? '0.0 lbs' }}
                </div>
                <div class="stat-desc text-base-content/60" 
                     aria-label="Weight in kilograms: {{ $summary['weight']['kg'] ?? '0.0 kg' }}">
                    ({{ $summary['weight']['kg'] ?? '0.0 kg' }})
                </div>
            </div>
        @elseif($manifestType === 'sea')
            <!-- Volume Display for Sea Manifests -->
            <div class="stat bg-base-200 rounded-lg p-4">
                <div class="stat-title text-base-content/70">Total Volume</div>
                <div class="stat-value text-2xl font-bold text-secondary" 
                     aria-label="Total volume: {{ $summary['volume'] ?? '0.0 cubic feet' }}">
                    {{ $summary['volume'] ?? '0.0 cubic feet' }}
                </div>
            </div>
        @else
            <!-- Default metric for unknown manifest types -->
            <div class="stat bg-base-200 rounded-lg p-4">
                <div class="stat-title text-base-content/70">Manifest Type</div>
                <div class="stat-value text-lg font-bold text-base-content">
                    {{ ucfirst($manifestType ?: 'Unknown') }}
                </div>
            </div>
        @endif
        
        <!-- Total Value -->
        <div class="stat bg-base-200 rounded-lg p-4">
            <div class="stat-title text-base-content/70">Total Value</div>
            <div class="stat-value text-2xl font-bold text-accent" 
                 aria-label="Total value: ${{ number_format($summary['total_value'] ?? 0, 2) }}">
                ${{ number_format($summary['total_value'] ?? 0, 2) }}
            </div>
        </div>
    </div>
    
    <!-- Data Completeness Indicators -->
    @if($hasIncompleteData)
        <div class="alert alert-warning mt-4" role="alert" aria-live="polite">
            <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <span>
                <strong>Incomplete Data:</strong> 
                Some packages are missing {{ $manifestType === 'air' ? 'weight' : ($manifestType === 'sea' ? 'volume' : 'measurement') }} information. 
                Totals shown are for available data only.
            </span>
        </div>
    @endif

    <!-- Loading indicator for real-time updates -->
    <div wire:loading.delay class="flex items-center justify-center mt-4 text-base-content/60">
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Updating summary...</span>
    </div>
</div>