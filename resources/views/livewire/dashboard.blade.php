<div>
    <h3 class="mt-5 text-lg leading-6 font-medium text-gray-900">Welcome back, {{ auth()->user()->first_name }}!</h3>

    <div>
        @include('livewire.quick-insights', [
            'inComingAir' => $inComingAir,
            'inComingSea' => $inComingSea,
            'availableAir' => $availableAir,
            'availableSea' => $availableSea,
            'accountBalance' => $accountBalance,
            'creditBalance' => $creditBalance,
            'totalAvailableBalance' => $totalAvailableBalance,
            'delayedPackages' => $delayedPackages
        ])

        <hr class="my-10">

        @if(auth()->user()->isCustomer())
            <!-- Detailed Account Balance for Customers -->
            <div class="mt-10">
                <livewire:customers.customer-account-balance />
            </div>
            
            <div class="mt-10">
                <h3 class="mb-5 text-base font-semibold text-gray-900">Packages</h3>
                <livewire:customers.customer-packages-with-modal :customer="auth()->user()" />
            </div>
        @else
            <div class="mt-10">
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Admin Dashboard</h3>
                    <p class="text-gray-600">Welcome to the admin dashboard. Use the navigation menu to access admin features.</p>
                </div>
            </div>
        @endif
    </div>
</div>