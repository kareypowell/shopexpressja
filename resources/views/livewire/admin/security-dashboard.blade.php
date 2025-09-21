<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Security Dashboard</h2>
            <p class="text-gray-600">Monitor security events and system anomalies</p>
        </div>
        <div class="flex space-x-3">
            <button wire:click="runAnomalyDetection" 
                    class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                Run Anomaly Detection
            </button>
            <button wire:click="refreshDashboard" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                Refresh Dashboard
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Time Range</label>
                <select wire:model="timeRange" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="1">Last Hour</option>
                    <option value="6">Last 6 Hours</option>
                    <option value="24">Last 24 Hours</option>
                    <option value="168">Last Week</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Risk Level</label>
                <select wire:model="riskLevelFilter" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">All Levels</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alert Type</label>
                <select wire:model="alertTypeFilter" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">All Types</option>
                    <option value="security_alert_generated">Security Alerts</option>
                    <option value="suspicious_activity_detected">Suspicious Activity</option>
                    <option value="failed_authentication">Failed Authentication</option>
                    <option value="unauthorized_access">Unauthorized Access</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Security Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Security Events</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $securitySummary['total_events'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Failed Logins</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $alertStats['failed_logins'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Suspicious Activities</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $alertStats['suspicious_activities'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Unique IPs</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $securitySummary['unique_ips'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Metrics -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Risk Assessment</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-900">{{ $riskMetrics['average_risk_score'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Average Risk Score</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-red-600">{{ $riskMetrics['high_risk_alerts'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">High Risk Alerts</div>
            </div>
            <div class="text-center">
                <div class="flex items-center justify-center">
                    @if(($riskMetrics['trend'] ?? 'stable') === 'increasing')
                        <svg class="w-6 h-6 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-red-600 font-medium">Increasing</span>
                    @elseif(($riskMetrics['trend'] ?? 'stable') === 'decreasing')
                        <svg class="w-6 h-6 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-green-600 font-medium">Decreasing</span>
                    @else
                        <svg class="w-6 h-6 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-600 font-medium">Stable</span>
                    @endif
                </div>
                <div class="text-sm text-gray-500">Risk Trend</div>
            </div>
        </div>
    </div>

    <!-- System Anomalies -->
    @if(!empty($systemAnomalies))
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-900 mb-4">System Anomalies</h3>
        <div class="space-y-3">
            @foreach($systemAnomalies as $anomaly)
            <div class="flex items-center justify-between p-3 border rounded-lg
                @if($anomaly['severity'] === 'critical') border-red-200 bg-red-50
                @elseif($anomaly['severity'] === 'high') border-orange-200 bg-orange-50
                @elseif($anomaly['severity'] === 'medium') border-yellow-200 bg-yellow-50
                @else border-gray-200 bg-gray-50 @endif">
                <div class="flex-1">
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($anomaly['severity'] === 'critical') bg-red-100 text-red-800
                            @elseif($anomaly['severity'] === 'high') bg-orange-100 text-orange-800
                            @elseif($anomaly['severity'] === 'medium') bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst($anomaly['severity']) }}
                        </span>
                        <span class="ml-3 text-sm font-medium text-gray-900">{{ $anomaly['type'] }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">{{ $anomaly['description'] }}</p>
                </div>
                <div class="text-sm text-gray-500">
                    Count: {{ $anomaly['count'] ?? 'N/A' }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Recent Security Alerts -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Recent Security Alerts</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Risk Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentAlerts as $alert)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $alert->created_at->format('M j, Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $riskLevel = $alert->additional_data['risk_level'] ?? 'unknown';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($riskLevel === 'critical') bg-red-100 text-red-800
                                @elseif($riskLevel === 'high') bg-orange-100 text-orange-800
                                @elseif($riskLevel === 'medium') bg-yellow-100 text-yellow-800
                                @elseif($riskLevel === 'low') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($riskLevel) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ str_replace('_', ' ', ucfirst($alert->action)) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            @if(isset($alert->additional_data['alerts']) && is_array($alert->additional_data['alerts']))
                                {{ implode(', ', $alert->additional_data['alerts']) }}
                            @else
                                {{ $alert->additional_data['description'] ?? 'Security event detected' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $alert->ip_address ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if(!isset($alert->additional_data['acknowledged']))
                            <button wire:click="acknowledgeAlert({{ $alert->id }})" 
                                    class="text-blue-600 hover:text-blue-900">
                                Acknowledge
                            </button>
                            @else
                            <span class="text-green-600">Acknowledged</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No security alerts found for the selected time range.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($recentAlerts->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $recentAlerts->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Auto-refresh dashboard every 30 seconds
    setInterval(function() {
        @this.call('$refresh');
    }, {{ $refreshInterval * 1000 }});

    // Listen for notifications
    window.addEventListener('show-notification', event => {
        const { type, message } = event.detail;
        
        // You can integrate with your existing notification system here
        // For now, we'll use a simple alert
        if (type === 'error') {
            alert('Error: ' + message);
        } else if (type === 'success') {
            alert('Success: ' + message);
        } else if (type === 'warning') {
            alert('Warning: ' + message);
        }
    });
</script>
@endpush