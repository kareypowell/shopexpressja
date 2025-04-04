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
    {{ $row->weight }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    @if($row->status == 'processing')
    <x-badges.primary>{{ $row->status }}</x-badges.primary>
    @elseif($row->status == 'shipped')
    <x-badges.success>{{ $row->status }}</x-badges.success>
    @elseif($row->status == 'delayed')
    <x-badges.warning>{{ $row->status }}</x-badges.warning>
    @elseif($row->status == 'ready')
    <x-badges.primary>{{ $row->status }}</x-badges.primary>
    @else
    <x-badges.default>{{ $row->status }}</x-badges.default>
    @endif
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->shipper->name }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ number_format($row->estimated_value, 2) }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->created_at->format('F j, Y @ G:i A') }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->updated_at->format('F j, Y @ G:i A') }}
  </div>
</x-livewire-tables::table.cell>