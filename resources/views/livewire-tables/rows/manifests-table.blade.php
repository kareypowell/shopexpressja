<x-livewire-tables::table.cell>
  <div>
    {{ $row->name }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->shipment_date }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    <x-badges.primary>{{ $row->reservation_number }}</x-badges.primary>
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    <x-badges.primary>{{ $row->flight_number }}</x-badges.primary>
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->flight_destination }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    <x-badges.success>${{ number_format($row->exchange_rate, 2) }}</x-badges.success>
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ ucfirst($row->type) }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    @if($row->is_open == true)
    <x-badges.primary>Yes</x-badges.primary>
    @elseif($row->is_open == false)
    <x-badges.success>No</x-badges.success>
    @endif
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->created_at->format('F j, Y @ G:i A') }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    <a href="{{ route('manifests.packages', $row->id) }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-wax-flower-600 hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500">
      View
    </a>

    <a href="#" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
      Edit
    </a>
  </div>
</x-livewire-tables::table.cell>