<x-livewire-tables::table.cell>
  <div>
    {{ $row->user->full_name }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    <x-badges.shs>{{ $row->user->profile->account_number }}</x-badges.shs>
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->shipper->name }}
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
    <x-badges.success>${{ number_format($row->value, 2) }} USD</x-badges.success>
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    @if($row->packagePreAlert != null && $row->packagePreAlert->status == 'processing')
    <x-badges.primary>{{ ucfirst($row->packagePreAlert->status) }}</x-badges.primary>
    @elseif($row->packagePreAlert != null && $row->packagePreAlert->status == 'shipped')
    <x-badges.shs>{{ ucfirst($row->packagePreAlert->status) }}</x-badges.shs>
    @elseif($row->packagePreAlert != null && $row->packagePreAlert->status == 'delayed')
    <x-badges.warning>{{ ucfirst($row->packagePreAlert->status) }}</x-badges.warning>
    @elseif($row->packagePreAlert != null && $row->packagePreAlert->status == 'ready')
    <x-badges.success>{{ ucfirst($row->packagePreAlert->status) }}</x-badges.success>
    @else
    <x-badges.default>Not available</x-badges.default>
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
    @if($row->file_path != 'Not available')
    <a href="{{ $row->file_path }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" target="_new">
      View Invoice
    </a>
    @endif
  </div>
</x-livewire-tables::table.cell>