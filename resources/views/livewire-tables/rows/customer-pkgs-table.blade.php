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
    @if($row->isSeaPackage() && $row->container_type)
      <x-badges.default>{{ ucfirst($row->container_type) }}</x-badges.default>
    @else
      -
    @endif
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    @if($row->isSeaPackage() && $row->length_inches && $row->width_inches && $row->height_inches)
      <span class="text-sm text-gray-600">
        {{ $row->length_inches }}" × {{ $row->width_inches }}" × {{ $row->height_inches }}"
      </span>
    @else
      -
    @endif
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    @if($row->isSeaPackage() && $row->cubic_feet)
      <x-badges.success>{{ number_format($row->cubic_feet, 3) }} ft³</x-badges.success>
    @else
      -
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
    @if($row->isSeaPackage() && $row->items->count() > 0)
      <div class="mt-2">
        <details class="text-xs">
          <summary class="cursor-pointer text-gray-600 hover:text-gray-800">
            {{ $row->items->count() }} item(s)
          </summary>
          <div class="mt-1 pl-2 border-l-2 border-gray-200">
            @foreach($row->items as $item)
              <div class="text-gray-600 mb-1">
                <span class="font-medium">{{ $item->description }}</span>
                <span class="text-gray-500">(Qty: {{ $item->quantity }})</span>
                @if($item->weight_per_item)
                  <span class="text-gray-500">{{ number_format($item->weight_per_item, 2) }} lbs each</span>
                @endif
              </div>
            @endforeach
          </div>
        </details>
      </div>
    @endif
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>

  </div>
</x-livewire-tables::table.cell>