@section('title', 'Sign in to your account')

<div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <a href="{{ route('home') }}">
                <x-logo class="w-auto h-32 mx-auto text-shiraz-600" />
            </a>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Sign in to your account</h2>

            <p class="mt-2 text-sm text-center text-gray-600 leading-5 max-w">
                Don't have an account?
                <a href="{{ route('register') }}" class="font-medium text-shiraz-600 hover:text-shiraz-500 focus:outline-none focus:underline transition ease-in-out duration-150">
                    create a new account
                </a>
            </p>
        </div>

        <form class="mt-8 space-y-6" wire:submit.prevent="authenticate">
            <input type="hidden" name="remember" value="true">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email-address" class="sr-only">Email address</label>
                    <input wire:model.lazy="email" id="email" name="email" type="email" autocomplete="email" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-shiraz-500 focus:border-shiraz-500 focus:z-10 sm:text-sm @error('email') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror" placeholder="Email address">
                </div>
                @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input wire:model.lazy="password" id="password" type="password" autocomplete="current-password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-shiraz-500 focus:border-shiraz-500 focus:z-10 sm:text-sm @error('password') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror" placeholder="Password">
                </div>
                @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input wire:model.lazy="remember" id="remember" type="checkbox" class="h-4 w-4 text-shiraz-600 focus:ring-shiraz-500 border-gray-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-gray-900"> Remember me </label>
                </div>

                <div class="text-sm">
                    <a href="{{ route('password.request') }}" class="font-medium text-shiraz-600 hover:text-shiraz-500 focus:outline-none focus:underline transition ease-in-out duration-150">
                        Forgot your password?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-shiraz-600 hover:bg-shiraz-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shiraz-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <!-- Heroicon name: solid/lock-closed -->
                        <svg class="h-5 w-5 text-shiraz-500 group-hover:text-shiraz-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    Sign in
                </button>
            </div>
        </form>
    </div>
</div>