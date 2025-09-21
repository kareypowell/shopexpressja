<div>
    <!-- Modal -->
    <div x-data="{ show: @entangle('showModal') }" 
         x-show="show" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        
        <!-- Modal content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
                
                @if($auditLog)
                    <!-- Header -->
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $eventTypeColor }}">
                                        {{ ucfirst(str_replace('_', ' ', $auditLog->event_type)) }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        Audit Log Details
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        {{ $auditLog->created_at->format('F j, Y \a\t g:i A') }}
                                        @if($auditLog->user)
                                            • {{ $auditLog->user->full_name }}
                                        @else
                                            • System
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <button wire:click="closeModal" 
                                    class="text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-200">
                        <nav class="flex space-x-8 px-6" aria-label="Tabs">
                            <button wire:click="setActiveTab('details')" 
                                    class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'details' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Details
                            </button>
                            
                            @if($valueChanges)
                                <button wire:click="setActiveTab('changes')" 
                                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'changes' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                    Changes ({{ count($valueChanges) }})
                                </button>
                            @endif
                            
                            <button wire:click="setActiveTab('context')" 
                                    class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'context' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                User Context
                            </button>
                            
                            @if($relatedLogs && $relatedLogs->isNotEmpty())
                                <button wire:click="setActiveTab('related')" 
                                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'related' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                    Related Activity
                                </button>
                            @endif
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="px-6 py-6 max-h-[60vh] overflow-y-auto">
                        
                        <!-- Details Tab -->
                        @if($activeTab === 'details')
                            <div class="space-y-6">
                                <!-- Basic Information -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <h4 class="text-lg font-medium text-gray-900">Event Information</h4>
                                        
                                        <div class="space-y-3">
                                            <div class="flex justify-between">
                                                <span class="text-sm font-medium text-gray-500">Event Type:</span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $eventTypeColor }}">
                                                    {{ ucfirst(str_replace('_', ' ', $auditLog->event_type)) }}
                                                </span>
                                            </div>
                                            
                                            <div class="flex justify-between">
                                                <span class="text-sm font-medium text-gray-500">Action:</span>
                                                <span class="text-sm text-gray-900 font-medium">{{ ucfirst($auditLog->action) }}</span>
                                            </div>
                                            
                                            @if($auditLog->auditable_type)
                                                <div class="flex justify-between">
                                                    <span class="text-sm font-medium text-gray-500">Model:</span>
                                                    <span class="text-sm text-gray-900">{{ class_basename($auditLog->auditable_type) }}</span>
                                                </div>
                                            @endif
                                            
                                            @if($auditLog->auditable_id)
                                                <div class="flex justify-between">
                                                    <span class="text-sm font-medium text-gray-500">Model ID:</span>
                                                    <span class="text-sm text-gray-900 font-mono">{{ $auditLog->auditable_id }}</span>
                                                </div>
                                            @endif
                                            
                                            <div class="flex justify-between">
                                                <span class="text-sm font-medium text-gray-500">Timestamp:</span>
                                                <span class="text-sm text-gray-900">{{ $auditLog->created_at->format('M j, Y g:i:s A') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        <h4 class="text-lg font-medium text-gray-900">Request Information</h4>
                                        
                                        <div class="space-y-3">
                                            @if($auditLog->url)
                                                <div>
                                                    <span class="text-sm font-medium text-gray-500">URL:</span>
                                                    <div class="mt-1 text-sm text-gray-900 font-mono bg-gray-50 p-2 rounded break-all">
                                                        {{ $auditLog->url }}
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            @if($auditLog->ip_address)
                                                <div class="flex justify-between">
                                                    <span class="text-sm font-medium text-gray-500">IP Address:</span>
                                                    <span class="text-sm text-gray-900 font-mono">{{ $auditLog->ip_address }}</span>
                                                </div>
                                            @endif
                                            
                                            @if($auditLog->user_agent)
                                                <div>
                                                    <span class="text-sm font-medium text-gray-500">User Agent:</span>
                                                    <div class="mt-1 text-xs text-gray-600 bg-gray-50 p-2 rounded break-all">
                                                        {{ $auditLog->user_agent }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Data -->
                                @if($formattedAdditionalData)
                                    <div>
                                        <h4 class="text-lg font-medium text-gray-900 mb-3">Additional Data</h4>
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ json_encode($formattedAdditionalData, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Changes Tab -->
                        @if($activeTab === 'changes' && $valueChanges)
                            <div class="space-y-4">
                                <h4 class="text-lg font-medium text-gray-900">Field Changes</h4>
                                
                                <div class="space-y-4">
                                    @foreach($valueChanges as $change)
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <h5 class="font-medium text-gray-900">{{ $change['field'] }}</h5>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @if($change['change_type'] === 'added') bg-green-100 text-green-800
                                                    @elseif($change['change_type'] === 'removed') bg-red-100 text-red-800
                                                    @else bg-yellow-100 text-yellow-800
                                                    @endif">
                                                    {{ ucfirst($change['change_type']) }}
                                                </span>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <!-- Old Value -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-500 mb-2">
                                                        @if($change['change_type'] === 'added')
                                                            Previous (Empty)
                                                        @else
                                                            Previous Value
                                                        @endif
                                                    </label>
                                                    <div class="bg-red-50 border border-red-200 rounded p-3 min-h-[60px]">
                                                        @if($change['old_value'] !== null)
                                                            <div class="text-sm">{!! $this->formatValue($change['old_value']) !!}</div>
                                                        @else
                                                            <span class="text-gray-400 italic text-sm">No previous value</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <!-- New Value -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-500 mb-2">
                                                        @if($change['change_type'] === 'removed')
                                                            New (Removed)
                                                        @else
                                                            New Value
                                                        @endif
                                                    </label>
                                                    <div class="bg-green-50 border border-green-200 rounded p-3 min-h-[60px]">
                                                        @if($change['new_value'] !== null)
                                                            <div class="text-sm">{!! $this->formatValue($change['new_value']) !!}</div>
                                                        @else
                                                            <span class="text-gray-400 italic text-sm">Value removed</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- User Context Tab -->
                        @if($activeTab === 'context')
                            <div class="space-y-6">
                                <h4 class="text-lg font-medium text-gray-900">User & Session Context</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- User Information -->
                                    <div class="space-y-4">
                                        <h5 class="font-medium text-gray-900">User Information</h5>
                                        
                                        @if($userContext && isset($userContext['user']) && $userContext['user'])
                                            <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                                <div class="flex items-center space-x-3">
                                                    <div class="flex-shrink-0">
                                                        <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                                            <span class="text-white font-medium text-sm">
                                                                @if($userContext['user']->first_name && $userContext['user']->last_name)
                                                                    {{ substr($userContext['user']->first_name, 0, 1) }}{{ substr($userContext['user']->last_name, 0, 1) }}
                                                                @else
                                                                    U
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">{{ $userContext['user']->full_name ?? 'Unknown User' }}</p>
                                                        <p class="text-sm text-gray-500">{{ $userContext['user']->email ?? 'No email' }}</p>
                                                    </div>
                                                </div>
                                                
                                                @if($userContext && isset($userContext['user']) && $userContext['user'] && $userContext['user']->role)
                                                    <div class="flex justify-between">
                                                        <span class="text-sm font-medium text-gray-500">Role:</span>
                                                        <span class="text-sm text-gray-900">{{ $userContext['user']->role->name }}</span>
                                                    </div>
                                                @endif
                                                
                                                @if($userContext && isset($userContext['user']) && $userContext['user'])
                                                    <div class="flex justify-between">
                                                        <span class="text-sm font-medium text-gray-500">User ID:</span>
                                                        <span class="text-sm text-gray-900 font-mono">{{ $userContext['user']->id }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="bg-gray-50 rounded-lg p-4">
                                                <p class="text-sm text-gray-500 italic">System-generated event (no user context)</p>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Session & Request Information -->
                                    <div class="space-y-4">
                                        <h5 class="font-medium text-gray-900">Session & Request</h5>
                                        
                                        <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                            @if($userContext && isset($userContext['ip_address']) && $userContext['ip_address'])
                                                <div class="flex justify-between">
                                                    <span class="text-sm font-medium text-gray-500">IP Address:</span>
                                                    <span class="text-sm text-gray-900 font-mono">{{ $userContext['ip_address'] }}</span>
                                                </div>
                                            @endif
                                            
                                            @if($userContext && isset($userContext['session_id']) && $userContext['session_id'])
                                                <div class="flex justify-between">
                                                    <span class="text-sm font-medium text-gray-500">Session ID:</span>
                                                    <span class="text-sm text-gray-900 font-mono">{{ substr($userContext['session_id'], 0, 8) }}...</span>
                                                </div>
                                            @endif
                                            
                                            @if($userContext && isset($userContext['timestamp']) && $userContext['timestamp'])
                                                <div class="flex justify-between">
                                                    <span class="text-sm font-medium text-gray-500">Timestamp:</span>
                                                    <span class="text-sm text-gray-900">{{ $userContext['timestamp']->format('M j, Y g:i:s A T') }}</span>
                                                </div>
                                                
                                                <div class="flex justify-between">
                                                    <span class="text-sm font-medium text-gray-500">Time Ago:</span>
                                                    <span class="text-sm text-gray-900">{{ $userContext['timestamp']->diffForHumans() }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Related Activity Tab -->
                        @if($activeTab === 'related' && $relatedLogs && $relatedLogs->isNotEmpty())
                            <div class="space-y-6">
                                <h4 class="text-lg font-medium text-gray-900">Related Activity</h4>
                                
                                @foreach($relatedLogs as $relatedGroup)
                                    <div class="space-y-3">
                                        <h5 class="font-medium text-gray-700 border-b border-gray-200 pb-2">
                                            {{ $relatedGroup['title'] }} ({{ count($relatedGroup['logs']) }})
                                        </h5>
                                        
                                        <div class="space-y-2">
                                            @if(empty($relatedGroup['logs']))
                                                <p class="text-sm text-gray-500 italic">No related logs found</p>
                                            @else
                                                @foreach($relatedGroup['logs'] as $relatedLog)
                                                    @if($relatedLog)
                                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer"
                                                             wire:click="showAuditLogDetails({{ $relatedLog['id'] ?? 0 }})">
                                                            <div class="flex items-center space-x-3">
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                                    @if(($relatedLog['event_type'] ?? '') === 'authentication') bg-blue-100 text-blue-800
                                                                    @elseif(($relatedLog['event_type'] ?? '') === 'authorization') bg-purple-100 text-purple-800
                                                                    @elseif(($relatedLog['event_type'] ?? '') === 'security_event') bg-red-100 text-red-800
                                                                    @elseif(str_contains($relatedLog['event_type'] ?? '', 'model_')) bg-green-100 text-green-800
                                                                    @elseif(($relatedLog['event_type'] ?? '') === 'business_action') bg-yellow-100 text-yellow-800
                                                                    @elseif(($relatedLog['event_type'] ?? '') === 'financial_transaction') bg-indigo-100 text-indigo-800
                                                                    @else bg-gray-100 text-gray-800
                                                                    @endif">
                                                                    {{ !empty($relatedLog['action']) ? ucfirst($relatedLog['action']) : 'Unknown' }}
                                                                </span>
                                                                
                                                                <div>
                                                                    <p class="text-sm font-medium text-gray-900">
                                                                        {{ !empty($relatedLog['event_type']) ? ucfirst(str_replace('_', ' ', $relatedLog['event_type'])) : 'Unknown Event' }}
                                                                        @if(!empty($relatedLog['auditable_type']))
                                                                            • {{ class_basename($relatedLog['auditable_type']) }}
                                                                        @endif
                                                                    </p>
                                                                    <p class="text-xs text-gray-500">
                                                                        {{ !empty($relatedLog['created_at']) ? \Carbon\Carbon::parse($relatedLog['created_at'])->format('M j, g:i A') : 'Unknown Date' }}
                                                                        @if(!empty($relatedLog['user']))
                                                                            • {{ $relatedLog['user']['full_name'] ?? $relatedLog['user']['first_name'] . ' ' . $relatedLog['user']['last_name'] ?? 'Unknown User' }}
                                                                        @else
                                                                            • System
                                                                        @endif
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            
                                                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                        <button wire:click="closeModal" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Close
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>