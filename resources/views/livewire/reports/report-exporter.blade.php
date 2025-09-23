<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Export Report</h3>
            <p class="text-sm text-gray-600">Download your report in various formats</p>
        </div>
        
        <div class="flex items-center space-x-3">
            @if($canExport)
                <button 
                    wire:click="$set('showExportDialog', true)"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export Report
                </button>
            @else
                <div class="text-sm text-gray-500">
                    You don't have permission to export reports
                </div>
            @endif
            
            @if(!empty($activeExports))
                <button 
                    wire:click="refreshActiveExports"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh Status
                </button>
            @endif
        </div>
    </div>

    {{-- Error Display --}}
    @if($error)
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Export Error</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>{{ $error }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Success Message --}}
    @if($successMessage)
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">Success</h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p>{{ $successMessage }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Active Exports --}}
    @if(!empty($activeExports))
        <div class="mb-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">Export Status</h4>
            <div class="space-y-4">
                @foreach($activeExports as $export)
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        @if($export['status'] === 'completed')
                                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        @elseif($export['status'] === 'processing')
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                <svg class="animate-spin w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <h5 class="text-sm font-medium text-gray-900">
                                            {{ ucwords(str_replace('_', ' ', $export['report_type'])) }} - {{ strtoupper($export['export_format']) }}
                                        </h5>
                                        <p class="text-sm text-gray-600">
                                            Started: {{ $export['created_at'] }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $export['estimated_completion'] }}
                                        </p>
                                    </div>
                                </div>
                                
                                {{-- Progress Bar --}}
                                @if($export['status'] === 'processing')
                                    <div class="mt-3">
                                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                                            <span>Processing...</span>
                                            <span>{{ $export['progress'] }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $export['progress'] }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="flex items-center space-x-2 ml-4">
                                @if($export['status'] === 'completed')
                                    <button 
                                        wire:click="downloadExport('{{ $export['id'] }}')"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors duration-200"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Download
                                    </button>
                                @elseif(in_array($export['status'], ['pending', 'processing']))
                                    <button 
                                        wire:click="cancelExport('{{ $export['id'] }}')"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition-colors duration-200"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Cancel
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if(collect($activeExports)->where('status', 'completed')->count() > 0)
                <div class="mt-4 text-center">
                    <button 
                        wire:click="clearCompletedExports"
                        class="text-sm text-gray-600 hover:text-gray-900"
                    >
                        Clear completed exports
                    </button>
                </div>
            @endif
        </div>
    @endif

    {{-- Quick Export Options --}}
    @if($canExport && !empty($reportData))
        <div class="bg-gray-50 rounded-lg p-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">Quick Export</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($availableFormats as $format => $config)
                    <button 
                        wire:click="$set('exportFormat', '{{ $format }}'); $set('showExportDialog', true)"
                        class="flex items-center p-4 bg-white border border-gray-200 rounded-lg hover:border-blue-300 hover:shadow-sm transition-all duration-200"
                    >
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($config['icon'] === 'document-text')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    @elseif($config['icon'] === 'table')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0V4a1 1 0 011-1h16a1 1 0 011 1v16a1 1 0 01-1 1H4a1 1 0 01-1-1z"></path>
                                    @endif
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 text-left">
                            <h5 class="text-sm font-medium text-gray-900">{{ $config['name'] }}</h5>
                            <p class="text-xs text-gray-600">{{ $config['description'] }}</p>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Export Configuration Dialog --}}
    @if($showExportDialog)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Export Configuration</h3>
                    <button 
                        wire:click="$set('showExportDialog', false)"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                {{-- Export Format Selection --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Export Format</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @foreach($availableFormats as $format => $config)
                            <label class="relative">
                                <input 
                                    type="radio" 
                                    wire:model="exportFormat" 
                                    value="{{ $format }}"
                                    class="sr-only"
                                >
                                <div class="flex items-center p-3 border rounded-lg cursor-pointer transition-all duration-200 {{ $exportFormat === $format ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 {{ $exportFormat === $format ? 'bg-blue-100' : 'bg-gray-100' }} rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 {{ $exportFormat === $format ? 'text-blue-600' : 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if($config['icon'] === 'document-text')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                @elseif($config['icon'] === 'table')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0V4a1 1 0 011-1h16a1 1 0 011 1v16a1 1 0 01-1 1H4a1 1 0 01-1-1z"></path>
                                                @endif
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium {{ $exportFormat === $format ? 'text-blue-900' : 'text-gray-900' }}">
                                            {{ $config['name'] }}
                                        </div>
                                        <div class="text-xs {{ $exportFormat === $format ? 'text-blue-700' : 'text-gray-600' }}">
                                            {{ $config['description'] }}
                                        </div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
                
                {{-- Export Options --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Export Options</label>
                    <div class="space-y-3">
                        @if($this->getFormatConfig($exportFormat)['supports_charts'] ?? false)
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    wire:model="includeCharts"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm text-gray-700">Include charts and visualizations</span>
                            </label>
                        @endif
                        
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                wire:model="includeRawData"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >
                            <span class="ml-2 text-sm text-gray-700">Include raw data tables</span>
                        </label>
                    </div>
                </div>
                
                {{-- PDF-specific Options --}}
                @if($exportFormat === 'pdf' && ($this->getFormatConfig($exportFormat)['supports_options'] ?? false))
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="paperSize" class="block text-sm font-medium text-gray-700 mb-2">Paper Size</label>
                            <select 
                                wire:model="paperSize" 
                                id="paperSize"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                @foreach($paperSizes as $size => $label)
                                    <option value="{{ $size }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label for="orientation" class="block text-sm font-medium text-gray-700 mb-2">Orientation</label>
                            <select 
                                wire:model="orientation" 
                                id="orientation"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                @foreach($orientations as $orient => $label)
                                    <option value="{{ $orient }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
                
                {{-- Action Buttons --}}
                <div class="flex justify-end space-x-3">
                    <button 
                        wire:click="$set('showExportDialog', false)"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="startExport"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 rounded-lg transition-colors duration-200"
                    >
                        <svg wire:loading.remove class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <svg wire:loading class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove>Start Export</span>
                        <span wire:loading>Starting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading Overlay --}}
    <div wire:loading.flex wire:target="startExport,downloadExport" class="absolute inset-0 bg-white bg-opacity-75 items-center justify-center">
        <div class="flex items-center">
            <svg class="animate-spin h-5 w-5 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-900">Processing export...</span>
        </div>
    </div>
</div>

{{-- Export functionality handled by global reports dashboard --}}