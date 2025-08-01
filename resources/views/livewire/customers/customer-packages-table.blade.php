<div>
    @include('livewire.customers.customer-packages-table-header')
    
    {{-- Responsive Table Container --}}
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="overflow-x-auto">
            {{-- The table will be rendered by the DataTableComponent --}}
        </div>
    </div>
    
    {{-- Mobile-friendly Package Cards (shown on small screens) --}}
    <div class="block sm:hidden mt-4">
        <div class="text-center py-8 text-gray-500">
            <p class="text-sm">Use the table above to view package details on mobile devices.</p>
        </div>
    </div>
    
    {{-- Package Detail Modal --}}
    @include('livewire.customers.package-detail-modal')
</div>