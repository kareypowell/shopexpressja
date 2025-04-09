<x-livewire-tables::table.cell>
  <div>
    {{ $row->item_name }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    <a href="{{ $row->item_url }}" class="text-wax-flower-500 font-semibold" target="_new">Click here to view {{ $row->item_name }}</a>
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->quantity }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    ${{ number_format($row->unit_price, 2) }} USD
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    ${{ number_format($row->shipping_fee, 2) }} USD
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    ${{ number_format($row->tax, 2) }} USD
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    ${{ number_format($row->total_price, 2) }} USD
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    @if($row->status == 'approved')
    <x-badges.success>{{ ucfirst($row->status) }}</x-badges.success>
    @elseif($row->status == 'rejected')
    <x-badges.danger>{{ ucfirst($row->status) }}</x-badges.danger>
    @elseif($row->status == 'cancelled')
    <x-badges.warning>{{ ucfirst($row->status) }}</x-badges.warning>
    @elseif($row->status == 'completed')
    <x-badges.primary>{{ ucfirst($row->status) }}</x-badges.primary>
    @else
    <x-badges.default>{{ ucfirst($row->status) }}</x-badges.default>
    @endif
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->remarks }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    <a href="{{ route('view-purchase-request', $row->id) }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-wax-flower-600 hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500">
      View
    </a>
  </div>
</x-livewire-tables::table.cell>