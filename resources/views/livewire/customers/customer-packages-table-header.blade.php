<div class="mb-4">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-3 sm:space-y-0">
        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4">
            <h3 class="text-lg font-medium text-gray-900">Package History</h3>
            <div class="text-sm text-gray-500">
                {{ $this->getPackageStats()['total_packages'] }} packages
                @if($this->shouldShowCosts())
                    • ${{ number_format($this->getPackageStats()['total_spent'], 2) }} total spent
                @endif
            </div>
        </div>
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-3 space-y-2 sm:space-y-0">
            @if($this->shouldShowCosts())
                <button 
                    type="button"
                    wire:click="toggleCostBreakdown"
                    class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    @if($showCostBreakdown)
                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                        </svg>
                        <span class="hidden sm:inline">Hide Cost Breakdown</span>
                        <span class="sm:hidden">Hide Costs</span>
                    @else
                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span class="hidden sm:inline">Show Cost Breakdown</span>
                        <span class="sm:hidden">Show Costs</span>
                    @endif
                </button>
                
                <div class="text-sm text-gray-500 text-center sm:text-left">
                    Avg: ${{ number_format($this->getPackageStats()['average_cost'], 2) }} per package
                </div>
            @endif
        </div>
    </div>
</div>

@if($this->getPackageStats()['status_breakdown'])
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach($this->getPackageStats()['status_breakdown'] as $status => $count)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                @if($status == 'processing') bg-blue-100 text-blue-800
                @elseif($status == 'shipped') bg-green-100 text-green-800
                @elseif($status == 'delayed') bg-yellow-100 text-yellow-800
                @elseif($status == 'ready_for_pickup') bg-purple-100 text-purple-800
                @else bg-gray-100 text-gray-800
                @endif">
                {{ ucfirst(str_replace('_', ' ', $status)) }}: {{ $count }}
            </span>
        @endforeach
    </div>
@endif