<x-livewire-tables::table.cell>
  <div>
    {!! $this->highlightSearchTerm($row->first_name) !!}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {!! $this->highlightSearchTerm($row->last_name) !!}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {!! $this->highlightSearchTerm($row->email) !!}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {!! $this->highlightSearchTerm($row->profile->telephone_number) !!}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    <x-badges.shs>{!! $this->highlightSearchTerm($row->profile->account_number) !!}</x-badges.shs>
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    <x-badges.warning>{!! $this->highlightSearchTerm($row->profile->tax_number) !!}</x-badges.warning>
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {!! $this->highlightSearchTerm($row->profile->parish) !!}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    @if($row->trashed())
      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
        Deleted
      </span>
    @else
      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
        Active
      </span>
    @endif
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div>
    {{ $row->created_at->format('F j, Y @ G:i A') }}
  </div>
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
  <div class="flex items-center space-x-1">
    <!-- View Profile Button -->
    @can('customer.view', $row)
      <button 
        wire:click="viewCustomer({{ $row->id }})" 
        class="inline-flex items-center justify-center w-8 h-8 border border-transparent rounded shadow-sm text-white bg-wax-flower-600 hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500 transition-colors duration-200"
        title="View customer profile"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        </svg>
      </button>
    @endcan

    @if(!$row->trashed())
      <!-- Edit Button -->
      @can('customer.update', $row)
        <button 
          wire:click="editCustomer({{ $row->id }})" 
          class="inline-flex items-center justify-center w-8 h-8 border border-transparent rounded shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
          title="Edit customer information"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
          </svg>
        </button>
      @endcan

      <!-- Delete Button -->
      @can('customer.delete', $row)
        @if($row->canBeDeleted())
          <button 
            wire:click="confirmDelete({{ $row->id }})" 
            class="inline-flex items-center justify-center w-8 h-8 border border-transparent rounded shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
            title="Delete customer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
          </button>
        @endif
      @endcan
    @else
      <!-- Deleted Status Icon -->
      <span class="inline-flex items-center justify-center w-8 h-8 text-red-800 bg-red-100 rounded border border-red-200" title="Customer deleted">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
        </svg>
      </span>

      <!-- Restore Button -->
      @can('customer.restore', $row)
        @if($row->canBeRestored())
          <button 
            wire:click="confirmRestore({{ $row->id }})" 
            class="inline-flex items-center justify-center w-8 h-8 border border-transparent rounded shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
            title="Restore customer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
          </button>
        @endif
      @endcan
    @endif
  </div>
</x-livewire-tables::table.cell>