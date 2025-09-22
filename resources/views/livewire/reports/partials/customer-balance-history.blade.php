@if(count($balanceHistory) > 0)
    <!-- Balance Chart Placeholder -->
    <div class="mb-8 bg-gray-50 rounded-lg p-6">
        <h4 class="text-lg font-medium text-gray-900 mb-4">Account Balance Trend</h4>
        <div class="h-64 flex items-center justify-center border-2 border-dashed border-gray-300 rounded-lg">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500">Balance trend chart would be displayed here</p>
                <p class="text-xs text-gray-400">Chart.js integration can be added for visual representation</p>
            </div>
        </div>
    </div>

    <!-- Balance History Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($balanceHistory as $entry)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ \Carbon\Carbon::parse($entry['date'])->format('M j, Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="max-w-xs truncate">
                                {{ $entry['description'] }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <span class="{{ $entry['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                @if($entry['change'] >= 0)
                                    +${{ number_format($entry['change'], 2) }}
                                @else
                                    ${{ number_format($entry['change'], 2) }}
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <span class="{{ $entry['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                ${{ number_format($entry['balance'], 2) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($entry['type'] === 'payment') bg-green-100 text-green-800
                                @elseif($entry['type'] === 'charge') bg-red-100 text-red-800
                                @elseif($entry['type'] === 'credit') bg-blue-100 text-blue-800
                                @elseif($entry['type'] === 'refund') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($entry['type']) }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Balance Summary -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        @php
            $firstBalance = $balanceHistory[0]['balance'] - $balanceHistory[0]['change'];
            $lastBalance = end($balanceHistory)['balance'];
            $totalChanges = count($balanceHistory);
            $positiveChanges = collect($balanceHistory)->where('change', '>', 0)->count();
            $negativeChanges = collect($balanceHistory)->where('change', '<', 0)->count();
        @endphp

        <div class="bg-blue-50 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-blue-600">Starting Balance</p>
                    <p class="text-lg font-bold text-blue-900">${{ number_format($firstBalance, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">Current Balance</p>
                    <p class="text-lg font-bold text-green-900">${{ number_format($lastBalance, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-purple-50 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-purple-600">Credits</p>
                    <p class="text-lg font-bold text-purple-900">{{ $positiveChanges }}</p>
                </div>
            </div>
        </div>

        <div class="bg-red-50 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-red-600">Debits</p>
                    <p class="text-lg font-bold text-red-900">{{ $negativeChanges }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Net Change Summary -->
    <div class="mt-6 bg-gray-50 rounded-lg p-6">
        <h4 class="text-lg font-medium text-gray-900 mb-4">Period Summary</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <p class="text-sm text-gray-600">Net Change</p>
                <p class="text-2xl font-bold {{ ($lastBalance - $firstBalance) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    @if(($lastBalance - $firstBalance) >= 0)
                        +${{ number_format($lastBalance - $firstBalance, 2) }}
                    @else
                        ${{ number_format($lastBalance - $firstBalance, 2) }}
                    @endif
                </p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Total Transactions</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalChanges }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Average Transaction</p>
                <p class="text-2xl font-bold text-gray-900">
                    ${{ number_format(collect($balanceHistory)->avg('change'), 2) }}
                </p>
            </div>
        </div>
    </div>
@else
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No balance history</h3>
        <p class="mt-1 text-sm text-gray-500">This customer has no balance changes in the selected date range.</p>
    </div>
@endif