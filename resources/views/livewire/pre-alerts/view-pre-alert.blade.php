<div>
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Update Pre-Alert
        </h3>

        {{-- <button wire:click="create()" class="bg-wax-flower-500 hover:bg-wax-flower-700 text-white font-bold py-2 px-4 rounded">
            Create Pre-Alert
        </button> --}}
    </div>

    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5 py-4">
        <!-- Air Freight Address Card -->
        <div class="w-full bg-white rounded-lg shadow h-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-auto">
                        <h1 class="text-lg font-bold text-gray-900 flex items-center">
                            <!-- <x-air class="h-8 w-auto mr-2 text-wax-flower-600 flex-shrink-0" /> -->
                            <span>Update your pre-alert</span>
                        </h1>
                        <p class="mt-2 text-sm text-gray-700">Add the missing information in order to facilitate an expeditious process.</p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="bg-white px-4 pt-5 pb-4">
                        <div class="text-left">
                            <div class="mt-4 mb-5">
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
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-200 leading-tight focus:outline-none focus:shadow-outline" id="tracking_number" placeholder="Enter tracking number for the item" wire:model="tracking_number" autocomplete="off" readonly="readonly">
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
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button wire:click.prevent="update()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-wax-flower-600 text-base font-medium text-white hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500 sm:col-start-2 sm:text-sm">
                                Update
                            </button>
                            <a href="/pre-alerts" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sea Freight Address Card -->
        <div class="w-full bg-white rounded-lg shadow h-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-auto">
                        <h1 class="text-lg font-bold text-gray-900 flex items-center">
                            <!-- <x-sea class="h-8 w-auto mr-2 text-wax-flower-600 flex-shrink-0" /> -->
                            <span>Preview your invoice</span>
                        </h1>
                        <p class="mt-2 text-sm text-gray-700">.</p>
                    </div>
                </div>
                <div class="mt-4">

                </div>
            </div>
        </div>
    </div>
</div>