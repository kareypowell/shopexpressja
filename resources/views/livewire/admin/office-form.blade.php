<div>
    <!-- Success/Error Messages -->
    @if($successMessage)
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded" 
             x-data="{ show: true }" 
             x-show="show" 
             x-transition>
            <div class="flex justify-between items-center">
                <span>{{ $successMessage }}</span>
                <button @click="show = false; $wire.clearMessages()" class="text-green-700 hover:text-green-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if($errorMessage)
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded"
             x-data="{ show: true }" 
             x-show="show" 
             x-transition>
            <div class="flex justify-between items-center">
                <span>{{ $errorMessage }}</span>
                <button @click="show = false; $wire.clearMessages()" class="text-red-700 hover:text-red-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $this->pageTitle }}</h2>
                <p class="mt-1 text-sm text-gray-600">
                    @if($isEditing)
                        Update the office information below.
                    @else
                        Fill in the details to create a new office location.
                    @endif
                </p>
            </div>
            <div>
                <button wire:click="cancel" 
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <form wire:submit.prevent="save" class="space-y-6 p-6">
            <!-- Office Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Office Name <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="text" 
                           id="name"
                           wire:model.lazy="name" 
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-300 @enderror"
                           placeholder="Enter office name (e.g., Main Office, Warehouse A)"
                           maxlength="255">
                    @error('name')
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    @enderror
                </div>
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    A descriptive name for this office location.
                </p>
            </div>

            <!-- Office Address -->
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                    Office Address <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <textarea id="address"
                              wire:model.lazy="address" 
                              rows="3"
                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('address') border-red-300 @enderror"
                              placeholder="Enter the complete office address including street, city, state, and postal code"
                              maxlength="500"></textarea>
                    @error('address')
                        <div class="absolute top-2 right-2 pointer-events-none">
                            <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    @enderror
                </div>
                @error('address')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    The complete physical address of this office location.
                </p>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <div class="text-sm text-gray-500">
                    <span class="text-red-500">*</span> Required fields
                </div>
                <div class="flex space-x-3">
                    <button type="button" 
                            wire:click="cancel"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-25 transition ease-in-out duration-150">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($isEditing)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            @endif
                        </svg>
                        {{ $this->submitButtonText }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Additional Information -->
    @if($isEditing && $office)
        <div class="mt-6 bg-gray-50 rounded-lg p-6 border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Office Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $office->manifest_count }}</div>
                    <div class="text-sm text-gray-600">Associated Manifests</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $office->package_count }}</div>
                    <div class="text-sm text-gray-600">Associated Packages</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $office->profile_count }}</div>
                    <div class="text-sm text-gray-600">Associated Profiles</div>
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500">
                <p><strong>Created:</strong> {{ $office->created_at->format('F j, Y \a\t g:i A') }}</p>
                @if($office->updated_at && $office->updated_at != $office->created_at)
                    <p><strong>Last Updated:</strong> {{ $office->updated_at->format('F j, Y \a\t g:i A') }}</p>
                @endif
            </div>
        </div>
    @endif
</div>