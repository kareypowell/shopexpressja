<div>
    <h3 class="mt-5 text-base font-semibold text-gray-900">Shipping Addresses</h3>
    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 py-4">
        <!-- Air Freight Address Card -->
        <div class="w-full bg-white rounded-lg shadow">
            <div class="px-4 py-4">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-base font-bold text-lg text-gray-900">Air Freight Address</h1>
                            <p class="mt-2 text-sm text-gray-700">Use the address below to send items via air. Be sure to enter your address in this format.</p>
                        </div>
                    </div>
                    <div class="mt-8 flow-root">
                        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="inline-block min-w-full py-2 align-middle">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <tr>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">Name</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ auth()->user()->first_name . ' ' . auth()->user()->last_name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">Address Line 1</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $address->street_address}}</td>
                                        </tr>
                                        <tr>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">Address Line 2</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-bold text-wax-flower-500">{{ 'A' . auth()->user()->profile->account_number }}</td>
                                        </tr>
                                        <tr>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">City</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $address->city }}</td>
                                        </tr>
                                        <tr>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">State</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $address->state }}</td>
                                        </tr>
                                        <tr>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">Zip Code</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $address->zip_code }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sea Freight Address Card -->
        <div class="w-full bg-white rounded-lg shadow">
            <div class="px-4 py-4">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-base font-bold text-lg text-gray-900">Sea Freight Address</h1>
                            <p class="mt-2 text-sm text-gray-700">Use the address below to send items via ocean. Be sure to enter your address in this format.</p>
                        </div>
                    </div>
                    <div class="mt-8 flow-root">
                        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="inline-block min-w-full py-2 align-middle">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <tr>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">Name</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ auth()->user()->first_name . ' ' . auth()->user()->last_name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">Address Line 1</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $address->street_address }}</td>
                                        </tr>
                                        <tr>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">Address Line 2</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-bold text-wax-flower-500">{{ 'O' . auth()->user()->profile->account_number }}</td>
                                        </tr>
                                        <tr>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">City</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $address->city }}</td>
                                        </tr>
                                        <tr>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">State</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $address->state }}</td>
                                        </tr>
                                        <tr>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">Zip Code</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $address->zip_code }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Third card (empty) -->
        <div class="w-full bg-white rounded-lg shadow md:col-span-2 lg:col-span-1">
            <div class="px-4 py-4">
                <!-- Content for third card if needed -->
            </div>
        </div>
    </div>
</div>