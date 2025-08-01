{{-- Package Detail Modal --}}
<div x-data="{ open: false, packageData: null }" 
     x-on:show-package-details.window="open = true; packageData = $event.detail"
     x-show="open" 
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    
    {{-- Backdrop --}}
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="open" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 transition-opacity" 
             aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75" @click="open = false"></div>
        </div>

        {{-- This element is to trick the browser into centering the modal contents. --}}
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        {{-- Modal panel --}}
        <div x-show="open" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
            
            <div class="sm:flex sm:items-start">
                <div class="w-full">
                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Package Details</h3>
                                <p class="text-sm text-gray-500" x-text="packageData?.tracking_number"></p>
                            </div>
                        </div>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- Content --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Basic Information --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Basic Information</h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Tracking Number:</dt>
                                    <dd class="text-sm font-medium text-gray-900" x-text="packageData?.tracking_number"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Warehouse Receipt:</dt>
                                    <dd class="text-sm font-medium text-gray-900" x-text="packageData?.warehouse_receipt_no || 'N/A'"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Description:</dt>
                                    <dd class="text-sm font-medium text-gray-900" x-text="packageData?.description"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Status:</dt>
                                    <dd class="text-sm">
                                        <span x-text="packageData?.status" 
                                              :class="{
                                                  'bg-blue-100 text-blue-800': packageData?.status === 'processing',
                                                  'bg-green-100 text-green-800': packageData?.status === 'shipped',
                                                  'bg-yellow-100 text-yellow-800': packageData?.status === 'delayed',
                                                  'bg-purple-100 text-purple-800': packageData?.status === 'ready_for_pickup',
                                                  'bg-gray-100 text-gray-800': !['processing', 'shipped', 'delayed', 'ready_for_pickup'].includes(packageData?.status)
                                              }"
                                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Date Created:</dt>
                                    <dd class="text-sm font-medium text-gray-900" x-text="packageData?.created_at"></dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Shipping Information --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Shipping Information</h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Weight:</dt>
                                    <dd class="text-sm font-medium text-gray-900" x-text="packageData?.weight + ' lbs'"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Estimated Value:</dt>
                                    <dd class="text-sm font-medium text-gray-900" x-text="'$' + (packageData?.estimated_value || '0.00')"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Shipper:</dt>
                                    <dd class="text-sm font-medium text-gray-900" x-text="packageData?.shipper?.name || 'N/A'"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Office:</dt>
                                    <dd class="text-sm font-medium text-gray-900" x-text="packageData?.office?.name || 'N/A'"></dd>
                                </div>
                                <template x-if="packageData?.container_type">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Container Type:</dt>
                                        <dd class="text-sm font-medium text-gray-900 capitalize" x-text="packageData?.container_type"></dd>
                                    </div>
                                </template>
                                <template x-if="packageData?.cubic_feet">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Cubic Feet:</dt>
                                        <dd class="text-sm font-medium text-gray-900" x-text="packageData?.cubic_feet + ' ft³'"></dd>
                                    </div>
                                </template>
                            </dl>
                        </div>

                        {{-- Cost Breakdown --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Cost Breakdown</h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Freight:</dt>
                                    <dd class="text-sm font-medium text-gray-900" x-text="'$' + (packageData?.freight_price || '0.00')"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Customs Duty:</dt>
                                    <dd class="text-sm font-medium text-gray-900" x-text="'$' + (packageData?.customs_duty || '0.00')"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Storage Fee:</dt>
                                    <dd class="text-sm font-medium text-gray-900" x-text="'$' + (packageData?.storage_fee || '0.00')"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Delivery Fee:</dt>
                                    <dd class="text-sm font-medium text-gray-900" x-text="'$' + (packageData?.delivery_fee || '0.00')"></dd>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-gray-200">
                                    <dt class="text-sm font-medium text-gray-900">Total Cost:</dt>
                                    <dd class="text-sm font-bold text-gray-900" 
                                        x-text="'$' + ((parseFloat(packageData?.freight_price || 0) + parseFloat(packageData?.customs_duty || 0) + parseFloat(packageData?.storage_fee || 0) + parseFloat(packageData?.delivery_fee || 0)).toFixed(2))">
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Package Items (if sea package) --}}
                        <div class="bg-gray-50 rounded-lg p-4" x-show="packageData?.items && packageData.items.length > 0">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Package Items</h4>
                            <div class="space-y-2">
                                <template x-for="item in packageData?.items || []" :key="item.id">
                                    <div class="bg-white rounded p-3 border border-gray-200">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900" x-text="item.description"></p>
                                                <p class="text-xs text-gray-500">Quantity: <span x-text="item.quantity"></span></p>
                                            </div>
                                            <div class="text-right" x-show="item.weight_per_item">
                                                <p class="text-xs text-gray-500" x-text="item.weight_per_item + ' lbs each'"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="mt-6 flex justify-end">
                        <button @click="open = false" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>