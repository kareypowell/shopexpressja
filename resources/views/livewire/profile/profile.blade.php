<div x-data="{ activeTab: 'profile' }" class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4">
            <div class="flex items-center space-x-4">
                <div class="h-12 w-12 rounded-full bg-wax-flower-100 flex items-center justify-center">
                    <span class="text-lg font-semibold text-wax-flower-700">
                        {{ substr(auth()->user()->first_name, 0, 1) }}{{ substr(auth()->user()->last_name, 0, 1) }}
                    </span>
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">{{ auth()->user()->full_name }}</h1>
                    <p class="text-sm text-gray-500">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>
    </div> 
   <!-- Tab Navigation -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <button @click="activeTab = 'profile'" 
                        :class="activeTab === 'profile' ? 'border-wax-flower-500 text-wax-flower-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Profile
                </button>
                <button @click="activeTab = 'security'" 
                        :class="activeTab === 'security' ? 'border-wax-flower-500 text-wax-flower-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Security
                </button>
                <button @click="activeTab = 'notifications'" 
                        :class="activeTab === 'notifications' ? 'border-wax-flower-500 text-wax-flower-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Notifications
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Profile Tab -->
        <div x-show="activeTab === 'profile'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6">Personal Information</h3>
                
                <form class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First name</label>
                            <input wire:model.lazy="firstName" id="first_name" type="text" name="first_name" autocomplete="given-name" 
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-wax-flower-500 focus:ring-wax-flower-500 @error('firstName') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                            @error('firstName')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last name</label>
                            <input wire:model.lazy="lastName" type="text" name="last_name" id="last_name" autocomplete="family-name" 
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-wax-flower-500 focus:ring-wax-flower-500 @error('lastName') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                            @error('lastName')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email address</label>
                            <input wire:model.lazy="email" id="email" name="email" type="email" autocomplete="email" 
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-wax-flower-500 focus:ring-wax-flower-500 @error('email') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                            @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tax_number" class="block text-sm font-medium text-gray-700 mb-2">Tax Registration Number (TRN)</label>
                            <input wire:model.lazy="taxNumber" id="tax_number" type="number" name="tax_number" autocomplete="given-name" minlength="9" pattern="\d{9}" maxlength="9" 
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-wax-flower-500 focus:ring-wax-flower-500 @error('taxNumber') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                            @error('taxNumber')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="telephone_number" class="block text-sm font-medium text-gray-700 mb-2">Telephone Number (mobile)</label>
                            <input wire:model.lazy="telephoneNumber" type="text" name="telephone_number" id="telephone_number" autocomplete="tel" 
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-wax-flower-500 focus:ring-wax-flower-500 @error('telephoneNumber') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                            @error('telephoneNumber')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div class="border-t border-gray-200 pt-6">
                        <h4 class="text-base font-medium text-gray-900 mb-4">Address Information</h4>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="street_address" class="block text-sm font-medium text-gray-700 mb-2">Street address</label>
                                <input wire:model.lazy="streetAddress" type="text" name="street_address" id="street_address" autocomplete="street-address" 
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-wax-flower-500 focus:ring-wax-flower-500 @error('streetAddress') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                                @error('streetAddress')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="city_town" class="block text-sm font-medium text-gray-700 mb-2">Town</label>
                                <input wire:model.lazy="cityTown" type="text" name="city_town" id="city_town" autocomplete="address-level2" 
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-wax-flower-500 focus:ring-wax-flower-500 @error('cityTown') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                                @error('cityTown')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="parish" class="block text-sm font-medium text-gray-700 mb-2">Parish</label>
                                <select wire:model.lazy="parish" id="parish" required 
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-wax-flower-500 focus:ring-wax-flower-500 @error('parish') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                                    <option value="" selected>--- Select Parish ---</option>
                                    <option value="Clarendon">Clarendon</option>
                                    <option value="Hanover">Hanover</option>
                                    <option value="Kingston">Kingston</option>
                                    <option value="Manchester">Manchester</option>
                                    <option value="Portland">Portland</option>
                                    <option value="St. Andrew">St. Andrew</option>
                                    <option value="St. Ann">St. Ann</option>
                                    <option value="St. Catherine">St. Catherine</option>
                                    <option value="St. Elizabeth">St. Elizabeth</option>
                                    <option value="St. James">St. James</option>
                                    <option value="St. Mary">St. Mary</option>
                                    <option value="St. Thomas">St. Thomas</option>
                                    <option value="Trelawny">Trelawny</option>
                                    <option value="Westmoreland">Westmoreland</option>
                                </select>
                                @error('parish')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="pickup_location" class="block text-sm font-medium text-gray-700 mb-2">Pickup Location</label>
                                <select wire:model.lazy="pickupLocation" id="pickup_location" required 
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-wax-flower-500 focus:ring-wax-flower-500 @error('pickupLocation') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                                    <option value="" selected>--- Select Location ---</option>
                                    @foreach($pickupLocations as $pickupLocation)
                                    <option value="{{ $pickupLocation->id }}">{{ $pickupLocation->name }}</option>
                                    @endforeach
                                </select>
                                @error('pickupLocation')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200">
                        <button wire:click="updateProfile()" type="button" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-wax-flower-600 hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>        
        <!-- Security Tab -->
        <div x-show="activeTab === 'security'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6">Security Settings</h3>
                
                <form class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <input wire:model.lazy="currentPassword" id="current_password" name="current_password" type="password" autocomplete="current-password" 
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-wax-flower-500 focus:ring-wax-flower-500 @error('currentPassword') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                            @error('currentPassword')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input wire:model.lazy="newPassword" id="new_password" name="new_password" type="password" autocomplete="new-password" 
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-wax-flower-500 focus:ring-wax-flower-500 @error('newPassword') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                            @error('newPassword')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                            <input wire:model.lazy="confirmPassword" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" 
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-wax-flower-500 focus:ring-wax-flower-500 @error('confirmPassword') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                            @error('confirmPassword')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200">
                        <button wire:click="updatePassword()" type="button" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-wax-flower-600 hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>     
   <!-- Notifications Tab -->
        <div x-show="activeTab === 'notifications'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6">Notification Preferences</h3>
                
                <form class="space-y-8">
                    <fieldset>
                        <legend class="text-base font-medium text-gray-900 mb-4">Email Notifications</legend>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="email_packages" name="email_notifications" type="checkbox" checked 
                                           class="focus:ring-wax-flower-500 h-4 w-4 text-wax-flower-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="email_packages" class="font-medium text-gray-700">Package Updates</label>
                                    <p class="text-gray-500">Get notified when your packages change status or require action.</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="email_payments" name="email_notifications" type="checkbox" 
                                           class="focus:ring-wax-flower-500 h-4 w-4 text-wax-flower-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="email_payments" class="font-medium text-gray-700">Payment Notifications</label>
                                    <p class="text-gray-500">Get notified about payment confirmations and billing updates.</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="email_promotions" name="email_notifications" type="checkbox" 
                                           class="focus:ring-wax-flower-500 h-4 w-4 text-wax-flower-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="email_promotions" class="font-medium text-gray-700">Promotional Offers</label>
                                    <p class="text-gray-500">Get notified about special offers and promotional deals.</p>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-base font-medium text-gray-900 mb-4">SMS Notifications</legend>
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input id="sms_all" name="sms_notifications" type="radio" checked 
                                       class="focus:ring-wax-flower-500 h-4 w-4 text-wax-flower-600 border-gray-300">
                                <label for="sms_all" class="ml-3 block text-sm font-medium text-gray-700">All SMS notifications</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input id="sms_email" name="sms_notifications" type="radio" 
                                       class="focus:ring-wax-flower-500 h-4 w-4 text-wax-flower-600 border-gray-300">
                                <label for="sms_email" class="ml-3 block text-sm font-medium text-gray-700">Same as email preferences</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input id="sms_none" name="sms_notifications" type="radio" 
                                       class="focus:ring-wax-flower-500 h-4 w-4 text-wax-flower-600 border-gray-300">
                                <label for="sms_none" class="ml-3 block text-sm font-medium text-gray-700">No SMS notifications</label>
                            </div>
                        </div>
                    </fieldset>

                    <div class="flex justify-end pt-6 border-t border-gray-200">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-wax-flower-600 hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500">
                            Save Preferences
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>