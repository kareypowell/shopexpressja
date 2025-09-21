<div>
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Security Dashboard
                </h3>
                <button wire:click="refreshDashboard" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>Refresh Dashboard</span>
                    <span wire:loading>Refreshing...</span>
                </button>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label for="timeRange" class="block text-sm font-medium text-gray-700">Time Range</label>
                    <select wire:model="timeRange" id="timeRange" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        @foreach($timeRangeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="riskLevel" class="block text-sm font-medium text-gray-700">Risk Level</label>
                    <select wire:model="riskLevel" id="riskLevel" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        @foreach($riskLevelOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="alertType" class="block text-sm font-medium text-gray-700">Alert Type</label>
                    <select wire:model="alertType" id="alertType" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        @foreach($alertTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Security Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">Security Events</p>
                            <p class="text-2xl font-semibold text-red-900">{{ $securityData['security_events'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-800">Failed Logins</p>
                            <p class="text-2xl font-semibold text-yellow-900">{{ $securityData['failed_logins'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-purple-800">Suspicious Activities</p>
                            <p class="text-2xl font-semibold text-purple-900">{{ $securityData['suspicious_activities'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-800">Unique IPs</p>
                            <p class="text-2xl font-semibold text-blue-900">{{ $securityData['unique_ips'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Risk Assessment -->
            @if(isset($securityData['risk_assessment']))
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Risk Assessment</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-500">Average Risk Score</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $securityData['risk_assessment']['score'] ?? 0 }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-500">High Risk Alerts</p>
                        <p class="text-3xl font-bold text-red-600">{{ $securityData['risk_assessment']['high_risk_alerts'] ?? 0 }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-500">Risk Trend</p>
                        <div class="flex items-center justify-center">
                            <span class="text-2xl {{ $this->getTrendColor($securityData['risk_assessment']['trend'] ?? 'Stable') }}">
                                {{ $this->getTrendIcon($securityData['risk_assessment']['trend'] ?? 'Stable') }}
                            </span>
                            <span class="ml-2 text-lg font-semibold text-gray-900">
                                {{ $securityData['risk_assessment']['trend'] ?? 'Stable' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Security Alerts -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Recent Security Alerts</h4>
                    
                    @if(isset($securityData['recent_alerts']) && count($securityData['recent_alerts']) > 0)
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Risk Level</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($securityData['recent_alerts'] as $alert)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $alert['time'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $this->getRiskLevelColor($alert['risk_level']) }}">
                                                    {{ $alert['risk_level'] }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $alert['type'] }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ $alert['description'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $alert['ip_address'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <span class="text-green-600">{{ $alert['status'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No security alerts</h3>
                            <p class="mt-1 text-sm text-gray-500">No security alerts found for the selected time period.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>