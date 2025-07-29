<div>
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Update Manifest
        </h3>
    </div>

    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5 py-4">
        <!-- Air Freight Address Card -->
        <div class="w-full bg-white rounded-lg shadow h-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-auto">
                        <h1 class="text-lg font-bold text-gray-900 flex items-center">
                            <!-- <x-air class="h-8 w-auto mr-2 text-wax-flower-600 flex-shrink-0" /> -->
                            <span>Update manifest details</span>
                        </h1>
                        <p class="mt-2 text-sm text-gray-700">You will only be able to upadate this manifest while it's still open.</p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="bg-white px-4 pt-5 pb-4">
                        <div class="text-left">
                            <div class="mt-6 mb-5">
                                <label for="type" class="block text-gray-700 text-sm font-bold mb-2">Select manifest type</label>
                                <div class="mt-1 rounded-md shadow-sm">
                                    <select wire:model="type" id="type" required autofocus class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('type') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                                        <option value="" selected>--- Select type ---</option>
                                        <option value="air">Air</option>
                                        <option value="sea">Sea</option>
                                    </select>
                                </div>
                                @error('type')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Manifest Name</label>
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" placeholder="Enter name for the manifest" wire:model="name" autocomplete="off">
                                @error('name') <span class="text-red-500">{{ $message }}</span>@enderror
                            </div>

                            <div class="mb-4">
                                <label for="reservation_number" class="block text-gray-700 text-sm font-bold mb-2">Reservation Number</label>
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="reservation_number" placeholder="Enter the reservation number" wire:model="reservation_number" autocomplete="off">
                                @error('reservation_number') <span class="text-red-500">{{ $message }}</span>@enderror
                            </div>

                            <!-- Air Manifest Fields -->
                            @if($type === 'air')
                                <div class="mb-4">
                                    <label for="flight_number" class="block text-gray-700 text-sm font-bold mb-2">Flight Number</label>
                                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="flight_number" placeholder="Enter the flight number for the manifest" wire:model="flight_number" autocomplete="off">
                                    @error('flight_number') <span class="text-red-500">{{ $message }}</span>@enderror
                                </div>

                                <div class="mb-4">
                                    <label for="flight_destination" class="block text-gray-700 text-sm font-bold mb-2">Flight Destination</label>
                                    <select wire:model.lazy="flight_destination" id="flight_destination" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue focus:border-blue-300 transition duration-150 ease-in-out sm:text-sm sm:leading-5 @error('flight_destination') border-red-300 text-red-900 placeholder-red-300 focus:border-red-300 focus:ring-red @enderror">
                                        <option value="" selected>--- Select destination ---</option>
                                        <option value="MIA-KGN">MIA-KGN</option>
                                    </select>
                                    @error('flight_destination') <span class="text-red-500">{{ $message }}</span>@enderror
                                </div>
                            @endif

                            <!-- Sea Manifest Fields -->
                            @if($type === 'sea')
                                <div class="mb-4">
                                    <label for="vessel_name" class="block text-gray-700 text-sm font-bold mb-2">Vessel Name</label>
                                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="vessel_name" placeholder="Enter the vessel name" wire:model="vessel_name" autocomplete="off">
                                    @error('vessel_name') <span class="text-red-500">{{ $message }}</span>@enderror
                                </div>

                                <div class="mb-4">
                                    <label for="voyage_number" class="block text-gray-700 text-sm font-bold mb-2">Voyage Number</label>
                                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="voyage_number" placeholder="Enter the voyage number" wire:model="voyage_number" autocomplete="off">
                                    @error('voyage_number') <span class="text-red-500">{{ $message }}</span>@enderror
                                </div>

                                <div class="mb-4">
                                    <label for="departure_port" class="block text-gray-700 text-sm font-bold mb-2">Departure Port</label>
                                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="departure_port" placeholder="Enter the departure port" wire:model="departure_port" autocomplete="off">
                                    @error('departure_port') <span class="text-red-500">{{ $message }}</span>@enderror
                                </div>

                                <div class="mb-4">
                                    <label for="arrival_port" class="block text-gray-700 text-sm font-bold mb-2">Arrival Port <span class="text-gray-500 text-xs">(Optional)</span></label>
                                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="arrival_port" placeholder="Enter the arrival port" wire:model="arrival_port" autocomplete="off">
                                    @error('arrival_port') <span class="text-red-500">{{ $message }}</span>@enderror
                                </div>

                                <div class="mb-4">
                                    <label for="estimated_arrival_date" class="block text-gray-700 text-sm font-bold mb-2">Estimated Arrival Date <span class="text-gray-500 text-xs">(Optional)</span></label>
                                    <input type="date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="estimated_arrival_date" placeholder="Select the estimated arrival date" wire:model="estimated_arrival_date" autocomplete="off">
                                    @error('estimated_arrival_date') <span class="text-red-500">{{ $message }}</span>@enderror
                                </div>
                            @endif

                            <div class="mb-4">
                                <label for="exchange_rate" class="block text-gray-700 text-sm font-bold mb-2">Exchange Rate</label>
                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="exchange_rate" placeholder="Enter the daily exchange rate" wire:model="exchange_rate" autocomplete="off">
                                @error('exchange_rate') <span class="text-red-500">{{ $message }}</span>@enderror
                            </div>

                            <div class="mb-4">
                                <label for="shipment_date" class="block text-gray-700 text-sm font-bold mb-2">Shipment Date</label>
                                <input type="date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="shipment_date" placeholder="Select the shipment date" wire:model="shipment_date" autocomplete="off">
                                @error('shipment_date') <span class="text-red-500">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button wire:click.prevent="update()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-wax-flower-600 text-base font-medium text-white hover:bg-wax-flower-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500 sm:col-start-2 sm:text-sm">
                                Update
                            </button>
                            <a href="/admin/manifests" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-wax-flower-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sea Freight Address Card -->
        <!-- <div class="w-full bg-white rounded-lg shadow h-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-auto">
                        <h1 class="text-lg font-bold text-gray-900 flex items-center">
                            <x-sea class="h-8 w-auto mr-2 text-wax-flower-600 flex-shrink-0" />
                            <span>Preview your invoice</span>
                        </h1>
                        <p class="mt-2 text-sm text-gray-700">.</p>
                    </div>
                </div>
                <div class="mt-4">

                </div>
            </div>
        </div> -->
    </div>
</div>