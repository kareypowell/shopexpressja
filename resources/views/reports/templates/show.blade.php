@extends('layouts.app')

@section('title', 'Report Template: ' . $template->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $template->name }}</h1>
                <p class="text-gray-600 mt-1">{{ $template->description }}</p>
            </div>
            <div class="flex items-center space-x-3">
                @can('report.updateReportTemplates')
                    <a href="{{ route('reports.templates.edit', $template) }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit Template
                    </a>
                @endcan
                
                <a href="{{ route('reports.templates.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Templates
                </a>
            </div>
        </div>
    </div>

    <!-- Template Details -->
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Template Details</h3>
        </div>
        
        <div class="px-6 py-4">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $template->name }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ ucfirst(str_replace('_', ' ', $template->type)) }}
                        </span>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $template->description ?: 'No description provided' }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            @if($template->is_active) bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Created By</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $template->creator ? $template->creator->first_name . ' ' . $template->creator->last_name : 'System' }}
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500">Created At</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $template->created_at->format('M j, Y g:i A') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Template Configuration -->
    @if($template->template_config)
        <div class="mt-6 bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Configuration</h3>
            </div>
            
            <div class="px-6 py-4">
                <div class="bg-gray-50 rounded-md p-4">
                    <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ json_encode($template->template_config, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </div>
    @endif

    <!-- Default Filters -->
    @if($template->default_filters)
        <div class="mt-6 bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Default Filters</h3>
            </div>
            
            <div class="px-6 py-4">
                <div class="bg-gray-50 rounded-md p-4">
                    <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ json_encode($template->default_filters, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </div>
    @endif

    <!-- Actions -->
    <div class="mt-6 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            @can('report.generateReports')
                <a href="{{ route('reports.index', ['template' => $template->id]) }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Generate Report
                </a>
            @endcan
        </div>
        
        @can('report.deleteReportTemplates')
            <form method="POST" action="{{ route('reports.templates.destroy', $template) }}" 
                  onsubmit="return confirm('Are you sure you want to delete this template? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Template
                </button>
            </form>
        @endcan
    </div>
</div>
@endsection