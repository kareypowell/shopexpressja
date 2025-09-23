<div class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="max-w-lg w-full bg-white shadow-lg rounded-lg p-8 mx-4">
        <div class="text-center">
            <!-- Error Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100">
                <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            
            <!-- Error Title -->
            <h3 class="mt-4 text-xl font-semibold text-gray-900">
                @if(isset($reportType))
                    {{ ucfirst(str_replace('_', ' ', $reportType)) }} Report Error
                @else
                    Report Error
                @endif
            </h3>
            
            <!-- Error Message -->
            <p class="mt-2 text-sm text-gray-600 max-w-md mx-auto">
                {{ $error ?? 'An unexpected error occurred while loading the report. Our team has been notified and is working to resolve the issue.' }}
            </p>
            
            <!-- Error ID for Support -->
            <div class="mt-4 p-3 bg-gray-50 rounded-md">
                <p class="text-xs text-gray-500">
                    Error ID: {{ Str::random(8) }} • {{ now()->format('Y-m-d H:i:s') }}
                </p>
            </div>
            
            <!-- Action Buttons -->
            <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
                @if($canRetry ?? true)
                    <button 
                        onclick="window.location.reload()" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Try Again
                    </button>
                @endif
                
                <a 
                    href="{{ route('reports.index') }}" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Reports
                </a>
            </div>
            
            <!-- Support Contact -->
            <div class="mt-6 text-xs text-gray-500">
                <p>If this problem persists, please contact support with the Error ID above.</p>
            </div>
        </div>
    </div>
</div>