@extends('layouts.app')

@section('title', 'Report Templates')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Report Templates</h1>
            <p class="mt-2 text-gray-600">Manage and configure report templates for consistent reporting</p>
        </div>
        @can('report.createReportTemplates')
        <a href="{{ route('reports.templates.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Create Template
        </a>
        @endcan
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-64">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" 
                       placeholder="Search templates..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="min-w-48">
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" id="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="sales" {{ request('type') === 'sales' ? 'selected' : '' }}>Sales</option>
                    <option value="manifest" {{ request('type') === 'manifest' ? 'selected' : '' }}>Manifest</option>
                    <option value="customer" {{ request('type') === 'customer' ? 'selected' : '' }}>Customer</option>
                    <option value="financial" {{ request('type') === 'financial' ? 'selected' : '' }}>Financial</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        @forelse($templates as $template)
        <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $template->name }}</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($template->type === 'sales') bg-blue-100 text-blue-800
                        @elseif($template->type === 'manifest') bg-green-100 text-green-800
                        @elseif($template->type === 'customer') bg-purple-100 text-purple-800
                        @else bg-yellow-100 text-yellow-800
                        @endif">
                        {{ ucfirst($template->type) }}
                    </span>
                </div>
                <div class="flex items-center space-x-1">
                    @if($template->is_active)
                        <span class="w-2 h-2 bg-green-500 rounded-full" title="Active"></span>
                    @else
                        <span class="w-2 h-2 bg-gray-400 rounded-full" title="Inactive"></span>
                    @endif
                </div>
            </div>

            @if($template->description)
            <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $template->description }}</p>
            @endif

            <div class="text-xs text-gray-500 mb-4">
                <div>Created: {{ $template->created_at->format('M j, Y') }}</div>
                <div>Updated: {{ $template->updated_at->format('M j, Y') }}</div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex space-x-2">
                    <a href="{{ route('reports.templates.show', $template) }}" 
                       class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        View
                    </a>
                    @can('report.updateReportTemplates')
                    <a href="{{ route('reports.templates.edit', $template) }}" 
                       class="text-green-600 hover:text-green-800 text-sm font-medium">
                        Edit
                    </a>
                    @endcan
                </div>
                
                <div class="flex space-x-1">
                    @can('report.createReportTemplates')
                    <form method="POST" action="{{ route('reports.templates.duplicate', $template) }}" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-600 hover:text-gray-800" title="Duplicate">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </form>
                    @endcan
                    
                    @can('report.deleteReportTemplates')
                    <form method="POST" action="{{ route('reports.templates.destroy', $template) }}" 
                          class="inline" 
                          onsubmit="return confirm('Are you sure you want to delete this template?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No templates found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new report template.</p>
                @can('report.createReportTemplates')
                <div class="mt-6">
                    <a href="{{ route('reports.templates.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create Template
                    </a>
                </div>
                @endcan
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($templates->hasPages())
    <div class="flex justify-center">
        {{ $templates->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection