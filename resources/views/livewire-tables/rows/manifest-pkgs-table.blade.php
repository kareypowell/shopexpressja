<x-livewire-tables::table.cell>
  <div>
    {{ $row->user->full_name }}
    @if($row->user->profile->account_number)
    <p>
      <span class="text-sm text-gray-500">
        <small><strong>Account Number: <x-badges.shs>{{ $row->user->profile->account_number }}</x-badges.shs></small>
      </span>
    </p>
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
    <!-- <a href="{{ route('manifests.packages', $row->id) }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-wax-flower-600 hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500">
      View
    </a> -->

    <a href="{{ route('manifests.packages.edit', [$row->manifest_id, $row->id]) }}" type="button" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
      Edit
    </a>
  </div>
</x-livewire-tables::table.cell>