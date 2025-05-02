<div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

    <!-- This element is to trick the browser into centering the modal contents. -->
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

    <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
      <form>
        <div>
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-wax-flower-100">
            <!-- Heroicon name: bell-alert -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-wax-flower-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5" />
            </svg>
          </div>

          <div class="mt-3 text-center sm:mt-5">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
              Create Pre-Alert
            </h3>
            <div class="mt-2">
              <p class="text-sm text-gray-500">
                Fill in the form below to create a new pre-alert.
              </p>
            </div>

            <div class="bg-white px-4 pt-5 pb-4">
              <div class="text-left">
                @if (auth()->user()->isSuperAdmin())
                <div class="mt-6 mb-5">
                  <label for="user_id" class="block text-gray-700 text-sm font-bold mb-2">Select customer</label>
                  <div class="mt-1 rounded-md shadow-sm">
                    <select wire:model.lazy="user_id" id="user_id" required autofocus class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('user_id') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                      <option value="" selected>--- Select customer ---</option>
                      @foreach($customerList as $customer)
                      <option value="{{ $customer->id }}">{{ $customer->full_name . " (" . $customer->profile->account_number . ")" }}</option>
                      @endforeach
                    </select>
                  </div>
                  @error('user_id')
                  <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>
                @endif

                <div class="mt-6 mb-5">
                  <label for="shipper_id" class="block text-gray-700 text-sm font-bold mb-2">Select your shipper (carrier)</label>
                  <div class="mt-1 rounded-md shadow-sm">
                    <select wire:model.lazy="shipper_id" id="shipper_id" required autofocus class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('shipper_id') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                      <option value="" selected>--- Select your shipper ---</option>
                      @foreach($shipperList as $shipper)
                      <option value="{{ $shipper->id }}">{{ $shipper->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  @error('parish')
                  <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>

                <div class="mb-4">
                  <label for="tracking_number" class="block text-gray-700 text-sm font-bold mb-2">Tracking Number</label>
                  <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="tracking_number" placeholder="Enter tracking number for the item" wire:model="tracking_number" autocomplete="off">
                  @error('tracking_number') <span class="text-red-500">{{ $message }}</span>@enderror
                </div>

                <div class="mb-4">
                  <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                  <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="description" placeholder="Briefly describe your item" wire:model="description" autocomplete="off" rows="4"></textarea>
                  @error('description') <span class="text-red-500">{{ $message }}</span>@enderror
                </div>

                <div class="mb-4">
                  <label for="value" class="block text-gray-700 text-sm font-bold mb-2">Value (must be in USD)</label>
                  <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="value" placeholder="Enter the value of the item" wire:model="value" autocomplete="off">
                  @error('value') <span class="text-red-500">{{ $message }}</span>@enderror
                </div>

                <div class="mb-4">
                  <label for="file_path" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Attach your invoice</label>
                  <input type="file"
                    id="file_path"
                    wire:model="file_path"
                    accept="image/*, application/pdf"
                    class="block w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 cursor-pointer dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                  <div wire:loading wire:target="file_path">
                    <span class="text-sm text-gray-500">Uploading...</span>
                  </div>
                  @error('file_path') <span class="text-red-500">{{ $message }}</span>@enderror
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