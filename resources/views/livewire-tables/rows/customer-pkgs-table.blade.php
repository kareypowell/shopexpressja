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
    @if($row->status == 'processing')
    <x-badges.primary>{{ ucfirst($row->status) }}</x-badges.primary>
    @elseif($row->status == 'shipped')
    <x-badges.shs>{{ ucfirst($row->status) }}</x-badges.shs>
    @elseif($row->status == 'delayed')
    <x-badges.warning>{{ ucfirst($row->status) }}</x-badges.warning>
    @elseif($row->status == 'ready')
    <x-badges.success>{{ ucfirst($row->status) }}</x-badges.success>
    @else
    <x-badges.default>{{ ucfirst($row->status) }}</x-badges.default>
    @endif
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>

  </div>
</x-livewire-tables::table.cell>