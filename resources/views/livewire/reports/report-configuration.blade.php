<div class="space-y-6">
    <!-- Report Type Selection -->
    <div class="bg-white rounded-lg shadow-md p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Report Configuration</h3>
        
        <div class="flex items-center space-x-4">
            <label for="reportType" class="text-sm font-medium text-gray-700">Report Type:</label>
            <select wire:model="reportType" id="reportType" 
                    class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="sales">Sales & Collections</option>
                <option value="manifest">Manifest Performance</option>
                <option value="customer">Customer Analytics</option>
                <option value="financial">Financial Summary</option>
            </select>
        </div>
    </div>

    <!-- Saved Filters Section -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Saved Filters</h3>
            @can('manageSavedFilters')
            <button wire:click="createFilter" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Filter
            </button>
            @endcan
        </div>

        <!-- User's Filters -->
        @if(!empty($userFilters))
        <div class="mb-6">
            <h4 class="text-md font-medium text-gray-800 mb-3">My Filters</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($userFilters as $filter)
                <div class="border border-gray-200 rounded-lg p-3 hover:border-blue-300 transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <h5 class="font-medium text-gray-900 text-sm">{{ $filter['name'] }}</h5>
                        <div class="flex space-x-1">
                            @can('manageSavedFilters')
                            <button wire:click="editFilter({{ $filter['id'] }})" 
                                    class="text-blue-600 hover:text-blue-800" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button wire:click="duplicateFilter({{ $filter['id'] }})" 
                                    class="text-green-600 hover:text-green-800" title="Duplicate">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                            <button wire:click="deleteFilter({{ $filter['id'] }})" 
                                    class="text-red-600 hover:text-red-800" title="Delete"
                                    onclick="return confirm('Are you sure you want to delete this filter?')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                            @endcan
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mb-2">
                        @if($filter['is_shared'])
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                Shared
                            </span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-600">
                        {{ count($filter['filter_config']) }} filter(s) configured
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Shared Filters -->
        @if(!empty($sharedFilters))
        <div>
            <h4 class="text-md font-medium text-gray-800 mb-3">Shared Filters</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($sharedFilters as $filter)
                <div class="border border-gray-200 rounded-lg p-3 hover:border-blue-300 transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <h5 class="font-medium text-gray-900 text-sm">{{ $filter['name'] }}</h5>
                        <button wire:click="duplicateFilter({{ $filter['id'] }})" 
                                class="text-green-600 hover:text-green-800" title="Copy to My Filters">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="text-xs text-gray-500 mb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                            Shared
                        </span>
                    </div>
                    <div class="text-xs text-gray-600">
                        {{ count($filter['filter_config']) }} filter(s) configured
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if(empty($userFilters) && empty($sharedFilters))
        <div class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
            </svg>
            <p>No saved filters found for this report type.</p>
            @can('manageSavedFilters')
            <button wire:click="createFilter" 
                    class="mt-2 text-blue-600 hover:text-blue-800 font-medium">
                Create your first filter
            </button>
            @endcan
        </div>
        @endif
    </div>

    <!-- Report Templates Section -->
    @if(!empty($templates))
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Report Templates</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($templates as $template)
            <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                <div class="flex justify-between items-start mb-2">
                    <h4 class="font-medium text-gray-900">{{ $template['name'] }}</h4>
                    <button wire:click="applyTemplate({{ $template['id'] }})" 
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Apply
                    </button>
                </div>
                @if($template['description'])
                <p class="text-sm text-gray-600 mb-2">{{ $template['description'] }}</p>
                @endif
                <div class="text-xs text-gray-500">
                    {{ count($template['default_filters'] ?? []) }} default filter(s)
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Filter Modal -->
    @if($showFilterModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeFilterModal">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white" wire:click.stop>
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ $editingFilter ? 'Edit Filter' : 'Create New Filter' }}
                </h3>
                
                <form wire:submit.prevent="saveFilter" class="space-y-4">
                    <div>
                        <label for="filterName" class="block text-sm font-medium text-gray-700 mb-1">Filter Name</label>
                        <input type="text" wire:model="filterName" id="filterName" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('filterName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter Configuration</label>
                        <div class="space-y-2 max-h-40 overflow-y-auto border border-gray-200 rounded p-3">
                            <!-- This would be expanded with actual filter configuration UI -->
                            <p class="text-sm text-gray-600">Filter configuration interface would go here.</p>
                            <p class="text-xs text-gray-500">Current filters: {{ count($filterConfig) }}</p>
                        </div>
                    </div>

                    @can('shareSavedFilters')
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="isShared" class="rounded border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">Share this filter with other users</span>
                        </label>
                        
                        @if($isShared)
                        <div class="mt-2 ml-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Share with roles:</label>
                            <div class="space-y-1">
                                <label class="flex items-center">
                                    <input type="checkbox" wire:click="toggleSharedRole('admin')" 
                                           {{ in_array('admin', $sharedWithRoles) ? 'checked' : '' }}
                                           class="rounded border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">Admins</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" wire:click="toggleSharedRole('superadmin')" 
                                           {{ in_array('superadmin', $sharedWithRoles) ? 'checked' : '' }}
                                           class="rounded border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">Super Admins</span>
                                </label>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endcan

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" wire:click="closeFilterModal" 
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            {{ $editingFilter ? 'Update' : 'Create' }} Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex">
            <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">There were errors:</h3>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    window.addEventListener('notify', event => {
        // You can integrate with your notification system here
        console.log(event.detail.type + ': ' + event.detail.message);
        
        // Example with a simple alert (replace with your notification system)
        if (event.detail.type === 'success') {
            // Show success notification
        } else if (event.detail.type === 'error') {
            // Show error notification
        }
    });
</script>
@endpush