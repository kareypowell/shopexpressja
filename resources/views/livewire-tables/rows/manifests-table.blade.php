@php
    // This is a row template for Laravel Livewire Tables
    // Each cell is rendered separately, so we don't need a wrapper div
@endphp

<x-livewire-tables::table.cell>
    {{ $row->name }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->shipment_date ? $row->shipment_date->format('F j, Y') : 'Not set' }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if($row->reservation_number)
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
            {{ $row->reservation_number }}
        </span>
    @else
        <span class="text-gray-400">-</span>
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if($row->flight_number)
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
            {{ $row->flight_number }}
        </span>
    @else
        <span class="text-gray-400">-</span>
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->flight_destination ?: '-' }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->packages->count() }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
        ${{ number_format($row->packages->sum(function($package) { return $package->total_cost; }), 2) }}
    </span>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
        {{ number_format($row->packages->sum('weight'), 2) . " / " . number_format($row->packages->sum('weight') / 2.205, 2) }}
    </span>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
        ${{ number_format($row->exchange_rate, 2) }}
    </span>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ ucfirst($row->type) }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if($row->is_open)
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            Open
        </span>
    @else
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
            </svg>
            Closed
        </span>
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->created_at->format('F j, Y @ G:i A') }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <a href="{{ route('admin.manifests.packages', $row->id) }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-blue-600 hover:bg-blue-700 mr-2">
        View
    </a>
    <a href="{{ route('admin.manifests.edit', $row->id) }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-gray-600 hover:bg-gray-700">
        Edit
    </a>
</x-livewire-tables::table.cell>