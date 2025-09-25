<div class="manifest-summary bg-gradient-to-br from-red-50 to-red-100 p-8 rounded-xl shadow-lg border border-red-200">
    <div class="flex items-center space-x-3 mb-4">
        <div class="p-3 bg-red-100 rounded-lg">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
        </div>
        <div>
            <h3 class="text-xl font-bold text-red-900">Manifest Summary Error</h3>
            <p class="text-sm text-red-700">{{ $errorMessage ?? 'Unable to load manifest summary' }}</p>
        </div>
    </div>
    
    <div class="bg-white rounded-lg p-4 border border-red-200">
        <p class="text-sm text-gray-600 mb-3">
            There was an issue loading the manifest summary. This error has been logged for investigation.
        </p>
        
        <button onclick="window.location.reload()" 
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Reload Page
        </button>
    </div>
</div>