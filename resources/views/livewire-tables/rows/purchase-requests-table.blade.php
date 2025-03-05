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
    ${{ number_format($row->unit_price, 2) }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    ${{ number_format($row->shipping_fee, 2) }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    ${{ number_format($row->tax, 2) }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    ${{ number_format($row->total_price, 2) }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    @if($row->status == 'Approved')
    <x-badges.success>{{ $row->status }}</x-badges.success>
    @elseif($row->status == 'Rejected')
    <x-badges.danger>{{ $row->status }}</x-badges.danger>
    @elseif($row->status == 'Cancelled')
    <x-badges.warning>{{ $row->status }}</x-badges.warning>
    @elseif($row->status == 'Completed')
    <x-badges.primary>{{ $row->status }}</x-badges.primary>
    @else
    <x-badges.default>{{ $row->status }}</x-badges.default>
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

  </div>
</x-livewire-tables::table.cell>