<div>
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Manifest Packages
        </h3>

        <div class="flex space-x-3">
            <button wire:click="goToWorkflow()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Package Workflow
            </button>

            <button wire:click="create()" class="bg-wax-flower-500 hover:bg-wax-flower-700 text-white font-bold py-2 px-4 rounded">
                Add Package
            </button>
        </div>
    </div>

    <!-- Package Status Legend -->
    <div class="mb-6">
        <x-package-status-legend :compact="true" />
    </div>

    <!-- Bulk Actions Bar -->
    @if($showBulkActions)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-sm font-medium text-blue-900">
                    {{ count($selectedPackages) }} package(s) selected
                </span>
            </div>
            <div class="flex space-x-2">
                @if($this->canConsolidateSelected)
                    <button wire:click="showConsolidationModal()" 
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Consolidate
                    </button>
                @endif
                <button wire:click="showBulkStatusUpdate()" 
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                    Update Status
                </button>
                <button wire:click="clearSelections()" 
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                    Clear Selection
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Enhanced Manifest Summary -->
    @livewire('manifests.enhanced-manifest-summary', ['manifest' => $this->manifest], key('summary-'.$manifest_id))

    <br>
    
    <!-- Tabbed Interface for Packages -->
    @livewire('manifests.manifest-tabs-container', ['manifest' => $this->manifest], key('tabs-'.$manifest_id))

    <!-- Status Update Modal -->
    @if($showStatusUpdateModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900">Update Package Status</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 mb-4">{{ $confirmationMessage }}</p>
                    
                    <div class="mb-4">
                        <label for="bulk-status" class="block text-sm font-medium text-gray-700 mb-2">New Status</label>
                        <select wire:model="bulkStatus" id="bulk-status" 
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Select Status</option>
                            @foreach($this->statusOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-center space-x-4">
                    <button wire:click="confirmBulkStatusUpdate()" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Update Status
                    </button>
                    <button wire:click="cancelStatusUpdate()" 
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($isOpen)
    @include('livewire.manifests.packages.create')
    @endif

    <!-- Consolidation Modal -->
    @if($showConsolidationModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Consolidate Packages</h3>
                    <button wire:click="cancelConsolidation" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-4">
                        You are about to consolidate {{ count($packagesForConsolidation) }} packages into a single consolidated package. 
                        This will group them together for easier management and processing.
                    </p>
                    
                    <!-- Packages Preview -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4 max-h-60 overflow-y-auto">
                        <h4 class="font-medium text-gray-900 mb-2">Packages to be consolidated:</h4>
                        <div class="space-y-2">
                            @foreach($packagesForConsolidation as $package)
                                <div class="flex items-center justify-between bg-white p-2 rounded border">
                                    <div>
                                        <span class="font-medium">{{ $package['tracking_number'] }}</span>
                                        <span class="text-gray-500 ml-2">{{ $package['description'] ?? 'No description' }}</span>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $package['weight'] }} lbs
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Customer Info -->
                    @if(!empty($packagesForConsolidation))
                        <div class="bg-blue-50 rounded-lg p-3 mb-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span class="text-blue-800 font-medium">
                                    Customer: 
                                    @php
                                        $firstPackage = $packagesForConsolidation[0] ?? null;
                                        $customerName = 'N/A';
                                        $accountNumber = '';
                                        
                                        if ($firstPackage && isset($firstPackage['user'])) {
                                            $user = $firstPackage['user'];
                                            $customerName = $user['full_name'] ?? 'N/A';
                                            
                                            // Double-check if full_name is empty or N/A
                                            if (!$customerName || $customerName === 'N/A' || trim($customerName) === '') {
                                                $firstName = $user['first_name'] ?? '';
                                                $lastName = $user['last_name'] ?? '';
                                                $customerName = trim($firstName . ' ' . $lastName);
                                                if (empty($customerName)) {
                                                    $customerName = 'N/A';
                                                }
                                            }
                                            
                                            if (isset($user['profile']['account_number'])) {
                                                $accountNumber = $user['profile']['account_number'];
                                            }
                                        }
                                    @endphp
                                    {{ $customerName }}
                                    @if($accountNumber)
                                        <span class="text-blue-600 text-sm ml-2">({{ $accountNumber }})</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Notes -->
                    <div class="mb-4">
                        <label for="consolidation-notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Notes (Optional)
                        </label>
                        <textarea 
                            wire:model="consolidationNotes" 
                            id="consolidation-notes"
                            rows="3" 
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="Add any notes about this consolidation..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button wire:click="cancelConsolidation" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded">
                        Cancel
                    </button>
                    <button wire:click="confirmConsolidation" 
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Consolidate Packages
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Consolidated Package Fee Entry Modal -->
    @if($showConsolidatedFeeModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="consolidated-fee-modal">
            <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Update Consolidated Package Fees - {{ $feeConsolidatedPackage->consolidated_tracking_number ?? '' }}
                        </h3>
                        <button wire:click="closeConsolidatedFeeModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    @if($feeConsolidatedPackage)
                        <!-- Consolidated Package Info -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <h4 class="font-medium text-gray-900 mb-2">Consolidated Package Information</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Customer:</span>
                                    <span class="ml-2 font-medium">{{ $feeConsolidatedPackage->packages->first()->user->full_name ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Total Packages:</span>
                                    <span class="ml-2">{{ $feeConsolidatedPackage->packages->count() }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Total Weight:</span>
                                    <span class="ml-2">{{ number_format($feeConsolidatedPackage->packages->sum('weight'), 2) }} lbs</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Total Freight Price:</span>
                                    <span class="ml-2">${{ number_format($feeConsolidatedPackage->packages->sum('freight_price'), 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Individual Package Fees -->
                        <div class="mb-6">
                            <h4 class="font-medium text-gray-900 mb-4">Individual Package Fees</h4>
                            <div class="space-y-4">
                                @foreach($consolidatedPackagesNeedingFees as $index => $packageData)
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h5 class="font-medium text-gray-800">
                                                {{ $packageData['tracking_number'] }}
                                            </h5>
                                            @if($packageData['needs_fees'])
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Needs Fees
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <div class="mb-3 text-sm text-gray-600">
                                            {{ $packageData['description'] }}
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Customs Duty
                                                </label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                                                    <input 
                                                        type="number" 
                                                        wire:model.lazy="consolidatedPackagesNeedingFees.{{ $index }}.customs_duty"
                                                        step="0.01"
                                                        min="0"
                                                        class="pl-8 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                                        placeholder="0.00"
                                                    >
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Storage Fee
                                                </label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                                                    <input 
                                                        type="number" 
                                                        wire:model.lazy="consolidatedPackagesNeedingFees.{{ $index }}.storage_fee"
                                                        step="0.01"
                                                        min="0"
                                                        class="pl-8 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                                        placeholder="0.00"
                                                    >
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Delivery Fee
                                                </label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                                                    <input 
                                                        type="number" 
                                                        wire:model.lazy="consolidatedPackagesNeedingFees.{{ $index }}.delivery_fee"
                                                        step="0.01"
                                                        min="0"
                                                        class="pl-8 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                                        placeholder="0.00"
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end space-x-3">
                            <button 
                                wire:click="closeConsolidatedFeeModal" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300"
                            >
                                Cancel
                            </button>
                            <button 
                                wire:click="processConsolidatedFeeUpdate" 
                                class="px-4 py-2 bg-wax-flower-600 text-white text-sm font-medium rounded-md hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-wax-flower-500"
                            >
                                Update Fees & Set Ready
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <script>
        function toggleConsolidatedDetails(consolidatedPackageId) {
            const detailsElement = document.getElementById('consolidated-details-' + consolidatedPackageId);
            if (detailsElement) {
                detailsElement.classList.toggle('hidden');
            }
        }
    </script>
</div>