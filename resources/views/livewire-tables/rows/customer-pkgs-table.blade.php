<x-livewire-tables::table.cell>
  <div>
    <x-badges.primary>{{ $row->tracking_number }}</x-badges.primary>
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->description }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->shipper->name }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    @if($row->status == 'Processing')
    <x-badges.primary>{{ $row->status }}</x-badges.primary>
    @elseif($row->status == 'Shipped')
    <x-badges.success>{{ $row->status }}</x-badges.success>
    @elseif($row->status == 'Delayed')
    <x-badges.warning>{{ $row->status }}</x-badges.warning>
    @elseif($row->status == 'Ready for Pickup')
    <x-badges.primary>{{ $row->status }}</x-badges.primary>
    @else
    <x-badges.default>{{ $row->status }}</x-badges.default>
    @endif
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>

  </div>
</x-livewire-tables::table.cell>