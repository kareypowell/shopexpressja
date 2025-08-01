<div>
    <!-- Customer Profile Header -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-16 w-16 rounded-full bg-gray-300 flex items-center justify-center">
                            <span class="text-xl font-medium text-gray-700">
                                {{ substr($customer->first_name, 0, 1) }}{{ substr($customer->last_name, 0, 1) }}
                            </span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ $customer->full_name }}
                        </h1>
                        <p class="text-sm text-gray-500">
                            {{ $customer->email }}
                        </p>
                        @if($customer->profile)
                            <p class="text-sm text-gray-500">
                                Account: {{ $customer->profile->account_number }}
                            </p>
                        @endif
                    </div>
                </div>
                <div class="flex space-x-3">
                    @if($canExport)
                        <button wire:click="exportCustomerData" 
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export Data
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Information Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Personal Information -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Personal Information</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->email }}</dd>
                    </div>
                    @if($customer->profile)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Phone</dt>
                            <dd class="text-sm text-gray-900">{{ $customer->profile->telephone_number ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tax Number</dt>
                            <dd class="text-sm text-gray-900">{{ $customer->profile->tax_number ?? 'N/A' }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Role</dt>
                        <dd class="text-sm text-gray-900">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ ucfirst($customer->role->name ?? 'N/A') }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Address Information -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Address Information</h3>
                @if($customer->profile)
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Street Address</dt>
                            <dd class="text-sm text-gray-900">{{ $customer->profile->street_address ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">City/Town</dt>
                            <dd class="text-sm text-gray-900">{{ $customer->profile->city_town ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Parish</dt>
                            <dd class="text-sm text-gray-900">{{ $customer->profile->parish ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Country</dt>
                            <dd class="text-sm text-gray-900">{{ $customer->profile->country ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Pickup Location</dt>
                            <dd class="text-sm text-gray-900">{{ $customer->profile->office->name ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-sm text-gray-500">No address information available.</p>
                @endif
            </div>
        </div>

        <!-- Account Information -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Account Information</h3>
                <dl class="space-y-3">
                    @if($customer->profile)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Account Number</dt>
                            <dd class="text-sm text-gray-900 font-mono">{{ $customer->profile->account_number }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Login</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->updated_at->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="text-sm text-gray-900">
                            @if($customer->deleted_at)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Inactive
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total Packages -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Packages</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $packageStats['total_packages'] ?? 0 }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Spent -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Spent</dt>
                            <dd class="text-lg font-medium text-gray-900">${{ number_format($financialSummary['total_spent'] ?? 0, 2) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Package Value -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg Package Value</dt>
                            <dd class="text-lg font-medium text-gray-900">${{ number_format($financialSummary['averages']['per_package'] ?? 0, 2) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Frequency -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Packages/Month</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format($packageStats['shipping_frequency'] ?? 0, 1) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    @if($canViewFinancials)
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Financial Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">${{ number_format($financialSummary['breakdown']['freight'] ?? 0, 2) }}</div>
                        <div class="text-sm text-gray-500">Freight Costs</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">${{ number_format($financialSummary['breakdown']['customs'] ?? 0, 2) }}</div>
                        <div class="text-sm text-gray-500">Customs Duty</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600">${{ number_format($financialSummary['breakdown']['storage'] ?? 0, 2) }}</div>
                        <div class="text-sm text-gray-500">Storage Fees</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">${{ number_format($financialSummary['breakdown']['delivery'] ?? 0, 2) }}</div>
                        <div class="text-sm text-gray-500">Delivery Fees</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Package Status Breakdown -->
    @if(!empty($packageStats['status_breakdown']))
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Package Status Overview</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ $packageStats['status_breakdown']['delivered'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Delivered</div>
                </div>
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ $packageStats['status_breakdown']['in_transit'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">In Transit</div>
                </div>
                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600">{{ $packageStats['status_breakdown']['ready_for_pickup'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Ready for Pickup</div>
                </div>
                <div class="text-center p-4 bg-red-50 rounded-lg">
                    <div class="text-2xl font-bold text-red-600">{{ $packageStats['status_breakdown']['delayed'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Delayed</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Enhanced Package History -->
    @if($canViewPackages)
        @livewire('customers.package-history', ['customer' => $customer])
    @endif
</div>

@push('scripts')
<script>
    window.addEventListener('show-alert', event => {
        alert(event.detail.message);
    });
</script>
@endpush