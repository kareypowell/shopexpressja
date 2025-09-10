<div class="manifest-lock-status">
    <!-- Status Display Section -->
    <div class="flex items-center justify-between mb-4 p-4 bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="flex items-center space-x-3">
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-700">Status:</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $manifest->status_badge_class }}">
                    {{ $manifest->status_label }}
                </span>
            </div>
            
            @if(!$manifest->is_open)
                <div class="flex items-center space-x-1 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM12 9V7a4 4 0 118 0v2"/>
                    </svg>
                    <span>Locked - View Only</span>
                </div>
            @endif
        </div>
        
        <!-- Action Buttons -->
        <div class="flex space-x-2">
            @if(!$manifest->is_open && auth()->user()->can('unlock', $manifest))
                <button wire:click="showUnlockModal" 
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                    Unlock Manifest
                </button>
            @elseif($manifest->is_open)
                <div class="flex items-center space-x-2">
                    <div class="inline-flex items-center px-3 py-2 text-sm text-green-700 bg-green-50 rounded-md">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0v4m0 0H6m2 0h2m0 0h2m2 0h2"/>
                        </svg>
                        Editing Enabled
                    </div>
                    @if(auth()->user()->can('edit', $manifest))
                        <button wire:click="showLockModal" 
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Lock Manifest
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Error Messages -->
    @error('unlock')
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
            <div class="flex items-center">
                <svg class="w-4 h-4 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <span class="text-sm text-red-700">{{ $message }}</span>
            </div>
        </div>
    @enderror

    @error('lock')
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
            <div class="flex items-center">
                <svg class="w-4 h-4 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <span class="text-sm text-red-700">{{ $message }}</span>
            </div>
        </div>
    @enderror

    <!-- Success Message -->
    @if(session()->has('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-md">
            <div class="flex items-center">
                <svg class="w-4 h-4 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm text-green-700">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Unlock Modal -->
    @if($showUnlockModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" 
             x-data="{ show: @entangle('showUnlockModal') }"
             x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click.self="$wire.cancelUnlock()"
             @keydown.escape.window="$wire.cancelUnlock()">
            
            <div class="relative w-full max-w-md sm:max-w-lg md:max-w-xl lg:max-w-2xl mx-auto bg-white rounded-lg shadow-xl border border-gray-200"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 @click.stop>
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg sm:text-xl font-semibold text-gray-900">Unlock Manifest</h3>
                            <p class="text-sm text-gray-600 mt-1">Manifest #{{ $manifest->id }}</p>
                        </div>
                    </div>
                    <button wire:click="cancelUnlock" 
                            class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 rounded-md p-1 transition-colors duration-200"
                            aria-label="Close modal">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Modal Content -->
                <div class="p-4 sm:p-6">
                    <!-- Warning Notice -->
                    <div class="flex items-start space-x-3 p-3 sm:p-4 bg-yellow-50 border border-yellow-200 rounded-lg mb-4 sm:mb-6">
                        <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-yellow-800">Important Notice</p>
                            <p class="text-sm text-yellow-700 mt-1">
                                Unlocking this manifest will allow editing of package details. This action will be logged for audit purposes and may require additional approval.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Form Section -->
                    <div class="space-y-4">
                        <div>
                            <label for="unlockReason" class="block text-sm font-medium text-gray-700 mb-2">
                                Reason for Unlocking <span class="text-red-500" aria-label="Required">*</span>
                            </label>
                            <div class="relative">
                                <textarea wire:model.lazy="unlockReason" 
                                          id="unlockReason"
                                          class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none transition-colors duration-200 @error('unlockReason') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                          rows="4" 
                                          placeholder="Please provide a detailed reason for unlocking this manifest. Include specific business justification and expected changes (minimum 10 characters)..."
                                          maxlength="500"
                                          aria-describedby="unlock-reason-help unlock-reason-counter"
                                          required></textarea>
                            </div>
                            
                            <!-- Help Text and Character Counter -->
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-2 space-y-1 sm:space-y-0">
                                <div class="flex-1">
                                    @error('unlockReason') 
                                        <span class="text-red-500 text-xs font-medium" id="unlock-reason-help">{{ $message }}</span> 
                                    @else
                                        <span class="text-xs text-gray-500" id="unlock-reason-help">
                                            Minimum 10 characters required. Be specific about the business need.
                                        </span>
                                    @enderror
                                </div>
                                <span class="text-xs text-gray-400 font-mono" id="unlock-reason-counter">
                                    {{ strlen($unlockReason) }}/500
                                </span>
                            </div>
                        </div>

                        <!-- Additional Context (if needed) -->
                        <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Manifest Details</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-600">Status:</span>
                                    <span class="ml-2 font-medium">{{ $manifest->status_label }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Packages:</span>
                                    <span class="ml-2 font-medium">{{ $manifest->packages->count() }}</span>
                                </div>
                                @if($manifest->created_at)
                                <div class="sm:col-span-2">
                                    <span class="text-gray-600">Created:</span>
                                    <span class="ml-2 font-medium">{{ $manifest->created_at->format('M j, Y g:i A') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Actions -->
                <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 p-4 sm:p-6 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                    <button wire:click="cancelUnlock" 
                            class="w-full sm:w-auto px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200 font-medium">
                        Cancel
                    </button>
                    <button wire:click="unlockManifest" 
                            class="w-full sm:w-auto px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                            :disabled="!$wire.unlockReason || $wire.unlockReason.length < 10"
                        <span wire:loading.remove wire:target="unlockManifest" class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                            </svg>
                            Unlock Manifest
                        </span>
                        <span wire:loading wire:target="unlockManifest" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Unlocking...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Lock Modal -->
    @if($showLockModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" 
             x-data="{ show: @entangle('showLockModal') }"
             x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click.self="$wire.cancelLock()"
             @keydown.escape.window="$wire.cancelLock()">
            
            <div class="relative w-full max-w-md sm:max-w-lg md:max-w-xl lg:max-w-2xl mx-auto bg-white rounded-lg shadow-xl border border-gray-200"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 @click.stop>
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg sm:text-xl font-semibold text-gray-900">Lock Manifest</h3>
                            <p class="text-sm text-gray-600 mt-1">Manifest #{{ $manifest->id }}</p>
                        </div>
                    </div>
                    <button wire:click="cancelLock" 
                            class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 rounded-md p-1 transition-colors duration-200"
                            aria-label="Close modal">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Modal Content -->
                <div class="p-4 sm:p-6">
                    <!-- Warning Notice -->
                    <div class="flex items-start space-x-3 p-3 sm:p-4 bg-red-50 border border-red-200 rounded-lg mb-4 sm:mb-6">
                        <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-red-800">Important Notice</p>
                            <p class="text-sm text-red-700 mt-1">
                                Locking this manifest will prevent further editing of package details. This action will be logged for audit purposes and can only be reversed by unlocking.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Form Section -->
                    <div class="space-y-4">
                        <div>
                            <label for="lockReason" class="block text-sm font-medium text-gray-700 mb-2">
                                Reason for Locking <span class="text-red-500" aria-label="Required">*</span>
                            </label>
                            <div class="relative">
                                <textarea wire:model.lazy="lockReason" 
                                          id="lockReason"
                                          class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none transition-colors duration-200 @error('lockReason') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                          rows="4" 
                                          placeholder="Please provide a detailed reason for locking this manifest. Include completion status and any final notes (minimum 10 characters)..."
                                          maxlength="500"
                                          aria-describedby="lock-reason-help lock-reason-counter"
                                          required></textarea>
                            </div>
                            
                            <!-- Help Text and Character Counter -->
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-2 space-y-1 sm:space-y-0">
                                <div class="flex-1">
                                    @error('lockReason') 
                                        <span class="text-red-500 text-xs font-medium" id="lock-reason-help">{{ $message }}</span> 
                                    @else
                                        <span class="text-xs text-gray-500" id="lock-reason-help">
                                            Minimum 10 characters required. Describe why the manifest is ready to be locked.
                                        </span>
                                    @enderror
                                </div>
                                <span class="text-xs text-gray-400 font-mono" id="lock-reason-counter">
                                    {{ strlen($lockReason) }}/500
                                </span>
                            </div>
                        </div>

                        <!-- Additional Context (if needed) -->
                        <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Manifest Details</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-600">Status:</span>
                                    <span class="ml-2 font-medium">{{ $manifest->status_label }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Packages:</span>
                                    <span class="ml-2 font-medium">{{ $manifest->packages->count() }}</span>
                                </div>
                                @if($manifest->created_at)
                                <div class="sm:col-span-2">
                                    <span class="text-gray-600">Created:</span>
                                    <span class="ml-2 font-medium">{{ $manifest->created_at->format('M j, Y g:i A') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Actions -->
                <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 p-4 sm:p-6 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                    <button wire:click="cancelLock" 
                            class="w-full sm:w-auto px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200 font-medium">
                        Cancel
                    </button>
                    <button wire:click="lockManifest" 
                            class="w-full sm:w-auto px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                            :disabled="!$wire.lockReason || $wire.lockReason.length < 10">
                        <span wire:loading.remove wire:target="lockManifest" class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Lock Manifest
                        </span>
                        <span wire:loading wire:target="lockManifest" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Locking...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>