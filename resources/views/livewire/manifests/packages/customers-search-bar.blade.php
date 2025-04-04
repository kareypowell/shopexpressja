<div class="relative">
    <input
        type="text"
        class="form-input"
        placeholder="Search Customers..."
        wire:model="query"
        wire:keydown.escape="reset"
        wire:keydown.tab="reset"
        wire:keydown.arrow-up="decrementHighlight"
        wire:keydown.arrow-down="incrementHighlight"
        wire:keydown.enter="selectCustomer" />

    <div wire:loading class="absolute z-10 w-full bg-white rounded-t-none shadow-lg list-group">
        <div class="list-item">Searching...</div>
    </div>

    @if(!empty($query))
    <div class="fixed top-0 bottom-0 left-0 right-0" wire:click="reset"></div>

    <div class="absolute z-10 w-full bg-white rounded-t-none shadow-lg list-group">
        @if(!empty($customers))
        @foreach($customers as $i => $customer)
        <a
            href="{{ route('show-customer', $customer['id']) }}"
            class="list-item {{ $highlightIndex === $i ? 'highlight' : '' }}">{{ $customer['name'] }}</a>
        @endforeach
        @else
        <div class="list-item">No results!</div>
        @endif
    </div>
    @endif
</div>