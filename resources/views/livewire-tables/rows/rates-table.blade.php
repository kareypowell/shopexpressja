<x-livewire-tables::table.cell>
  <div>
    @if($row->type == 'air')
    <x-badges.primary>{{ ucfirst($row->type) }}</x-badges.primary>
    @elseif($row->type == 'sea')
    <x-badges.success>{{ ucfirst($row->type) }}</x-badges.success>
    @endif
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    @if($row->type == 'air')
      {{ number_format($row->weight, 1) }} lbs
    @elseif($row->type == 'sea')
      {{ number_format($row->min_cubic_feet, 1) }} - {{ number_format($row->max_cubic_feet, 1) }} ft³
    @endif
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    ${{ number_format($row->price, 2) }}
    @if($row->type == 'sea')
      <span class="text-gray-500 text-sm">/ ft³</span>
    @endif
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    ${{ number_format($row->processing_fee, 2) }}
  </div>
</x-livewire-tables::table.cell>

<!-- <x-livewire-tables::table.cell>
  <div>
    {{ $row->created_at->format('F j, Y @ G:i A') }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->updated_at->format('F j, Y @ G:i A') }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>

  </div>
</x-livewire-tables::table.cell> -->