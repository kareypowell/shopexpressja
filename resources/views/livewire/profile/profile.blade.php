<div class="divide-y divide-gray-900/10">
    <div class="grid grid-cols-1 gap-x-8 gap-y-8 py-10 md:grid-cols-3">
        <div class="px-4 sm:px-0">
            <h2 class="text-base/7 font-semibold text-gray-900">Personal Information</h2>
            <p class="mt-1 text-sm/6 text-gray-600">Keep your information update to avaoid delays in the process.</p>
        </div>

        <form class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl md:col-span-2">
            <div class="px-4 py-6 sm:p-8">
                <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <label for="first_name" class="block text-sm/6 font-medium text-gray-900">First name</label>
                        <div class="mt-2">
                            <input wire:model.lazy="firstName" id="first_name" type="text" name="first_name" id="first_name" autocomplete="given-name" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-wax-flower-600 sm:text-sm/6 @error('firstName') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                        </div>

                        @error('firstName')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-3">
                        <label for="last_name" class="block text-sm/6 font-medium text-gray-900">Last name</label>
                        <div class="mt-2">
                            <input wire:model.lazy="lastName" type="text" name="last_name" id="last_name" autocomplete="family-name" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-wax-flower-600 sm:text-sm/6 @error('lastName') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                        </div>

                        @error('lastName')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-4">
                        <label for="email" class="block text-sm/6 font-medium text-gray-900">Email address</label>
                        <div class="mt-2">
                            <input wire:model.lazy="email" id="email" name="email" type="email" autocomplete="email" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-wax-flower-600 sm:text-sm/6 @error('email') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                        </div>
                        @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-3">
                        <label for="tax_number" class="block text-sm/6 font-medium text-gray-900">Tax Registration Number (TRN)</label>
                        <div class="mt-2">
                            <input wire:model.lazy="taxNumber" id="tax_number" type="number" name="tax_number" id="tax_number" autocomplete="given-name" minlength="9" pattern="\d{9}" maxlength="9" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-wax-flower-600 sm:text-sm/6 @error('taxNumber') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                        </div>
                        @error('taxNumber')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-3">
                        <label for="telephone_number" class="block text-sm/6 font-medium text-gray-900">Telephone Number (mobile)</label>
                        <div class="mt-2">
                            <input wire:model.lazy="telephoneNumber" type="text" name="telephone_number" id="telephone_number" autocomplete="family-name" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-wax-flower-600 sm:text-sm/6 @error('telephoneNumber') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                        </div>
                        @error('telephoneNumber')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <hr>

                    <div class="col-span-full">
                        <label for="street_address" class="block text-sm/6 font-medium text-gray-900">Street address</label>
                        <div class="mt-2">
                            <input wire:model.lazy="streetAddress" type="text" name="street_address" id="street_address" autocomplete="street_address" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-wax-flower-600 sm:text-sm/6 @error('streetAddress') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                        </div>
                        @error('streetAddress')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2 sm:col-start-1">
                        <label for="city_town" class="block text-sm/6 font-medium text-gray-900">Town</label>
                        <div class="mt-2">
                            <input wire:model.lazy="cityTown" type="text" name="city_town" id="city_town" autocomplete="address-level2" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-wax-flower-600 sm:text-sm/6 @error('cityTown') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                        </div>
                        @error('cityTown')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="parish" class="block text-sm/6 font-medium text-gray-900">
                            Parish
                        </label>
                        <div class="mt-1 rounded-md shadow-sm">
                            <select wire:model.lazy="parish" id="parish" required autofocus class="appearance-none block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-wax-flower-600 sm:text-sm/6 @error('parish') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
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
                        </div>
                        @error('parish')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="pickup_location" class="block text-sm/6 font-medium text-gray-900">
                            Pickup Location
                        </label>
                        <div class="mt-1 rounded-md shadow-sm">
                            <select wire:model.lazy="pickupLocation" id="pickup_location" required autofocus class="appearance-none block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-wax-flower-600 sm:text-sm/6 @error('pickupLocation') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                                <option value="" selected>--- Select Location ---</option>
                                @foreach($pickupLocations as $pickupLocation)
                                <option value="{{ $pickupLocation->id }}">{{ $pickupLocation->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('pickupLocation')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- <div class="sm:col-span-2">
                        <label for="postal-code" class="block text-sm/6 font-medium text-gray-900">ZIP / Postal code</label>
                        <div class="mt-2">
                            <input type="text" name="postal-code" id="postal-code" autocomplete="postal-code" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-wax-flower-600 sm:text-sm/6">
                        </div>
                    </div> -->
                </div>
            </div>
            <div class="flex items-center justify-end gap-x-6 border-t border-gray-900/10 px-4 py-4 sm:px-8">
                <button type="button" class="text-sm/6 font-semibold text-gray-900">Cancel</button>
                <button wire:click="updateProfile()" type="button" class=" rounded-md bg-wax-flower-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-wax-flower-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wax-flower-600">Save</button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-x-8 gap-y-8 py-10 md:grid-cols-3">
        <div class="px-4 sm:px-0">
            <h2 class="text-base/7 font-semibold text-gray-900">Security</h2>
            <p class="mt-1 text-sm/6 text-gray-600">Keep your account secure by updating your password regularly.</p>
        </div>

        <form class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl md:col-span-2">
            <div class="px-4 py-6 sm:p-8">
                <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <label for="current_password" class="block text-sm/6 font-medium text-gray-900">Current Password</label>
                        <div class="mt-2">
                            <input wire:model.lazy="currentPassword" id="current_password" name="current_password" type="password" autocomplete="off" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-wax-flower-600 sm:text-sm/6 @error('currentPassword') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                        </div>
                        @error('currentPassword')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-4">
                        <label for="new_password" class="block text-sm/6 font-medium text-gray-900">New Password</label>
                        <div class="mt-2">
                            <input wire:model.lazy="newPassword" id="new_password" name="new_password" type="password" autocomplete="off" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-wax-flower-600 sm:text-sm/6 @error('newPassword') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                        </div>
                        @error('newPassword')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-4">
                        <label for="confirm_password" class="block text-sm/6 font-medium text-gray-900">Confirm Password</label>
                        <div class="mt-2">
                            <input wire:model.lazy="confirmPassword" id="confirm_password" name="confirm_password" type="password" autocomplete="off" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-wax-flower-600 sm:text-sm/6 @error('confirmPassword') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                        </div>
                        @error('confirmPassword')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-x-6 border-t border-gray-900/10 px-4 py-4 sm:px-8">
                <button type="button" class="text-sm/6 font-semibold text-gray-900">Cancel</button>
                <button wire:click="updatePassword()" type="button" class=" rounded-md bg-wax-flower-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-wax-flower-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wax-flower-600">Save</button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-x-8 gap-y-8 py-10 md:grid-cols-3">
        <div class="px-4 sm:px-0">
            <h2 class="text-base/7 font-semibold text-gray-900">Notifications</h2>
            <p class="mt-1 text-sm/6 text-gray-600">We'll always let you know about important changes, but you pick what else you want to hear about.</p>
        </div>

        <form class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl md:col-span-2">
            <div class="px-4 py-6 sm:p-8">
                <div class="max-w-2xl space-y-10 md:col-span-2">
                    <fieldset>
                        <legend class="text-sm/6 font-semibold text-gray-900">By email</legend>
                        <div class="mt-6 space-y-6">
                            <div class="flex gap-3">
                                <div class="flex h-6 shrink-0 items-center">
                                    <div class="group grid size-4 grid-cols-1">
                                        <input id="comments" aria-describedby="comments-description" name="comments" type="checkbox" checked class="col-start-1 row-start-1 appearance-none rounded border border-gray-300 bg-white checked:border-wax-flower-600 checked:bg-wax-flower-600 indeterminate:border-wax-flower-600 indeterminate:bg-wax-flower-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wax-flower-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto">
                                        <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-[:disabled]:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                            <path class="opacity-0 group-has-[:checked]:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path class="opacity-0 group-has-[:indeterminate]:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-sm/6">
                                    <label for="comments" class="font-medium text-gray-900">Comments</label>
                                    <p id="comments-description" class="text-gray-500">Get notified when someones posts a comment on a posting.</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="flex h-6 shrink-0 items-center">
                                    <div class="group grid size-4 grid-cols-1">
                                        <input id="candidates" aria-describedby="candidates-description" name="candidates" type="checkbox" class="col-start-1 row-start-1 appearance-none rounded border border-gray-300 bg-white checked:border-wax-flower-600 checked:bg-wax-flower-600 indeterminate:border-wax-flower-600 indeterminate:bg-wax-flower-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wax-flower-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto">
                                        <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-[:disabled]:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                            <path class="opacity-0 group-has-[:checked]:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path class="opacity-0 group-has-[:indeterminate]:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-sm/6">
                                    <label for="candidates" class="font-medium text-gray-900">Candidates</label>
                                    <p id="candidates-description" class="text-gray-500">Get notified when a candidate applies for a job.</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="flex h-6 shrink-0 items-center">
                                    <div class="group grid size-4 grid-cols-1">
                                        <input id="offers" aria-describedby="offers-description" name="offers" type="checkbox" class="col-start-1 row-start-1 appearance-none rounded border border-gray-300 bg-white checked:border-wax-flower-600 checked:bg-wax-flower-600 indeterminate:border-wax-flower-600 indeterminate:bg-wax-flower-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wax-flower-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto">
                                        <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-[:disabled]:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                            <path class="opacity-0 group-has-[:checked]:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path class="opacity-0 group-has-[:indeterminate]:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-sm/6">
                                    <label for="offers" class="font-medium text-gray-900">Offers</label>
                                    <p id="offers-description" class="text-gray-500">Get notified when a candidate accepts or rejects an offer.</p>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm/6 font-semibold text-gray-900">Push notifications</legend>
                        <p class="mt-1 text-sm/6 text-gray-600">These are delivered via SMS to your mobile phone.</p>
                        <div class="mt-6 space-y-6">
                            <div class="flex items-center gap-x-3">
                                <input id="push-everything" name="push-notifications" type="radio" checked class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white checked:border-wax-flower-600 checked:bg-wax-flower-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wax-flower-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:before:bg-gray-400 forced-colors:appearance-auto forced-colors:before:hidden [&:not(:checked)]:before:hidden">
                                <label for="push-everything" class="block text-sm/6 font-medium text-gray-900">Everything</label>
                            </div>
                            <div class="flex items-center gap-x-3">
                                <input id="push-email" name="push-notifications" type="radio" class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white checked:border-wax-flower-600 checked:bg-wax-flower-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wax-flower-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:before:bg-gray-400 forced-colors:appearance-auto forced-colors:before:hidden [&:not(:checked)]:before:hidden">
                                <label for="push-email" class="block text-sm/6 font-medium text-gray-900">Same as email</label>
                            </div>
                            <div class="flex items-center gap-x-3">
                                <input id="push-nothing" name="push-notifications" type="radio" class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white checked:border-wax-flower-600 checked:bg-wax-flower-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wax-flower-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:before:bg-gray-400 forced-colors:appearance-auto forced-colors:before:hidden [&:not(:checked)]:before:hidden">
                                <label for="push-nothing" class="block text-sm/6 font-medium text-gray-900">No push notifications</label>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>
            <div class="flex items-center justify-end gap-x-6 border-t border-gray-900/10 px-4 py-4 sm:px-8">
                <button type="button" class="text-sm/6 font-semibold text-gray-900">Cancel</button>
                <button type="submit" class="rounded-md bg-wax-flower-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-wax-flower-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wax-flower-600">Save</button>
            </div>
        </form>
    </div>
</div>