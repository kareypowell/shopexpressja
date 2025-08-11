@props(['compact' => false])

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-medium text-gray-900">Package Status Legend</h3>
        @if($compact)
            <span class="text-xs text-gray-500">Color-coded status indicators</span>
        @endif
    </div>
    
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 text-xs">
        <div class="flex items-center space-x-2">
            <x-badges.default>Pending</x-badges.default>
            @if(!$compact)
                <span class="text-gray-600">New packages</span>
            @endif
        </div>
        
        <div class="flex items-center space-x-2">
            <x-badges.primary>Processing</x-badges.primary>
            @if(!$compact)
                <span class="text-gray-600">Being processed</span>
            @endif
        </div>
        
        <div class="flex items-center space-x-2">
            <x-badges.shs>Shipped</x-badges.shs>
            @if(!$compact)
                <span class="text-gray-600">In transit</span>
            @endif
        </div>
        
        <div class="flex items-center space-x-2">
            <x-badges.warning>At Customs</x-badges.warning>
            @if(!$compact)
                <span class="text-gray-600">Customs clearance</span>
            @endif
        </div>
        
        <div class="flex items-center space-x-2">
            <x-badges.success>Ready</x-badges.success>
            @if(!$compact)
                <span class="text-gray-600">Ready for pickup</span>
            @endif
        </div>
        
        <div class="flex items-center space-x-2">
            <x-badges.success>Delivered</x-badges.success>
            @if(!$compact)
                <span class="text-gray-600">Completed</span>
            @endif
        </div>
        
        <div class="flex items-center space-x-2">
            <x-badges.danger>Delayed</x-badges.danger>
            @if(!$compact)
                <span class="text-gray-600">Issue/problem</span>
            @endif
        </div>
    </div>
    
    @if($compact)
        <div class="mt-3 text-xs text-gray-500 border-t border-gray-100 pt-2">
            <strong>Quick Reference:</strong> 
            <span class="text-gray-400">Gray</span> = New, 
            <span class="text-wax-flower-600">Purple</span> = Processing, 
            <span class="text-wax-flower-600">Brand</span> = Shipped, 
            <span class="text-yellow-600">Yellow</span> = Customs, 
            <span class="text-green-600">Green</span> = Ready/Delivered, 
            <span class="text-red-600">Red</span> = Delayed
        </div>
    @endif
</div>