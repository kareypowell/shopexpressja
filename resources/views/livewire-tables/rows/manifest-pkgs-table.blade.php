<x-livewire-tables::table.cell>
  <div>
    @if($row->user)
      {{ $row->user->full_name }}
      @if($row->user->profile && $row->user->profile->account_number)
      <p>
        <span class="text-sm text-gray-500">
          <small><strong>Account Number: <x-badges.shs>{{ $row->user->profile->account_number }}</x-badges.shs></small>
        </span>
      </p>
      @endif
    @else
      <span class="text-red-500 text-sm">User not found</span>
    @endif
  </div>
</x-livewire-tables::table.cell>

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
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->shipper->name }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    <x-badges.success>${{ number_format($row->estimated_value, 2) }} USD</x-badges.success>
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    <x-badges.success>${{ number_format($row->freight_price, 2) }} JMD</x-badges.success>
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
    {{ $row->created_at->format('F j, Y @ G:i A') }}
  </div>
</x-livewire-tables::table.cell>

<!-- <x-livewire-tables::table.cell>
  <div>
    {{ $row->updated_at->format('F j, Y @ G:i A') }}
  </div>
</x-livewire-tables::table.cell> -->

<x-livewire-tables::table.cell>
  <div>


    <a href="{{ route('admin.manifests.packages.edit', [$row->manifest_id, $row->id]) }}" type="button" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
      Edit
    </a>
  </div>
</x-livewire-tables::table.cell>