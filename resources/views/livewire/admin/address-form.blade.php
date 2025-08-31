<div>
    <form wire:submit.prevent="save">
        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <!-- Street Address -->
                    <div class="sm:col-span-6">
                        <label for="street_address" class="block text-sm font-medium text-gray-700">
                            Street Address <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input wire:model="street_address" 
                                   type="text" 
                                   id="street_address"
                                   class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md @error('street_address') border-red-300 @enderror">
                        </div>
                        @error('street_address')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- City -->
                    <div class="sm:col-span-2">
                        <label for="city" class="block text-sm font-medium text-gray-700">
                            City <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input wire:model="city" 
                                   type="text" 
                                   id="city"
                                   class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md @error('city') border-red-300 @enderror">
                        </div>
                        @error('city')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- State -->
                    <div class="sm:col-span-2">
                        <label for="state" class="block text-sm font-medium text-gray-700">
                            State/Province <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input wire:model="state" 
                                   type="text" 
                                   id="state"
                                   class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md @error('state') border-red-300 @enderror">
                        </div>
                        @error('state')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- ZIP Code -->
                    <div class="sm:col-span-2">
                        <label for="zip_code" class="block text-sm font-medium text-gray-700">
                            ZIP/Postal Code <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input wire:model="zip_code" 
                                   type="text" 
                                   id="zip_code"
                                   class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md @error('zip_code') border-red-300 @enderror">
                        </div>
                        @error('zip_code')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Country -->
                    <div class="sm:col-span-3">
                        <label for="country" class="block text-sm font-medium text-gray-700">
                            Country <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <select wire:model="country" 
                                    id="country"
                                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md @error('country') border-red-300 @enderror">
                                <option value="">Select a country</option>
                                <option value="USA">United States</option>
                                <option value="Canada">Canada</option>
                                <option value="Mexico">Mexico</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="Germany">Germany</option>
                                <option value="France">France</option>
                                <option value="Italy">Italy</option>
                                <option value="Spain">Spain</option>
                                <option value="Netherlands">Netherlands</option>
                                <option value="Belgium">Belgium</option>
                                <option value="Switzerland">Switzerland</option>
                                <option value="Austria">Austria</option>
                                <option value="Australia">Australia</option>
                                <option value="New Zealand">New Zealand</option>
                                <option value="Japan">Japan</option>
                                <option value="South Korea">South Korea</option>
                                <option value="China">China</option>
                                <option value="India">India</option>
                                <option value="Brazil">Brazil</option>
                                <option value="Argentina">Argentina</option>
                                <option value="Chile">Chile</option>
                                <option value="Colombia">Colombia</option>
                                <option value="Peru">Peru</option>
                                <option value="South Africa">South Africa</option>
                                <option value="Nigeria">Nigeria</option>
                                <option value="Kenya">Kenya</option>
                                <option value="Egypt">Egypt</option>
                                <option value="Morocco">Morocco</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        @error('country')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Primary Address Toggle -->
                    <div class="sm:col-span-6">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input wire:model="is_primary" 
                                       id="is_primary" 
                                       type="checkbox" 
                                       class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="is_primary" class="font-medium text-gray-700">Primary Address</label>
                                <p class="text-gray-500">Set this as the primary shipping address. Only one address can be primary at a time.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-4 py-3 bg-gray-50 text-right sm:px-6 rounded-b-lg">
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('admin.addresses.index') }}" 
                       class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        @if($isEditing)
                            Update Address
                        @else
                            Create Address
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>