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

    <!-- Search and Filter Section -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Search Packages</label>
                    <input wire:model.debounce.300ms="searchTerm" type="text" id="search" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Search by tracking number, description, customer...">
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status-filter" class="block text-sm font-medium text-gray-700">Filter by Status</label>
                    <select wire:model="statusFilter" id="status-filter" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">All Statuses</option>
                        @foreach($this->statusOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex items-end space-x-2">
                    <button wire:click="resetFilters()" 
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Reset Filters
                    </button>
                    <button wire:click="clearSelections()" 
                            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                        Clear Selections
                    </button>
                </div>
            </div>
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

    <!-- Manifest Totals Summary -->
    @if($this->manifestTotals['individual_packages'] > 0 || $this->manifestTotals['consolidated_packages'] > 0)
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Manifest Summary</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $this->manifestTotals['individual_packages'] }}</div>
                    <div class="text-sm text-gray-500">Individual Packages</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $this->manifestTotals['consolidated_packages'] }}</div>
                    <div class="text-sm text-gray-500">Consolidated Groups</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $this->manifestTotals['total_packages_in_consolidated'] }}</div>
                    <div class="text-sm text-gray-500">Packages in Groups</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-600">{{ number_format($this->manifestTotals['total_weight'], 2) }} lbs</div>
                    <div class="text-sm text-gray-500">Total Weight</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Consolidated Packages Section -->
    @if($this->consolidatedPackages->count() > 0)
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Consolidated Packages</h4>
            <div class="space-y-4">
                @foreach($this->consolidatedPackages as $consolidatedPackage)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-4">
                                <div>
                                    <h5 class="font-medium text-gray-900">{{ $consolidatedPackage->consolidated_tracking_number }}</h5>
                                    <p class="text-sm text-gray-500">{{ $consolidatedPackage->customer->full_name ?? 'N/A' }}</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <x-package-status-badge :status="$consolidatedPackage->status" />
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $consolidatedPackage->total_quantity }} packages
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <select wire:change="updateConsolidatedPackageStatus({{ $consolidatedPackage->id }}, $event.target.value)"
                                        class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Change Status</option>
                                    @foreach($this->statusOptions as $option)
                                        <option value="{{ $option['value'] }}" 
                                                @if($consolidatedPackage->status === $option['value']) selected @endif>
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($consolidatedPackage->canBeUnconsolidated())
                                    <button wire:click="unconsolidatePackage({{ $consolidatedPackage->id }})"
                                            onclick="return confirm('Are you sure you want to unconsolidate this package? This will separate all individual packages.')"
                                            class="text-red-600 hover:text-red-900 text-sm">
                                        Unconsolidate
                                    </button>
                                @endif
                                <button onclick="toggleConsolidatedDetails({{ $consolidatedPackage->id }})"
                                        class="text-blue-600 hover:text-blue-900 text-sm">
                                    Toggle Details
                                </button>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-4 gap-4 text-sm text-gray-600 mb-3">
                            <div>Weight: {{ number_format($consolidatedPackage->total_weight, 2) }} lbs</div>
                            <div>Freight: ${{ number_format($consolidatedPackage->total_freight_price, 2) }}</div>
                            <div>Customs: ${{ number_format($consolidatedPackage->total_customs_duty, 2) }}</div>
                            <div>Total: ${{ number_format($consolidatedPackage->total_cost, 2) }}</div>
                        </div>

                        <!-- Expandable Individual Packages -->
                        <div id="consolidated-details-{{ $consolidatedPackage->id }}" class="hidden mt-4 border-t pt-4">
                            <h6 class="font-medium text-gray-900 mb-2">Individual Packages:</h6>
                            <div class="space-y-2">
                                @foreach($consolidatedPackage->packages as $package)
                                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded">
                                        <div class="flex items-center space-x-4">
                                            <span class="font-medium">{{ $package->tracking_number }}</span>
                                            <span class="text-gray-600">{{ Str::limit($package->description, 30) }}</span>
                                            <span class="text-gray-500">{{ $package->weight }} lbs</span>
                                        </div>
                                        <x-package-status-badge :package="$package" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Individual Packages Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:p-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Individual Packages</h4>
            @if($this->packages->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" 
                                           @if($selectAll) checked @endif
                                           wire:click="toggleSelectAll()"
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tracking Number
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Customer
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Description
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Weight
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($this->packages as $package)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" 
                                               @if(in_array($package->id, $selectedPackages)) checked @endif
                                               wire:click="togglePackageSelection({{ $package->id }})"
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $package->tracking_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $package->user->full_name ?? 'N/A' }}
                                        @if($package->user && $package->user->profile)
                                            <br><span class="text-xs text-gray-400">{{ $package->user->profile->account_number }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ Str::limit($package->description, 50) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-package-status-badge :package="$package" />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $package->weight }} lbs
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('admin.manifests.packages.edit', ['manifest' => $manifest_id, 'package' => $package->id]) }}" 
                                           class="text-blue-600 hover:text-blue-900">Edit</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $this->packages->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5M9 5v-.5" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No packages found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if(!empty($searchTerm) || !empty($statusFilter))
                            Try adjusting your search or filter criteria.
                        @else
                            Get started by adding a new package to this manifest.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

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

    <script>
        function toggleConsolidatedDetails(consolidatedPackageId) {
            const detailsElement = document.getElementById('consolidated-details-' + consolidatedPackageId);
            if (detailsElement) {
                detailsElement.classList.toggle('hidden');
            }
        }
    </script>
</div>