<div>
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            View Packages
        </h3>

        <button wire:click="create()" class="bg-wax-flower-500 hover:bg-wax-flower-700 text-white font-bold py-2 px-4 rounded">
            Add Package
        </button>
    </div>

    {{--@if($isOpen)
    @include('livewire.manifests.packages.create')
    @endif--}}

    <livewire:manifests.packages.manifest-packages-table />
</div>