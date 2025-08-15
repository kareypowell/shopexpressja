<div>
    @include('livewire.quick-insights', [
        'inComingAir' => $inComingAir,
        'inComingSea' => $inComingSea,
        'availableAir' => $availableAir,
        'availableSea' => $availableSea,
        'accountBalance' => $accountBalance
    ])

    <hr class="my-10">

    <!-- Success/Error Messages -->
    @if($successMessage)
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ $successMessage }}
        </div>
    @endif

    @if($errorMessage)
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="mt-10">
        <!-- Package Management Header -->
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-base font-semibold text-gray-900">Packages</h3>
            
            <!-- Consolidation Controls -->
            <div class="flex items-center space-x-4">
                <!-- Consolidation Mode Toggle -->
                <div class="flex items-center">
                    <label class="inline-flex items-center">
                        <input type="checkbox" 
                               wire:model="consolidationMode" 
                               wire:click="toggleConsolidationMode"
                               class="form-checkbox h-4 w-4 text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">Consolidation Mode</span>
                    </label>
                </div>

                <!-- View Toggle -->
                <div class="flex items-center">
                    <label class="inline-flex items-center">
                        <input type="checkbox" 
                               wire:model="showConsolidatedView" 
                               wire:click="toggleConsolidatedView"
                               class="form-checkbox h-4 w-4 text-green-600">
                        <span class="ml-2 text-sm text-gray-700">Show Consolidated</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Consolidation Mode Indicator -->
        @if($consolidationMode)
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-medium text-blue-800">
                            Consolidation Mode Active
                            @if($this->selectedPackagesCount > 0)
                                - {{ $this->selectedPackagesCount }} package(s) selected
                            @endif
                        </span>
                    </div>
                    
                    @if($this->selectedPackagesCount > 0)
                        <div class="flex items-center space-x-2">
                            <button wire:click="clearSelectedPackages" 
                                    class="text-xs px-2 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                Clear Selection
                            </button>
                            
                            @if($this->selectedPackagesCount >= 2)
                                <button wire:click="consolidateSelectedPackages" 
                                        class="text-xs px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Consolidate Selected
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Consolidation Notes -->
                @if($this->selectedPackagesCount >= 2)
                    <div class="mt-3">
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Consolidation Notes (Optional)
                        </label>
                        <textarea wire:model="consolidationNotes" 
                                  rows="2" 
                                  class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="Add any notes about this consolidation..."></textarea>
                    </div>
                @endif
            </div>
        @endif

        <!-- Package Display -->
        @if($showConsolidatedView)
            <!-- Consolidated Packages View -->
            <div class="space-y-6">
                @if($this->consolidatedPackages->count() > 0)
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Consolidated Packages</h4>
                        <div class="space-y-4">
                            @foreach($this->consolidatedPackages as $consolidatedPackage)
                                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                    <!-- Consolidated Package Header -->
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h5 class="font-medium text-gray-900">
                                                {{ $consolidatedPackage->consolidated_tracking_number }}
                                            </h5>
                                            <p class="text-sm text-gray-600">
                                                {{ $consolidatedPackage->packages->count() }} packages consolidated
                                                • Total Weight: {{ number_format($consolidatedPackage->total_weight, 2) }} lbs
                                                • Total Cost: ${{ number_format($consolidatedPackage->total_cost, 2) }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Consolidated on {{ $consolidatedPackage->consolidated_at->format('M d, Y') }}
                                            </p>
                                        </div>
                                        
                                        <div class="flex items-center space-x-2">
                                            <x-package-status-badge :status="$consolidatedPackage->status" />
                                            
                                            @if($consolidatedPackage->canBeUnconsolidated())
                                                <button wire:click="unconsolidatePackage({{ $consolidatedPackage->id }})" 
                                                        class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200"
                                                        onclick="return confirm('Are you sure you want to unconsolidate these packages?')">
                                                    Unconsolidate
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Individual Packages in Consolidation -->
                                    <div class="border-t border-gray-200 pt-3">
                                        <h6 class="text-xs font-medium text-gray-700 mb-2">Individual Packages:</h6>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                            @foreach($consolidatedPackage->packages as $package)
                                                <div class="text-xs p-2 bg-white border border-gray-100 rounded">
                                                    <div class="font-medium">{{ $package->tracking_number }}</div>
                                                    <div class="text-gray-600">
                                                        {{ $package->weight ?? 'N/A' }} lbs
                                                        @if($package->description)
                                                            • {{ Str::limit($package->description, 30) }}
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <p>No consolidated packages found.</p>
                    </div>
                @endif

                <!-- Individual Packages (if any remain) -->
                @if($this->individualPackages->count() > 0)
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Individual Packages</h4>
                        @include('livewire.packages.individual-packages-list', ['packages' => $this->individualPackages])
                    </div>
                @endif
            </div>
        @else
            <!-- Individual Packages View -->
            @if($consolidationMode)
                <!-- Show packages available for consolidation -->
                @if($this->availablePackagesForConsolidation->count() > 0)
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Available for Consolidation</h4>
                        @include('livewire.packages.consolidation-packages-list', ['packages' => $this->availablePackagesForConsolidation])
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <p>No packages available for consolidation.</p>
                    </div>
                @endif
            @else
                <!-- Standard package view -->
                <livewire:customers.customer-packages-with-modal :customer="auth()->user()" />
            @endif
        @endif
    </div>
</div>