@extends('layouts.app')

@section('title', 'Create Report Template')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('reports.index') }}" class="text-gray-700 hover:text-blue-600">Reports</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('reports.templates.index') }}" class="ml-1 text-gray-700 hover:text-blue-600 md:ml-2">Templates</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-gray-500 md:ml-2">Create</span>
                    </div>
                </li>
            </ol>
        </nav>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">Create Report Template</h1>
        <p class="mt-2 text-gray-600">Configure a new report template for consistent reporting</p>
    </div>

    <form method="POST" action="{{ route('reports.templates.store') }}" class="space-y-6">
        @csrf
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Template Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                    <select name="type" id="type" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('type') border-red-500 @enderror">
                        <option value="">Select Type</option>
                        <option value="sales" {{ old('type') === 'sales' ? 'selected' : '' }}>Sales & Collections</option>
                        <option value="manifest" {{ old('type') === 'manifest' ? 'selected' : '' }}>Manifest Performance</option>
                        <option value="customer" {{ old('type') === 'customer' ? 'selected' : '' }}>Customer Analytics</option>
                        <option value="financial" {{ old('type') === 'financial' ? 'selected' : '' }}>Financial Summary</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                          placeholder="Describe the purpose and usage of this template">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">Active template</span>
                </label>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Template Configuration</h2>
            
            <div class="space-y-6">
                <!-- Columns Configuration -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Report Columns</label>
                    <div id="columns-container" class="space-y-2">
                        <div class="flex items-center space-x-2 column-row">
                            <input type="text" name="template_config[columns][]" placeholder="Column name" required
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <select name="template_config[column_types][]" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="currency">Currency</option>
                                <option value="date">Date</option>
                                <option value="percentage">Percentage</option>
                            </select>
                            <button type="button" class="px-3 py-2 text-red-600 hover:text-red-800 remove-column" style="display: none;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="button" id="add-column" class="mt-2 px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                        Add Column
                    </button>
                </div>

                <!-- Chart Configuration -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chart Types</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="template_config[charts][]" value="bar" class="rounded border-gray-300">
                            <span class="ml-2 text-sm">Bar Chart</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="template_config[charts][]" value="line" class="rounded border-gray-300">
                            <span class="ml-2 text-sm">Line Chart</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="template_config[charts][]" value="pie" class="rounded border-gray-300">
                            <span class="ml-2 text-sm">Pie Chart</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="template_config[charts][]" value="doughnut" class="rounded border-gray-300">
                            <span class="ml-2 text-sm">Doughnut Chart</span>
                        </label>
                    </div>
                </div>

                <!-- Filter Configuration -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Available Filters</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="template_config[filters][]" value="date_range" class="rounded border-gray-300">
                            <span class="ml-2 text-sm">Date Range</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="template_config[filters][]" value="manifest_type" class="rounded border-gray-300">
                            <span class="ml-2 text-sm">Manifest Type</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="template_config[filters][]" value="office" class="rounded border-gray-300">
                            <span class="ml-2 text-sm">Office</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="template_config[filters][]" value="customer" class="rounded border-gray-300">
                            <span class="ml-2 text-sm">Customer</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="template_config[filters][]" value="status" class="rounded border-gray-300">
                            <span class="ml-2 text-sm">Status</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Default Filters</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="default_date_range" class="block text-sm font-medium text-gray-700 mb-1">Default Date Range</label>
                    <select name="default_filters[date_range]" id="default_date_range"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">No Default</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="last_7_days">Last 7 Days</option>
                        <option value="last_30_days">Last 30 Days</option>
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="this_year">This Year</option>
                    </select>
                </div>

                <div>
                    <label for="default_manifest_type" class="block text-sm font-medium text-gray-700 mb-1">Default Manifest Type</label>
                    <select name="default_filters[manifest_type]" id="default_manifest_type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="air">Air Freight</option>
                        <option value="sea">Sea Freight</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('reports.templates.index') }}" 
               class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                Create Template
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const columnsContainer = document.getElementById('columns-container');
    const addColumnBtn = document.getElementById('add-column');
    
    function updateRemoveButtons() {
        const rows = columnsContainer.querySelectorAll('.column-row');
        rows.forEach((row, index) => {
            const removeBtn = row.querySelector('.remove-column');
            if (rows.length > 1) {
                removeBtn.style.display = 'block';
            } else {
                removeBtn.style.display = 'none';
            }
        });
    }
    
    addColumnBtn.addEventListener('click', function() {
        const newRow = document.createElement('div');
        newRow.className = 'flex items-center space-x-2 column-row';
        newRow.innerHTML = `
            <input type="text" name="template_config[columns][]" placeholder="Column name" required
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <select name="template_config[column_types][]" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="text">Text</option>
                <option value="number">Number</option>
                <option value="currency">Currency</option>
                <option value="date">Date</option>
                <option value="percentage">Percentage</option>
            </select>
            <button type="button" class="px-3 py-2 text-red-600 hover:text-red-800 remove-column">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        `;
        
        columnsContainer.appendChild(newRow);
        updateRemoveButtons();
        
        // Add event listener to the new remove button
        newRow.querySelector('.remove-column').addEventListener('click', function() {
            newRow.remove();
            updateRemoveButtons();
        });
    });
    
    // Add event listeners to existing remove buttons
    columnsContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-column')) {
            e.target.closest('.column-row').remove();
            updateRemoveButtons();
        }
    });
    
    updateRemoveButtons();
});
</script>
@endpush
@endsection