<div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

    <!-- This element is to trick the browser into centering the modal contents. -->
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

    <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
      <form>
        <div>
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-wax-flower-100">
            <!-- Heroicon name: money -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-wax-flower-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>

          <div class="mt-3 text-center sm:mt-5">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
              Create Purchase Request
            </h3>
            <div class="mt-2">
              <p class="text-sm text-gray-500">
                Fill in the form below to create a new purchase request.
              </p>
            </div>

            <div class="bg-white px-4 pt-5 pb-4">
              <div class="text-left">
                <div class="mb-4">
                  <label for="item_name" class="block text-gray-700 text-sm font-bold mb-2">Item Name</label>
                  <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="item_name" placeholder="Enter name of the item" wire:model="item_name" autocomplete="off">
                  @error('item_name') <span class="text-red-500">{{ $message }}</span>@enderror
                </div>

                <div class="mb-4">
                  <label for="item_url" class="block text-gray-700 text-sm font-bold mb-2">Item URL</label>
                  <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="item_url" placeholder="Copy/paste the URL of the item" wire:model="item_url" autocomplete="off">
                  @error('item_url') <span class="text-red-500">{{ $message }}</span>@enderror
                </div>

                <!-- Grid layout for Quantity and Unit Price -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                  <div>
                    <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">Quantity</label>
                    <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="quantity" placeholder="Enter quantity" wire:model="quantity" autocomplete="off">
                    @error('quantity') <span class="text-red-500">{{ $message }}</span>@enderror
                  </div>
                  <div>
                    <label for="unit_price" class="block text-gray-700 text-sm font-bold mb-2">Unit Price (USD)</label>
                    <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="unit_price" placeholder="Enter unit price" wire:model="unit_price" autocomplete="off">
                    @error('unit_price') <span class="text-red-500">{{ $message }}</span>@enderror
                  </div>
                </div>

                <!-- Grid layout for Shipping Fee and Tax -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                  <div>
                    <label for="shipping_fee" class="block text-gray-700 text-sm font-bold mb-2">Shipping Fee (USD)</label>
                    <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="shipping_fee" placeholder="Enter shipping fee" wire:model="shipping_fee" autocomplete="off">
                    @error('shipping_fee') <span class="text-red-500">{{ $message }}</span>@enderror
                  </div>
                  <div>
                    <label for="tax" class="block text-gray-700 text-sm font-bold mb-2">Tax (USD)</label>
                    <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="tax" placeholder="Enter tax amount" wire:model="tax" autocomplete="off">
                    @error('tax') <span class="text-red-500">{{ $message }}</span>@enderror
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
          <button wire:click.prevent="store()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-wax-flower-600 text-base font-medium text-white hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500 sm:col-start-2 sm:text-sm">
            Save
          </button>
          <button wire:click="closeModal()" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500 sm:mt-0 sm:col-start-1 sm:text-sm">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</div>