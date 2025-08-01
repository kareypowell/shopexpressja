<div>
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Customer</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Update customer information and profile details
                    </p>
                </div>
                <div class="flex space-x-3">
                    <button wire:click="cancel" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </button>
                    <button wire:click="save" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Changes
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

    <!-- Edit Form -->
    <form wire:submit.prevent="save">
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
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('firstName') border-red-300 @enderror">
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
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('lastName') border-red-300 @enderror">
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
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('email') border-red-300 @enderror">
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
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('telephoneNumber') border-red-300 @enderror">
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
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('taxNumber') border-red-300 @enderror">
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
                                          class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('streetAddress') border-red-300 @enderror"></textarea>
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
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('cityTown') border-red-300 @enderror">
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
                                    <option value="Kingston">Kingston</option>
                                    <option value="St. Andrew">St. Andrew</option>
                                    <option value="St. Thomas">St. Thomas</option>
                                    <option value="Portland">Portland</option>
                                    <option value="St. Mary">St. Mary</option>
                                    <option value="St. Ann">St. Ann</option>
                                    <option value="Trelawny">Trelawny</option>
                                    <option value="St. James">St. James</option>
                                    <option value="Hanover">Hanover</option>
                                    <option value="Westmoreland">Westmoreland</option>
                                    <option value="St. Elizabeth">St. Elizabeth</option>
                                    <option value="Manchester">Manchester</option>
                                    <option value="Clarendon">Clarendon</option>
                                    <option value="St. Catherine">St. Catherine</option>
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
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md @error('country') border-red-300 @enderror">
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
                                    @foreach($offices as $office)
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

        <!-- Account Information (Read-only) -->
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Account Information</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Account Number</dt>
                            <dd class="text-sm text-gray-900 font-mono">{{ $customer->profile->account_number ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                            <dd class="text-sm text-gray-900">{{ $customer->created_at->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Role</dt>
                            <dd class="text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ ucfirst($customer->role->name ?? 'N/A') }}
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
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Save Changes
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
</script>
@endpush