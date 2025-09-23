@extends('layouts.app')

@section('title', 'Customer Analytics Report')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @try
            <!-- Report Dashboard Component -->
            @livewire('reports.report-dashboard', [
                'reportType' => 'customers',
                'reportTitle' => 'Customer Analytics Report'
            ])
        @catch(\Exception $e)
            <!-- Error Fallback -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Report Temporarily Unavailable</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>We're experiencing technical difficulties with the Customer Analytics report. Please try refreshing the page or contact support if the issue persists.</p>
                        </div>
                        <div class="mt-4">
                            <button onclick="window.location.reload()" class="bg-red-100 px-3 py-2 text-xs font-semibold text-red-800 hover:bg-red-200 rounded">
                                Refresh Page
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endtry
    </div>
</div>
@endsection