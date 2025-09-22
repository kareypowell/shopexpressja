@extends('layouts.app')

@section('title', 'Edit Report Template')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Report Template</h1>
                <p class="text-gray-600 mt-1">Update the configuration for "{{ $template->name }}"</p>
            </div>
            <a href="{{ route('reports.templates.show', $template) }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Template
            </a>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="bg-white shadow-sm rounded-lg">
        <form method="POST" action="{{ route('reports.templates.update', $template) }}">
            @csrf
            @method('PUT')
            
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Template Information</h3>
            </div>
            
            <div class="px-6 py-4 space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Template Name</label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name', $template->name) }}"
                           required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-300 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Report Type</label>
                    <select name="type" 
                            id="type" 
                            required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('type') border-red-300 @enderror">
                        <option value="">Select a report type</option>
                        @foreach(\App\Models\ReportTemplate::getAvailableTypes() as $value => $label)
                            <option value="{{ $value }}" {{ old('type', $template->type) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" 
                              id="description" 
                              rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('description') border-red-300 @enderror"
                              placeholder="Describe what this template is used for...">{{ old('description', $template->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active" 
                               value="1"
                               {{ old('is_active', $template->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                            Active Template
                        </label>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Active templates are available for report generation</p>
                </div>
            </div>

            <!-- Template Configuration -->
            <div class="px-6 py-4 border-t border-gray-200">
                <h4 class="text-md font-medium text-gray-900 mb-4">Template Configuration</h4>
                
                <div class="space-y-4">
                    <!-- Chart Types -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chart Types</label>
                        <div class="space-y-2">
                            @php
                                $currentChartTypes = old('chart_types', $template->template_config['chart_types'] ?? []);
                            @endphp
                            <label class="inline-flex items-center mr-6">
                                <input type="checkbox" 
                                       name="chart_types[]" 
                                       value="line"
                                       {{ in_array('line', $currentChartTypes) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Line Charts</span>
                            </label>
                            <label class="inline-flex items-center mr-6">
                                <input type="checkbox" 
                                       name="chart_types[]" 
                                       value="bar"
                                       {{ in_array('bar', $currentChartTypes) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Bar Charts</span>
                            </label>
                            <label class="inline-flex items-center mr-6">
                                <input type="checkbox" 
                                       name="chart_types[]" 
                                       value="pie"
                                       {{ in_array('pie', $currentChartTypes) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Pie Charts</span>
                            </label>
                        </div>
                    </div>

                    <!-- Layout -->
                    <div>
                        <label for="layout" class="block text-sm font-medium text-gray-700">Layout</label>
                        <select name="layout" 
                                id="layout"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @php
                                $currentLayout = old('layout', $template->template_config['layout'] ?? 'standard');
                            @endphp
                            <option value="standard" {{ $currentLayout === 'standard' ? 'selected' : '' }}>Standard</option>
                            <option value="compact" {{ $currentLayout === 'compact' ? 'selected' : '' }}>Compact</option>
                            <option value="detailed" {{ $currentLayout === 'detailed' ? 'selected' : '' }}>Detailed</option>
                        </select>
                    </div>

                    <!-- Include Options -->
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" 
                                   name="include_charts" 
                                   value="1"
                                   {{ old('include_charts', $template->template_config['include_charts'] ?? true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Include Charts</span>
                        </label>
                        <br>
                        <label class="inline-flex items-center">
                            <input type="checkbox" 
                                   name="include_tables" 
                                   value="1"
                                   {{ old('include_tables', $template->template_config['include_tables'] ?? true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Include Data Tables</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Default Filters -->
            <div class="px-6 py-4 border-t border-gray-200">
                <h4 class="text-md font-medium text-gray-900 mb-4">Default Filters</h4>
                
                <div class="space-y-4">
                    <!-- Date Range -->
                    <div>
                        <label for="date_range" class="block text-sm font-medium text-gray-700">Default Date Range</label>
                        <select name="date_range" 
                                id="date_range"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @php
                                $currentDateRange = old('date_range', $template->default_filters['date_range'] ?? 'last_30_days');
                            @endphp
                            <option value="last_7_days" {{ $currentDateRange === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="last_30_days" {{ $currentDateRange === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="last_90_days" {{ $currentDateRange === 'last_90_days' ? 'selected' : '' }}>Last 90 Days</option>
                            <option value="current_month" {{ $currentDateRange === 'current_month' ? 'selected' : '' }}>Current Month</option>
                            <option value="last_month" {{ $currentDateRange === 'last_month' ? 'selected' : '' }}>Last Month</option>
                            <option value="current_year" {{ $currentDateRange === 'current_year' ? 'selected' : '' }}>Current Year</option>
                        </select>
                    </div>

                    <!-- Include All Options -->
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" 
                                   name="include_all_offices" 
                                   value="1"
                                   {{ old('include_all_offices', $template->default_filters['include_all_offices'] ?? true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Include All Offices by Default</span>
                        </label>
                        <br>
                        <label class="inline-flex items-center">
                            <input type="checkbox" 
                                   name="include_all_manifest_types" 
                                   value="1"
                                   {{ old('include_all_manifest_types', $template->default_filters['include_all_manifest_types'] ?? true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Include All Manifest Types by Default</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <a href="{{ route('reports.templates.show', $template) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Update Template
                </button>
            </div>
        </form>
    </div>
</div>
@endsection