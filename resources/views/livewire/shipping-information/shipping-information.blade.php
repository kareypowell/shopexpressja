<div>
    <h3 class="mt-5 text-base font-semibold text-gray-900">Shipping Addresses</h3>
    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5 py-4">
        <!-- Air Freight Address Card -->
        <div class="w-full bg-white rounded-lg shadow h-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-auto">
                        <h1 class="text-lg font-bold text-gray-900 flex items-center">
                            <x-air class="h-8 w-auto mr-2 text-wax-flower-600 flex-shrink-0" />
                            <span>Air Freight Address</span>
                        </h1>
                        <p class="mt-2 text-sm text-gray-700">Use the address below to send items via air. Be sure to enter your address in this format.</p>
                    </div>
                </div>
                <div class="mt-4">
                    <table class="min-w-full divide-y divide-gray-300">
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900 w-1/3">Name</td>
                                <td class="py-3 text-sm text-gray-500">{{ auth()->user()->first_name . ' ' . auth()->user()->last_name }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900">Address Line 1</td>
                                <td class="py-3 text-sm text-gray-500">{{ $address->street_address}}</td>
                            </tr>
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900">Address Line 2</td>
                                <td class="py-3 text-sm font-bold text-wax-flower-500">{{ 'A' . auth()->user()->profile->account_number }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900">City</td>
                                <td class="py-3 text-sm text-gray-500">{{ $address->city }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900">State</td>
                                <td class="py-3 text-sm text-gray-500">{{ $address->state }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900">Zip Code</td>
                                <td class="py-3 text-sm text-gray-500">{{ $address->zip_code }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sea Freight Address Card -->
        <div class="w-full bg-white rounded-lg shadow h-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-auto">
                        <h1 class="text-lg font-bold text-gray-900 flex items-center">
                            <x-sea class="h-8 w-auto mr-2 text-wax-flower-600 flex-shrink-0" />
                            <span>Sea Freight Address</span>
                        </h1>
                        <p class="mt-2 text-sm text-gray-700">Use the address below to send items via ocean. Be sure to enter your address in this format.</p>
                    </div>
                </div>
                <div class="mt-4">
                    <table class="min-w-full divide-y divide-gray-300">
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900 w-1/3">Name</td>
                                <td class="py-3 text-sm text-gray-500">{{ auth()->user()->first_name . ' ' . auth()->user()->last_name }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900">Address Line 1</td>
                                <td class="py-3 text-sm text-gray-500">{{ $address->street_address }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900">Address Line 2</td>
                                <td class="py-3 text-sm font-bold text-wax-flower-500">{{ 'O' . auth()->user()->profile->account_number }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900">City</td>
                                <td class="py-3 text-sm text-gray-500">{{ $address->city }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900">State</td>
                                <td class="py-3 text-sm text-gray-500">{{ $address->state }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 text-sm font-medium text-gray-900">Zip Code</td>
                                <td class="py-3 text-sm text-gray-500">{{ $address->zip_code }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>