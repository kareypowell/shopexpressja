{{-- Tracking Number --}}
<x-livewire-tables::table.cell>
    <div class="flex flex-col">
        <x-badges.primary>{{ $row->tracking_number }}</x-badges.primary>
        @if($row->warehouse_receipt_no)
            <span class="text-xs text-gray-500 mt-1">WR: {{ $row->warehouse_receipt_no }}</span>
        @endif
    </div>
</x-livewire-tables::table.cell>

{{-- Description --}}
<x-livewire-tables::table.cell>
    <div class="max-w-xs">
        <p class="text-sm font-medium text-gray-900 truncate">{{ $row->description }}</p>
        @if($row->estimated_value)
            <p class="text-xs text-gray-500">Est. Value: ${{ number_format($row->estimated_value, 2) }} USD</p>
        @endif
    </div>
</x-livewire-tables::table.cell>

{{-- Date --}}
<x-livewire-tables::table.cell>
    <div class="text-sm text-gray-900">
        {{ $row->created_at->format('M d, Y') }}
        <div class="text-xs text-gray-500">
            {{ $row->created_at->format('g:i A') }}
        </div>
    </div>
</x-livewire-tables::table.cell>

{{-- Weight --}}
<x-livewire-tables::table.cell>
    <div class="text-sm">
        <span class="font-medium">{{ number_format($row->weight, 2) }} lbs</span>
        @if($row->isSeaPackage() && $row->cubic_feet)
            <div class="text-xs text-gray-500">
                {{ number_format($row->cubic_feet, 3) }} ft³
            </div>
        @endif
    </div>
</x-livewire-tables::table.cell>

{{-- Status --}}
<x-livewire-tables::table.cell>
    <div class="flex flex-col space-y-1">
        @php
          $badgeClass = $row->status_badge_class ?? 'default';
          $statusLabel = $row->status_label ?? 'Unknown';
        @endphp
        @if($badgeClass === 'default')
          <x-badges.default>{{ $statusLabel }}</x-badges.default>
        @elseif($badgeClass === 'primary')
          <x-badges.primary>{{ $statusLabel }}</x-badges.primary>
        @elseif($badgeClass === 'success')
          <x-badges.success>{{ $statusLabel }}</x-badges.success>
        @elseif($badgeClass === 'warning')
          <x-badges.warning>{{ $statusLabel }}</x-badges.warning>
        @elseif($badgeClass === 'danger')
          <x-badges.danger>{{ $statusLabel }}</x-badges.danger>
        @elseif($badgeClass === 'shs')
          <x-badges.shs>{{ $statusLabel }}</x-badges.shs>
        @else
          <x-badges.default>{{ $statusLabel }}</x-badges.default>
        @endif
        
        @if($row->shipper)
            <span class="text-xs text-gray-500">via {{ $row->shipper->name }}</span>
        @endif
    </div>
</x-livewire-tables::table.cell>

{{-- Total Cost --}}
@if($this->shouldShowCosts())
<x-livewire-tables::table.cell>
    <div class="text-sm font-medium text-gray-900">
        @if($this->shouldShowCostForPackage($row))
            ${{ number_format($row->total_cost, 2) }}
        @else
            <span class="text-gray-400">-</span>
        @endif
    </div>
</x-livewire-tables::table.cell>
@endif

{{-- Cost Breakdown Columns (conditionally shown) --}}
@if($this->showCostBreakdown && $this->shouldShowCosts())
    {{-- Freight --}}
    <x-livewire-tables::table.cell>
        <div class="text-sm text-gray-900">
            @if($this->shouldShowCostForPackage($row))
                ${{ number_format($row->freight_price ?? 0, 2) }}
            @else
                <span class="text-gray-400">-</span>
            @endif
        </div>
    </x-livewire-tables::table.cell>

    {{-- Customs --}}
    <x-livewire-tables::table.cell>
        <div class="text-sm text-gray-900">
            @if($this->shouldShowCostForPackage($row))
                ${{ number_format($row->clearance_fee ?? 0, 2) }}
            @else
                <span class="text-gray-400">-</span>
            @endif
        </div>
    </x-livewire-tables::table.cell>

    {{-- Storage --}}
    <x-livewire-tables::table.cell>
        <div class="text-sm text-gray-900">
            @if($this->shouldShowCostForPackage($row))
                ${{ number_format($row->storage_fee ?? 0, 2) }}
            @else
                <span class="text-gray-400">-</span>
            @endif
        </div>
    </x-livewire-tables::table.cell>

    {{-- Delivery --}}
    <x-livewire-tables::table.cell>
        <div class="text-sm text-gray-900">
            @if($this->shouldShowCostForPackage($row))
                ${{ number_format($row->delivery_fee ?? 0, 2) }}
            @else
                <span class="text-gray-400">-</span>
            @endif
        </div>
    </x-livewire-tables::table.cell>
@endif

{{-- Details/Actions --}}
<x-livewire-tables::table.cell>
    <div class="flex space-x-2">
        <button 
            type="button"
            onclick="
                console.log('Button clicked for package {{ $row->id }}');
                window.livewire.emit('showPackageDetails', {{ $row->id }});
            "
            class="text-blue-600 hover:text-blue-900 text-sm font-medium"
        >
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            View Details
        </button>
    </div>
</x-livewire-tables::table.cell>