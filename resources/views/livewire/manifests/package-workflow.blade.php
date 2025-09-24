<div x-data="{}">
    <!-- Toastr notifications will be handled by JavaScript -->

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Package Workflow Management
                @if($manifestId)
                    - Manifest #{{ $manifestId }}
                @endif
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Manage package statuses and track workflow progress
            </p>
        </div>
        
        @if($manifestId)
            <a href="{{ route('admin.manifests.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Manifests
            </a>
        @endif
    </div>

    <!-- Status Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
        @foreach($statusStatistics as $status => $stats)
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            @php
                                $badgeClass = $stats['badge_class'] ?? 'default';
                            @endphp
                            @if($badgeClass === 'default')
                                <x-badges.default>{{ $stats['count'] }}</x-badges.default>
                            @elseif($badgeClass === 'primary')
                                <x-badges.primary>{{ $stats['count'] }}</x-badges.primary>
                            @elseif($badgeClass === 'success')
                                <x-badges.success>{{ $stats['count'] }}</x-badges.success>
                            @elseif($badgeClass === 'warning')
                                <x-badges.warning>{{ $stats['count'] }}</x-badges.warning>
                            @elseif($badgeClass === 'danger')
                                <x-badges.danger>{{ $stats['count'] }}</x-badges.danger>
                            @elseif($badgeClass === 'shs')
                                <x-badges.shs>{{ $stats['count'] }}</x-badges.shs>
                            @else
                                <x-badges.default>{{ $stats['count'] }}</x-badges.default>
                            @endif
                        </div>
                        <div class="ml-3 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    {{ $stats['label'] }}
                                </dt>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Bulk Actions -->
    @if(count($selectedPackages) > 0)
        <div class="bg-wax-flower-50 border border-wax-flower-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-wax-flower-800">
                        {{ count($selectedPackages) }} package(s) selected
                    </span>
                    
                    <!-- Quick Actions -->
                    <div class="flex items-center space-x-2">
                        @php
                            $commonNextStatus = $this->getCommonNextStatus();
                        @endphp
                        
                        @if($commonNextStatus)
                            <button 
                                wire:click="bulkAdvanceToNext"
                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                title="Advance all selected packages to {{ $commonNextStatus->getLabel() }}"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                                Advance to {{ $commonNextStatus->getLabel() }}
                            </button>
                        @endif

                        @if($this->canDistributeSelected())
                            <button 
                                wire:click="initiateDistribution"
                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                Distribute
                            </button>
                        @endif
                    </div>
                    
                    <!-- Consolidation Action -->
                    @if($this->canConsolidateSelected)
                        <div class="flex items-center space-x-2">
                            <button 
                                wire:click="showConsolidationModal"
                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                Consolidate
                            </button>
                        </div>
                    @endif

                    <!-- Custom Status Selection -->
                    <div class="flex items-center space-x-2">
                        <select 
                            wire:model="bulkStatus"
                            class="border-wax-flower-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                        >
                            <option value="">Custom status...</option>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <button 
                            wire:click="confirmBulkStatusUpdate"
                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-wax-flower-600 hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500"
                            @if(!$bulkStatus) disabled @endif
                        >
                            Update
                        </button>
                    </div>
                </div>

                <button 
                    wire:click="$set('selectedPackages', [])"
                    class="text-sm text-gray-500 hover:text-gray-700"
                >
                    Clear Selection
                </button>
            </div>

            @error('bulkStatus')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('selectedPackages')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <!-- Enhanced Manifest Summary -->
    @if($manifestId)
        @livewire('manifests.enhanced-manifest-summary', ['manifest' => $this->manifest], key('summary-'.$manifestId))
    @endif

    <br>
    
    <!-- Tabbed Interface for Packages -->
    @if($manifestId)
        @livewire('manifests.manifest-tabs-container', ['manifest' => $this->manifest], key('tabs-'.$manifestId))
    @endif
  
  <!-- Bulk Status Update Confirmation Modal -->
    @if($showConfirmModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="confirm-modal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.962-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mt-2">Confirm Status Update</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to update {{ count($confirmingPackages) }} package(s) to 
                            <strong>{{ $confirmingStatusLabel ?: 'Unknown' }}</strong>?
                        </p>
                        
                        <!-- Optional Notes -->
                        <div class="mt-4">
                            <label for="notes" class="block text-sm font-medium text-gray-700 text-left">
                                Notes (optional)
                            </label>
                            <textarea 
                                id="notes"
                                wire:model="notes"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                placeholder="Add any notes about this status change..."
                            ></textarea>
                        </div>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button 
                            wire:click="executeBulkStatusUpdate" 
                            class="px-4 py-2 bg-wax-flower-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-wax-flower-600 focus:outline-none focus:ring-2 focus:ring-wax-flower-300"
                        >
                            Confirm
                        </button>
                        <button 
                            wire:click="cancelBulkUpdate" 
                            class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Fee Entry Modal -->
    @if($showFeeModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="fee-modal">
            <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Update Package Fees - {{ $feePackage->tracking_number ?? '' }}
                        </h3>
                        <button wire:click="closeFeeModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    @if($feePackage)
                        <!-- Package Info -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <h4 class="font-medium text-gray-900 mb-2">Package Information</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Customer:</span>
                                    <span class="ml-2 font-medium">{{ $feePackage->user->full_name ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Description:</span>
                                    <span class="ml-2">{{ $feePackage->description }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Weight:</span>
                                    <span class="ml-2">{{ number_format($feePackage->weight, 2) }} lbs</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Freight Price:</span>
                                    <span class="ml-2">${{ number_format($feePackage->freight_price ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Fee Entry Form -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label for="clearance-fee" class="block text-sm font-medium text-gray-700 mb-1">
                                    Clearance Fee
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                                    <input 
                                        type="number" 
                                        id="customs-duty"
                                        wire:model.lazy="clearanceFee"
                                        step="0.01"
                                        min="0"
                                        class="pl-8 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                        placeholder="0.00"
                                    >
                                </div>
                                @error('clearanceFee')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="storage-fee" class="block text-sm font-medium text-gray-700 mb-1">
                                    Storage Fee
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                                    <input 
                                        type="number" 
                                        id="storage-fee"
                                        wire:model.lazy="storageFee"
                                        step="0.01"
                                        min="0"
                                        class="pl-8 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                        placeholder="0.00"
                                    >
                                </div>
                                @error('storageFee')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="delivery-fee" class="block text-sm font-medium text-gray-700 mb-1">
                                    Delivery Fee
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                                    <input 
                                        type="number" 
                                        id="delivery-fee"
                                        wire:model.lazy="deliveryFee"
                                        step="0.01"
                                        min="0"
                                        class="pl-8 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500 sm:text-sm"
                                        placeholder="0.00"
                                    >
                                </div>
                                @error('deliveryFee')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Credit Balance Option -->
                        @if($feePackage->user->credit_balance > 0)
                            <div class="mb-6">
                                <div class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        id="apply-credit"
                                        wire:model="applyCreditBalance"
                                        class="h-4 w-4 text-wax-flower-600 focus:ring-wax-flower-500 border-gray-300 rounded"
                                    >
                                    <label for="apply-credit" class="ml-2 block text-sm text-gray-900">
                                        Apply available credit balance (${{ number_format($feePackage->user->credit_balance, 2) }})
                                    </label>
                                </div>
                            </div>
                        @endif

                        <!-- Fee Preview -->
                        @if($feePreview && $feePreview['valid'])
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <h4 class="font-medium text-blue-900 mb-3">Cost Summary</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Freight Price:</span>
                                            <span>${{ number_format($feePreview['fees']['freight_price'], 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Clearance Fee:</span>
                                            <span>${{ number_format($feePreview['fees']['clearance_fee'], 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Storage Fee:</span>
                                            <span>${{ number_format($feePreview['fees']['storage_fee'], 2) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Delivery Fee:</span>
                                            <span>${{ number_format($feePreview['fees']['delivery_fee'], 2) }}</span>
                                        </div>
                                        <div class="flex justify-between font-medium border-t pt-2">
                                            <span>Total Cost:</span>
                                            <span>${{ $feePreview['formatted']['new_total_cost'] }}</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        @if($applyCreditBalance && $feePreview['cost_summary']['credit_to_apply'] > 0)
                                            <div class="flex justify-between text-green-600">
                                                <span>Credit Applied:</span>
                                                <span>-${{ $feePreview['formatted']['credit_to_apply'] }}</span>
                                            </div>
                                        @endif
                                        <div class="flex justify-between font-medium">
                                            <span>Net Charge:</span>
                                            <span>${{ $feePreview['formatted']['net_charge'] }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm text-gray-600">
                                            <span>Customer Balance After:</span>
                                            <span>${{ $feePreview['formatted']['customer_balance_after'] }}</span>
                                        </div>
                                        @if($applyCreditBalance)
                                            <div class="flex justify-between text-sm text-gray-600">
                                                <span>Credit Balance After:</span>
                                                <span>${{ $feePreview['formatted']['customer_credit_after'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end space-x-3">
                            <button 
                                wire:click="closeFeeModal" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300"
                            >
                                Cancel
                            </button>
                            <button 
                                wire:click="processFeeUpdate" 
                                class="px-4 py-2 bg-wax-flower-600 text-white text-sm font-medium rounded-md hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-wax-flower-500"
                                @if(!$feePreview || !$feePreview['valid']) disabled @endif
                            >
                                Update Fees & Set Ready
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
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
</div>