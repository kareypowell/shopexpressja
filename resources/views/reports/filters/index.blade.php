@extends('layouts.app')

@section('title', 'Saved Report Filters')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Breadcrumb Navigation -->
    <nav class="flex mb-4" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                    </svg>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <a href="{{ route('reports.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">Reports</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Saved Filters</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Saved Report Filters</h1>
                <p class="mt-2 text-gray-600">Manage your saved filter configurations for quick report access</p>
            </div>
            @can('report.manageSavedFilters')
            <button onclick="openCreateFilterModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Filter
            </button>
            @endcan
        </div>
    </div>

    <!-- Filter Type Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Filter Types">
                <a href="{{ route('reports.filters.index') }}" 
                   class="py-2 px-1 border-b-2 font-medium text-sm {{ !$reportType ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    All Filters
                </a>
                @foreach($availableReportTypes as $type => $name)
                <a href="{{ route('reports.filters.index', ['report_type' => $type]) }}" 
                   class="py-2 px-1 border-b-2 font-medium text-sm {{ $reportType === $type ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $name }}
                </a>
                @endforeach
            </nav>
        </div>
    </div>

    <!-- User Filters Section -->
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Your Filters</h2>
        
        @if(count($userFilters) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($userFilters as $filter)
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $filter->name }}</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($filter->report_type === 'sales') bg-blue-100 text-blue-800
                                @elseif($filter->report_type === 'manifest') bg-green-100 text-green-800
                                @elseif($filter->report_type === 'customer') bg-purple-100 text-purple-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{ $availableReportTypes[$filter->report_type] ?? ucfirst($filter->report_type) }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-1">
                            @if($filter->is_shared)
                                <span class="w-2 h-2 bg-green-500 rounded-full" title="Shared"></span>
                            @else
                                <span class="w-2 h-2 bg-gray-400 rounded-full" title="Private"></span>
                            @endif
                        </div>
                    </div>

                    <div class="text-xs text-gray-500 mb-4">
                        <div>Created: {{ $filter->created_at->format('M j, Y') }}</div>
                        <div>Updated: {{ $filter->updated_at->format('M j, Y') }}</div>
                        @if($filter->is_shared && $filter->shared_with_roles)
                            <div>Shared with: {{ implode(', ', $filter->shared_with_roles) }}</div>
                        @endif
                    </div>

                    <!-- Filter Configuration Preview -->
                    <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Filter Configuration:</h4>
                        <div class="text-xs text-gray-600 space-y-1">
                            @if(isset($filter->filter_config['date_range']))
                                <div>Date Range: <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $filter->filter_config['date_range'])) }}</span></div>
                            @endif
                            @if(isset($filter->filter_config['manifest_types']))
                                <div>Manifest Types: <span class="font-medium">{{ implode(', ', array_map('ucfirst', $filter->filter_config['manifest_types'])) }}</span></div>
                            @endif
                            @if(isset($filter->filter_config['status_filters']))
                                <div>Status: <span class="font-medium">{{ implode(', ', array_map('ucfirst', $filter->filter_config['status_filters'])) }}</span></div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex space-x-2">
                            <a href="{{ route(getReportRoute($filter->report_type), ['filter' => $filter->id]) }}" 
                               class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Apply Filter
                            </a>
                            @can('report.manageSavedFilters')
                            <button onclick="editFilter({{ $filter->id }})" 
                                    class="text-green-600 hover:text-green-800 text-sm font-medium">
                                Edit
                            </button>
                            @endcan
                        </div>
                        
                        <div class="flex space-x-1">
                            @can('report.manageSavedFilters')
                            <button onclick="duplicateFilter({{ $filter->id }})" 
                                    class="text-gray-600 hover:text-gray-800" title="Duplicate">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                            
                            @if($filter->user_id === auth()->id())
                            <button onclick="deleteFilter({{ $filter->id }})" 
                                    class="text-red-600 hover:text-red-800" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                            @endif
                            @endcan
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-white rounded-lg shadow-sm">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No saved filters</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first saved filter.</p>
                @can('report.manageSavedFilters')
                <div class="mt-6">
                    <button onclick="openCreateFilterModal()" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create Filter
                    </button>
                </div>
                @endcan
            </div>
        @endif
    </div>

    <!-- Shared Filters Section -->
    @if(count($sharedFilters) > 0)
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Shared Filters</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($sharedFilters as $filter)
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow border-l-4 border-green-500">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $filter->name }}</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($filter->report_type === 'sales') bg-blue-100 text-blue-800
                            @elseif($filter->report_type === 'manifest') bg-green-100 text-green-800
                            @elseif($filter->report_type === 'customer') bg-purple-100 text-purple-800
                            @else bg-yellow-100 text-yellow-800
                            @endif">
                            {{ $availableReportTypes[$filter->report_type] ?? ucfirst($filter->report_type) }}
                        </span>
                        <div class="text-xs text-gray-500 mt-1">
                            Shared by: {{ $filter->user ? $filter->user->first_name . ' ' . $filter->user->last_name : 'Unknown' }}
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Shared
                    </span>
                </div>

                <!-- Filter Configuration Preview -->
                <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Filter Configuration:</h4>
                    <div class="text-xs text-gray-600 space-y-1">
                        @if(isset($filter->filter_config['date_range']))
                            <div>Date Range: <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $filter->filter_config['date_range'])) }}</span></div>
                        @endif
                        @if(isset($filter->filter_config['manifest_types']))
                            <div>Manifest Types: <span class="font-medium">{{ implode(', ', array_map('ucfirst', $filter->filter_config['manifest_types'])) }}</span></div>
                        @endif
                        @if(isset($filter->filter_config['status_filters']))
                            <div>Status: <span class="font-medium">{{ implode(', ', array_map('ucfirst', $filter->filter_config['status_filters'])) }}</span></div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex space-x-2">
                        <a href="{{ route(getReportRoute($filter->report_type), ['filter' => $filter->id]) }}" 
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Apply Filter
                        </a>
                    </div>
                    
                    <div class="flex space-x-1">
                        @can('report.manageSavedFilters')
                        <button onclick="duplicateFilter({{ $filter->id }})" 
                                class="text-gray-600 hover:text-gray-800" title="Duplicate">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@php
function getReportRoute($reportType) {
    $routeMap = [
        'sales' => 'reports.sales',
        'manifest' => 'reports.manifests',
        'customer' => 'reports.customers',
        'financial' => 'reports.financial'
    ];
    
    return $routeMap[$reportType] ?? 'reports.sales';
}
@endphp

@push('scripts')
<script>
function openCreateFilterModal() {
    // TODO: Implement create filter modal
    alert('Create filter functionality will be implemented');
}

function editFilter(filterId) {
    // TODO: Implement edit filter functionality
    alert('Edit filter functionality will be implemented for filter ID: ' + filterId);
}

function duplicateFilter(filterId) {
    if (confirm('Are you sure you want to duplicate this filter?')) {
        fetch(`{{ route('reports.filters.index') }}/${filterId}/duplicate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to duplicate filter: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while duplicating the filter');
        });
    }
}

function deleteFilter(filterId) {
    if (confirm('Are you sure you want to delete this filter? This action cannot be undone.')) {
        fetch(`{{ route('reports.filters.index') }}/${filterId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete filter: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the filter');
        });
    }
}
</script>
@endpush
@endsection