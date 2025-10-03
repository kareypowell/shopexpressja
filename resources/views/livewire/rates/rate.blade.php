<div>
    <div class="flex items-center justify-between mb-5">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Shipping Rates
            </h3>
            <p class="mt-1 text-sm text-gray-600">
                View our air and sea shipping rates. Air rates are based on weight (lbs), sea rates are based on volume (cubic feet).
            </p>
        </div>

        <!-- <button wire:click="create()" class="bg-shiraz-500 hover:bg-shiraz-700 text-white font-bold py-2 px-4 rounded">
            Create Purchase Request
        </button> -->
    </div>

    <livewire:rates.rates-table />
</div>