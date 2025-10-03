@section('title', 'Create a new account')

<div>
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <a href="{{ route('home') }}">
            <x-logo class="w-auto h-32 mx-auto text-shiraz-600" />
        </a>

        <h2 class="mt-6 text-3xl font-extrabold text-center text-gray-900 leading-9">
            Create a new account
        </h2>

        <p class="mt-2 text-sm text-center text-gray-600 leading-5 max-w">
            Or
            <a href="{{ route('login') }}" class="font-medium text-shiraz-600 hover:text-shiraz-500 focus:outline-none focus:underline transition ease-in-out duration-150">
                sign in to your account
            </a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="px-4 py-8 bg-white shadow sm:rounded-lg sm:px-10">
            <form wire:submit.prevent="register">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 leading-5">
                        First Name
                    </label>

                    <div class="mt-1 rounded-md shadow-sm">
                        <input wire:model.lazy="firstName" id="first_name" type="text" required autofocus class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('firstName') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror" placeholder="Enter your First Name" />
                    </div>

                    @error('firstName')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6">
                    <label for="last_name" class="block text-sm font-medium text-gray-700 leading-5">
                        Last Name
                    </label>

                    <div class="mt-1 rounded-md shadow-sm">
                        <input wire:model.lazy="lastName" id="last_name" type="text" required autofocus class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('lastName') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror" placeholder="Enter your Last Name" />
                    </div>

                    @error('lastName')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 leading-5">
                        Email address
                    </label>

                    <div class="mt-1 rounded-md shadow-sm">
                        <input wire:model.lazy="email" id="email" type="email" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('email') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror" placeholder="Enter your E-Mail Address" />
                    </div>

                    @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6">
                    <label for="telephone_number" class="block text-sm font-medium text-gray-700 leading-5">
                        Telephone Number (mobile)
                    </label>

                    <div class="mt-1 rounded-md shadow-sm">
                        <input wire:model.lazy="telephoneNumber" id="telephone_number" type="text" required autofocus class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('telephoneNumber') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror" placeholder="Enter your Telephone Number" />
                    </div>

                    @error('telephoneNumber')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6">
                    <label for="tax_number" class="block text-sm font-medium text-gray-700 leading-5">
                        Tax Registration Number (TRN)
                    </label>

                    <div class="mt-1 rounded-md shadow-sm">
                        <input wire:model.lazy="taxNumber" id="tax_number" type="text" required autofocus class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('taxNumber') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror" placeholder="Enter your TRN" />
                    </div>

                    @error('taxNumber')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6">
                    <label for="street_address" class="block text-sm font-medium text-gray-700 leading-5">
                        Street Address
                    </label>

                    <div class="mt-1 rounded-md shadow-sm">
                        <input wire:model.lazy="streetAddress" id="street_address" type="text" required autofocus class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('streetAddress') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror" placeholder="Enter your Street Address" />
                    </div>

                    @error('streetAddress')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6">
                    <label for="town_city" class="block text-sm font-medium text-gray-700 leading-5">
                        Town/City
                    </label>

                    <div class="mt-1 rounded-md shadow-sm">
                        <input wire:model.lazy="townCity" id="town_city" type="text" required autofocus class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('townCity') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror" placeholder="Enter your Town" />
                    </div>

                    @error('townCity')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6">
                    <label for="parish" class="block text-sm font-medium text-gray-700 leading-5">
                        Parish
                    </label>
                    <div class="mt-1 rounded-md shadow-sm">
                        <select wire:model.lazy="parish" id="parish" required autofocus class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('parish') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
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

                <div class="mt-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 leading-5">
                        Password
                    </label>

                    <div class="mt-1 rounded-md shadow-sm">
                        <input wire:model.lazy="password" id="password" type="password" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('password') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror" />
                    </div>

                    @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 leading-5">
                        Confirm Password
                    </label>

                    <div class="mt-1 rounded-md shadow-sm">
                        <input wire:model.lazy="passwordConfirmation" id="password_confirmation" type="password" required class="block w-full px-3 py-2 placeholder-gray-400 border border-gray-300 appearance-none rounded-md focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5" />
                    </div>
                </div>

                <!-- Terms and Conditions Checkbox -->
                <div class="mt-6">
                    <div class="flex items-center">
                        <input wire:model.lazy="termsAccepted" id="terms" type="checkbox" required class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded @error('termsAccepted') border-red-300 @enderror">
                        <label for="terms" class="ml-2 block text-sm text-gray-700">
                            I agree to the <a href="https://shipsharkltd.com/terms-of-service/" target="_new" class="text-blue-600 hover:text-blue-500">Terms and Conditions</a>
                        </label>
                    </div>
                    @error('termsAccepted')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6">
                    <span class="block w-full rounded-md shadow-sm">
                        <button type="submit" class="flex justify-center w-full px-4 py-2 text-sm font-medium text-white bg-shiraz-600 border border-transparent rounded-md hover:bg-shiraz-500 focus:outline-none focus:border-shiraz-700 focus:ring-shiraz active:bg-shiraz-700 transition duration-150 ease-in-out">
                            Register
                        </button>
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>