<div>
    <h3 class="mt-5 text-lg leading-6 font-medium text-gray-900">Welcome back, {{ auth()->user()->first_name }}!</h3>

    <div>
        @include('livewire.quick-insights', [
        'inComingAir' => $inComingAir,
        'inComingSea' => $inComingSea,
        'availableAir' => $availableAir,
        'availableSea' => $availableSea,
        'accountBalance' => $accountBalance,
        'delayedPackages' => $delayedPackages
        ])

        <hr class="my-10">

        <div class="mt-10">
            <!-- <h3 class="mb-5 text-base font-semibold text-gray-900">Manifests</h3> -->
        </div>
    </div>
</div>