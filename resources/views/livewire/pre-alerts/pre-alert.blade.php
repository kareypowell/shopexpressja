<div>
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Pre-Alerts
        </h3>

        <button wire:click="create()" class="bg-wax-flower-500 hover:bg-wax-flower-700 text-white font-bold py-2 px-4 rounded">
            Create Pre-Alert
        </button>
    </div>

    @if($isOpen)
        @include('livewire.pre-alerts.create')
    @endif

    <livewire:pre-alerts-table />
</div>