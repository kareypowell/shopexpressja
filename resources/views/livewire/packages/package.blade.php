<div>
    @include('livewire.quick-insights', [
        'inComingAir' => $inComingAir,
        'inComingSea' => $inComingSea,
        'availableAir' => $availableAir,
        'availableSea' => $availableSea,
        'accountBalance' => $accountBalance
    ])

    <hr class="my-10">

    <div class="mt-10">
        <h3 class="mb-5 text-base font-semibold text-gray-900">Packages</h3>
        <livewire:customers.customer-packages-with-modal :customer="auth()->user()" />
    </div>
</div>