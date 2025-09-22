@extends('reports.pdf.layout')

@section('content')
    {{-- Customer Summary --}}
    @if(isset($customer_info))
        <div class="section">
            <h2>Customer Information</h2>
            <table>
                <tbody>
                    <tr>
                        <td class="font-bold" style="width: 150px;">Customer Name:</td>
                        <td>{{ $customer_info['name'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">Account Number:</td>
                        <td>{{ $customer_info['account_number'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">Email:</td>
                        <td>{{ $customer_info['email'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">Member Since:</td>
                        <td>{{ isset($customer_info['created_at']) ? \Carbon\Carbon::parse($customer_info['created_at'])->format('F j, Y') : 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    {{-- Account Summary Cards --}}
    <div class="section">
        <h2>Account Summary</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <h4>Total Packages</h4>
                <div class="value text-blue">{{ number_format($summary['total_packages'] ?? 0) }}</div>
            </div>
            <div class="summary-card">
                <h4>Account Balance</h4>
                <div class="value text-{{ ($summary['account_balance'] ?? 0) >= 0 ? 'green' : 'red' }}">
                    ${{ number_format($summary['account_balance'] ?? 0, 2) }}
                </div>
            </div>
            <div class="summary-card">
                <h4>Total Spent</h4>
                <div class="value text-blue">${{ number_format($summary['total_spent'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-card">
                <h4>Outstanding Balance</h4>
                <div class="value text-red">${{ number_format($summary['outstanding_balance'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    @if(isset($charts) && !empty($charts))
        <div class="section">
            <h2>Customer Analytics</h2>
            
            @if(isset($charts['spending_trends']))
                <div class="chart-container">
                    <div class="chart-title">Monthly Spending Trends</div>
                    <img src="{{ $charts['spending_trends'] }}" alt="Spending Trends Chart">
                </div>
            @endif

            @if(isset($charts['package_types']))
                <div class="chart-container">
                    <div class="chart-title">Package Distribution by Type</div>
                    <img src="{{ $charts['package_types'] }}" alt="Package Types Chart">
                </div>
            @endif
        </div>
    @endif

    {{-- Recent Packages --}}
    @if(isset($package_data) && !empty($package_data))
        <div class="section">
            <h2>Recent Packages</h2>
            <table>
                <thead>
                    <tr>
                        <th>Package #</th>
                        <th>Manifest</th>
                        <th>Received</th>
                        <th>Status</th>
                        <th class="text-right">Weight</th>
                        <th class="text-right">Charges</th>
                        <th class="text-right">Paid</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($package_data as $package)
                        <tr>
                            <td class="font-bold">{{ $package['package_number'] ?? 'N/A' }}</td>
                            <td>{{ $package['manifest_number'] ?? 'N/A' }}</td>
                            <td>{{ isset($package['created_at']) ? \Carbon\Carbon::parse($package['created_at'])->format('M j, Y') : 'N/A' }}</td>
                            <td>
                                <span class="text-{{ $package['status'] === 'delivered' ? 'green' : 'blue' }}">
                                    {{ ucfirst($package['status'] ?? 'N/A') }}
                                </span>
                            </td>
                            <td class="text-right">{{ number_format($package['weight'] ?? 0, 1) }} lbs</td>
                            <td class="text-right">${{ number_format($package['total_charges'] ?? 0, 2) }}</td>
                            <td class="text-right text-green">${{ number_format($package['amount_paid'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray font-bold">
                        <td colspan="5">TOTALS</td>
                        <td class="text-right">${{ number_format(collect($package_data)->sum('total_charges'), 2) }}</td>
                        <td class="text-right text-green">${{ number_format(collect($package_data)->sum('amount_paid'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    {{-- Transaction History --}}
    @if(isset($transaction_data) && !empty($transaction_data))
        <div class="section page-break">
            <h2>Transaction History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaction_data as $transaction)
                        <tr>
                            <td>{{ isset($transaction['created_at']) ? \Carbon\Carbon::parse($transaction['created_at'])->format('M j, Y') : 'N/A' }}</td>
                            <td>
                                <span class="text-{{ $transaction['type'] === 'payment' ? 'green' : 'red' }}">
                                    {{ ucfirst($transaction['type'] ?? 'N/A') }}
                                </span>
                            </td>
                            <td>{{ $transaction['description'] ?? 'N/A' }}</td>
                            <td class="text-right text-{{ $transaction['type'] === 'payment' ? 'green' : 'red' }}">
                                {{ $transaction['type'] === 'payment' ? '+' : '-' }}${{ number_format(abs($transaction['amount'] ?? 0), 2) }}
                            </td>
                            <td class="text-right">${{ number_format($transaction['running_balance'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Consolidated Packages --}}
    @if(isset($consolidation_data) && !empty($consolidation_data))
        <div class="section">
            <h2>Consolidation History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Consolidation #</th>
                        <th>Date</th>
                        <th class="text-right">Packages</th>
                        <th class="text-right">Weight</th>
                        <th class="text-right">Savings</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($consolidation_data as $consolidation)
                        <tr>
                            <td class="font-bold">{{ $consolidation['consolidation_number'] ?? 'N/A' }}</td>
                            <td>{{ isset($consolidation['created_at']) ? \Carbon\Carbon::parse($consolidation['created_at'])->format('M j, Y') : 'N/A' }}</td>
                            <td class="text-right">{{ number_format($consolidation['package_count'] ?? 0) }}</td>
                            <td class="text-right">{{ number_format($consolidation['total_weight'] ?? 0, 1) }} lbs</td>
                            <td class="text-right text-green">${{ number_format($consolidation['savings'] ?? 0, 2) }}</td>
                            <td>
                                <span class="text-{{ $consolidation['status'] === 'delivered' ? 'green' : 'blue' }}">
                                    {{ ucfirst($consolidation['status'] ?? 'N/A') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray font-bold">
                        <td colspan="2">TOTALS</td>
                        <td class="text-right">{{ number_format(collect($consolidation_data)->sum('package_count')) }}</td>
                        <td class="text-right">{{ number_format(collect($consolidation_data)->sum('total_weight'), 1) }} lbs</td>
                        <td class="text-right text-green">${{ number_format(collect($consolidation_data)->sum('savings'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    {{-- Filter Information --}}
    @if(isset($filters) && !empty($filters))
        <div class="section">
            <h2>Report Filters</h2>
            <table>
                <tbody>
                    @if(isset($filters['date_range']))
                        <tr>
                            <td class="font-bold" style="width: 150px;">Date Range:</td>
                            <td>{{ $filters['date_range'] }}</td>
                        </tr>
                    @endif
                    @if(isset($filters['customer_id']))
                        <tr>
                            <td class="font-bold">Customer ID:</td>
                            <td>{{ $filters['customer_id'] }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif
@endsection