<div>
    @if($mode === 'index')
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                All Manifests
            </h3>

            <a href="{{ route('admin.manifests.create') }}" class="bg-wax-flower-500 hover:bg-wax-flower-700 text-white font-bold py-2 px-4 rounded">
                Create Manifest
            </a>
        </div>

        <livewire:manifests.manifests-table />
    @elseif($mode === 'create')
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Create Manifest
            </h3>

            <a href="{{ route('admin.manifests.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Manifests
            </a>
        </div>
    @endif

    @if($isOpen)
        @include('livewire.manifests.create')
    @endif
</div>