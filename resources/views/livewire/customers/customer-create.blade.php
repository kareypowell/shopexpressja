<div>
    <x-breadcrumb :items="$breadcrumbs" />
    
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create New Customer</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Add a new customer account with complete profile information
                    </p>
                </div>
                <div class="flex space-x-3">
                    <button wire:click="cancel" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </button>
                    <button wire:click="create" 
                            wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                        <svg wire:loading wire:target="create" class="-ml-1 mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg wire:loading.remove wire:target="create" class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Customer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if (session()->has('success'))
        <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('warning'))
        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-yellow-800">
                        {{ session('warning') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('email_info'))
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-800">
                        {{ session('email_info') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Email Status Display -->
    @if($emailStatus)
        <div class="mb-6">
            @if($emailStatus === 'sent' || $emailStatus === 'processed')
                <div class="bg-green-50 border border-green-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-green-800">
                                {{ $emailStatus === 'processed' ? 'Email Processed Successfully' : 'Email Sent Successfully' }}
                            </h3>
                            <p class="mt-1 text-sm text-green-700">{{ $emailMessage }}</p>
                            @if($emailDeliveryId)
                                <div class="mt-2 flex items-center space-x-3">
                                    <button wire:click="toggleEmailDetails" 
                                            class="text-xs font-medium text-green-600 hover:text-green-500">
                                        {{ $showEmailDetails ? 'Hide' : 'Show' }} Details
                                    </button>
                                    @if($showEmailDetails)
                                        <span class="text-xs text-green-600 font-mono">ID: {{ $emailDeliveryId }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif($emailStatus === 'queued')
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-blue-800">Email Queued for Delivery</h3>
                            <p class="mt-1 text-sm text-blue-700">{{ $emailMessage }}</p>
                            @if($emailDeliveryId)
                                <div class="mt-2 flex items-center space-x-3">
                                    <button wire:click="checkEmailDeliveryStatus" 
                                            class="text-xs font-medium text-blue-600 hover:text-blue-500">
                                        Check Status
                                    </button>
                                    <button wire:click="toggleEmailDetails" 
                                            class="text-xs font-medium text-blue-600 hover:text-blue-500">
                                        {{ $showEmailDetails ? 'Hide' : 'Show' }} Details
                                    </button>
                                    @if($showEmailDetails)
                                        <span class="text-xs text-blue-600 font-mono">ID: {{ $emailDeliveryId }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif($emailStatus === 'failed')
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-red-800">Email Delivery Failed</h3>
                            <p class="mt-1 text-sm text-red-700">{{ $emailMessage }}</p>
                            <div class="mt-2 flex items-center space-x-3">
                                @if($emailRetryCount < 3)
                                    <button wire:click="retryWelcomeEmail({{ $customer->id ?? 0 }})" 
                                            class="text-xs font-medium text-red-600 hover:text-red-500">
                                        Retry Email {{ $emailRetryCount > 0 ? '(Attempt #' . ($emailRetryCount + 1) . ')' : '' }}
                                    </button>
                                @else
                                    <span class="text-xs text-red-500">Maximum retry attempts reached</span>
                                @endif
                                <button wire:click="toggleEmailDetails" 
                                        class="text-xs font-medium text-red-600 hover:text-red-500">
                                    {{ $showEmailDetails ? 'Hide' : 'Show' }} Details
                                </button>
                                @if($showEmailDetails)
                                    <div class="text-xs text-red-600">
                                        <div>Retry Count: {{ $emailRetryCount }}</div>
                                        @if($emailDeliveryId)
                                            <div class="font-mono">ID: {{ $emailDeliveryId }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Creation Form -->
    <form wire:submit.prevent="create">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Personal Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Personal Information</h3>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <!-- First Name -->
                        <div>
                            <label for="firstName" class="block text-sm font-medium text-gray-700">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <input wire:model="firstName" 
                                       type="text" 
                                       id="firstName" 
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('firstName') border-red-300 @enderror"
                                       placeholder="Enter first name">
                            </div>
                            @error('firstName')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label for="lastName" class="block text-sm font-medium text-gray-700">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <input wire:model="lastName" 
                                       type="text" 
                                       id="lastName" 
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('lastName') border-red-300 @enderror"
                                       placeholder="Enter last name">
                            </div>
                            @error('lastName')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <input wire:model="email" 
                                       type="email" 
                                       id="email" 
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('email') border-red-300 @enderror"
                                       placeholder="Enter email address">
                            </div>
                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Telephone Number -->
                        <div>
                            <label for="telephoneNumber" class="block text-sm font-medium text-gray-700">
                                Telephone Number <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <input wire:model="telephoneNumber" 
                                       type="tel" 
                                       id="telephoneNumber" 
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('telephoneNumber') border-red-300 @enderror"
                                       placeholder="Enter telephone number">
                            </div>
                            @error('telephoneNumber')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tax Number -->
                        <div>
                            <label for="taxNumber" class="block text-sm font-medium text-gray-700">
                                Tax Number
                            </label>
                            <div class="mt-1">
                                <input wire:model="taxNumber" 
                                       type="text" 
                                       id="taxNumber" 
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('taxNumber') border-red-300 @enderror"
                                       placeholder="Enter tax number (optional)">
                            </div>
                            @error('taxNumber')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-sm text-gray-500">Optional field for tax identification</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Address Information</h3>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Street Address -->
                        <div>
                            <label for="streetAddress" class="block text-sm font-medium text-gray-700">
                                Street Address <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <textarea wire:model="streetAddress" 
                                          id="streetAddress" 
                                          rows="3"
                                          class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('streetAddress') border-red-300 @enderror"
                                          placeholder="Enter complete street address"></textarea>
                            </div>
                            @error('streetAddress')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- City/Town -->
                        <div>
                            <label for="cityTown" class="block text-sm font-medium text-gray-700">
                                City/Town <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <input wire:model="cityTown" 
                                       type="text" 
                                       id="cityTown" 
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('cityTown') border-red-300 @enderror"
                                       placeholder="Enter city or town">
                            </div>
                            @error('cityTown')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Parish -->
                        <div>
                            <label for="parish" class="block text-sm font-medium text-gray-700">
                                Parish <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <select wire:model="parish" 
                                        id="parish" 
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('parish') border-red-300 @enderror">
                                    <option value="">Select Parish</option>
                                    @foreach($this->parishes as $parishOption)
                                        <option value="{{ $parishOption }}">{{ $parishOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('parish')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Country -->
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700">
                                Country <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <input wire:model="country" 
                                       type="text" 
                                       id="country" 
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('country') border-red-300 @enderror"
                                       placeholder="Enter country">
                            </div>
                            @error('country')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Pickup Location -->
                        <div>
                            <label for="pickupLocation" class="block text-sm font-medium text-gray-700">
                                Pickup Location <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <select wire:model="pickupLocation" 
                                        id="pickupLocation" 
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('pickupLocation') border-red-300 @enderror">
                                    <option value="">Select Pickup Location</option>
                                    @foreach($this->pickupLocations as $office)
                                        <option value="{{ $office->id }}">{{ $office->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('pickupLocation')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Settings -->
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Account Settings</h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Password Generation -->
                    <div>
                        <div class="flex items-center">
                            <input wire:model="generatePassword" 
                                   id="generatePassword" 
                                   type="checkbox" 
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="generatePassword" class="ml-2 block text-sm text-gray-900">
                                Generate random password automatically
                            </label>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            If unchecked, you'll need to set a password manually
                        </p>
                        
                        @if(!$generatePassword)
                            <div class="mt-4 space-y-4">
                                <!-- Password -->
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="mt-1">
                                        <input wire:model="password" 
                                               type="password" 
                                               id="password" 
                                               class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('password') border-red-300 @enderror"
                                               placeholder="Enter password (min 8 characters)">
                                    </div>
                                    @error('password')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Password Confirmation -->
                                <div>
                                    <label for="passwordConfirmation" class="block text-sm font-medium text-gray-700">
                                        Confirm Password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="mt-1">
                                        <input wire:model="passwordConfirmation" 
                                               type="password" 
                                               id="passwordConfirmation" 
                                               class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('passwordConfirmation') border-red-300 @enderror"
                                               placeholder="Confirm password">
                                    </div>
                                    @error('passwordConfirmation')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        @else
                            <div class="mt-4 p-3 bg-gray-50 rounded-md">
                                <p class="text-sm text-gray-600">
                                    <svg class="inline h-4 w-4 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    A secure password will be generated automatically
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Email Settings -->
                    <div>
                        <div class="flex items-center">
                            <input wire:model="sendWelcomeEmail" 
                                   id="sendWelcomeEmail" 
                                   type="checkbox" 
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="sendWelcomeEmail" class="ml-2 block text-sm text-gray-900">
                                Send welcome email to customer
                            </label>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Customer will receive account details and login instructions
                        </p>
                        
                        @if($sendWelcomeEmail)
                            <div class="mt-4 space-y-3">
                                <!-- Queue Email Option -->
                                <div class="flex items-center">
                                    <input wire:model="queueEmail" 
                                           id="queueEmail" 
                                           type="checkbox" 
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="queueEmail" class="ml-2 block text-sm text-gray-900">
                                        Queue email for background delivery
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 ml-6">
                                    Recommended for better performance. Uncheck to send immediately.
                                </p>
                                
                                <div class="p-3 bg-blue-50 rounded-md">
                                    <p class="text-sm text-blue-600">
                                        <svg class="inline h-4 w-4 text-blue-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                        </svg>
                                        Welcome email will be {{ $queueEmail ? 'queued for delivery' : 'sent immediately' }}
                                        @if($generatePassword)
                                            and will include the generated password
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Information Preview -->
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Account Information</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Account Number</dt>
                            <dd class="text-sm text-gray-900 font-mono">Will be generated automatically</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Role</dt>
                            <dd class="text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Customer
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="mt-6 flex items-center justify-end space-x-4">
            <button type="button" 
                    wire:click="cancel"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancel
            </button>
            <button type="submit" 
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                <svg wire:loading wire:target="create" class="-ml-1 mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg wire:loading.remove wire:target="create" class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Create Customer
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Auto-focus first input field
    document.addEventListener('DOMContentLoaded', function() {
        const firstInput = document.getElementById('firstName');
        if (firstInput) {
            firstInput.focus();
        }
    });

    // Show/hide password fields based on generatePassword checkbox
    document.addEventListener('livewire:load', function () {
        Livewire.hook('message.processed', (message, component) => {
            // Re-focus first input if form was reset
            if (component.fingerprint.name === 'customers.customer-create') {
                const firstInput = document.getElementById('firstName');
                if (firstInput && firstInput.value === '') {
                    firstInput.focus();
                }
            }
        });
    });
</script>
@endpush