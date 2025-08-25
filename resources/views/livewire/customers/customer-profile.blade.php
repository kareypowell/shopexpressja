<div>
    <x-breadcrumb :items="$breadcrumbs" />
    
    <!-- Customer Profile Header -->
    <div class="bg-white shadow-sm rounded-xl border border-gray-100 mb-8">
        <div class="px-6 py-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="h-20 w-20 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-bold text-white">
                                {{ substr($customer->first_name, 0, 1) }}{{ substr($customer->last_name, 0, 1) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            {{ $customer->full_name }}
                        </h1>
                        <div class="flex items-center space-x-4 mt-2">
                            <p class="text-gray-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                                {{ $customer->email }}
                            </p>
                            @if($customer->profile)
                                <p class="text-gray-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    {{ $customer->profile->account_number }}
                                </p>
                            @endif
                        </div>
                        <div class="flex items-center space-x-3 mt-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $customer->deleted_at ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3"/>
                                </svg>
                                {{ $customer->deleted_at ? 'Inactive' : 'Active' }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                {{ ucfirst($customer->role->name ?? 'Customer') }}
                            </span>
                            <span class="text-sm text-gray-500">
                                Member since {{ $customer->created_at->format('M Y') }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                    @if(auth()->user()->hasRole(['superadmin', 'admin']))
                        <a href="{{ route('admin.customers.balance', $customer) }}" 
                           class="inline-flex items-center justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Manage Balance
                        </a>
                    @endif
                    <button wire:click="refreshData" 
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Refresh Data
                    </button>
                    @if($canExport)
                        <button wire:click="exportCustomerData" 
                                class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Personal Information -->
        <div class="bg-white shadow-sm rounded-xl border border-gray-100">
            <div class="px-6 py-5">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="ml-3 text-lg font-semibold text-gray-900">Personal Information</h3>
                </div>
                <dl class="space-y-4">
                    <div class="flex justify-between items-start">
                        <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                        <dd class="text-sm text-gray-900 font-medium">{{ $customer->full_name }}</dd>
                    </div>
                    <div class="flex justify-between items-start">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->email }}</dd>
                    </div>
                    @if($customer->profile)
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">Phone</dt>
                            <dd class="text-sm text-gray-900">{{ $customer->profile->telephone_number ?? 'Not provided' }}</dd>
                        </div>
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">Tax Number</dt>
                            <dd class="text-sm text-gray-900 font-mono">{{ $customer->profile->tax_number ?? 'Not provided' }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Address Information -->
        <div class="bg-white shadow-sm rounded-xl border border-gray-100">
            <div class="px-6 py-5">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 class="ml-3 text-lg font-semibold text-gray-900">Address Information</h3>
                </div>
                @if($customer->profile)
                    <dl class="space-y-4">
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">Street Address</dt>
                            <dd class="text-sm text-gray-900 text-right max-w-xs">{{ $customer->profile->street_address ?? 'Not provided' }}</dd>
                        </div>
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">City/Town</dt>
                            <dd class="text-sm text-gray-900">{{ $customer->profile->city_town ?? 'Not provided' }}</dd>
                        </div>
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">Parish</dt>
                            <dd class="text-sm text-gray-900">{{ $customer->profile->parish ?? 'Not provided' }}</dd>
                        </div>
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">Country</dt>
                            <dd class="text-sm text-gray-900">{{ $customer->profile->country ?? 'Not provided' }}</dd>
                        </div>
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">Pickup Location</dt>
                            <dd class="text-sm text-gray-900">{{ $customer->profile->office->name ?? 'Not assigned' }}</dd>
                        </div>
                    </dl>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">No address information available</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Account Information -->
        <div class="bg-white shadow-sm rounded-xl border border-gray-100">
            <div class="px-6 py-5">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="ml-3 text-lg font-semibold text-gray-900">Account Information</h3>
                </div>
                <dl class="space-y-4">
                    @if($customer->profile)
                        <div class="flex justify-between items-start">
                            <dt class="text-sm font-medium text-gray-500">Account Number</dt>
                            <dd class="text-sm text-gray-900 font-mono bg-gray-50 px-2 py-1 rounded">{{ $customer->profile->account_number }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between items-start">
                        <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between items-start">
                        <dt class="text-sm font-medium text-gray-500">Last Activity</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->updated_at->diffForHumans() }}</dd>
                    </div>
                    <div class="flex justify-between items-start">
                        <dt class="text-sm font-medium text-gray-500">Account Balance</dt>
                        <dd class="text-sm font-semibold {{ ($customer->account_balance ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ${{ number_format($customer->account_balance ?? 0, 2) }}
                        </dd>
                    </div>
                    <div class="flex justify-between items-start">
                        <dt class="text-sm font-medium text-gray-500">Credit Balance</dt>
                        <dd class="text-sm font-semibold text-blue-600">
                            ${{ number_format($customer->credit_balance ?? 0, 2) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Key Metrics Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Packages -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-600">Total Packages</p>
                    <p class="text-3xl font-bold text-blue-900">{{ $packageStats['total_packages'] ?? 0 }}</p>
                    @if(isset($packageStats['status_breakdown']['delivered']))
                        <p class="text-xs text-blue-600 mt-1">
                            {{ $packageStats['status_breakdown']['delivered'] }} delivered
                        </p>
                    @endif
                </div>
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Spent -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-600">Total Spent</p>
                    <p class="text-3xl font-bold text-green-900">${{ number_format($financialSummary['total_spent'] ?? 0, 0) }}</p>
                    @if(isset($financialSummary['average_per_package']))
                        <p class="text-xs text-green-600 mt-1">
                            ${{ number_format($financialSummary['average_per_package'], 2) }} avg per package
                        </p>
                    @endif
                </div>
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Frequency -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border border-purple-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-600">Shipping Frequency</p>
                    <p class="text-3xl font-bold text-purple-900">{{ number_format($packageStats['shipping_frequency'] ?? 0, 1) }}</p>
                    <p class="text-xs text-purple-600 mt-1">packages per month</p>
                </div>
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delivery Rate -->
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl border border-orange-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-orange-600">Delivery Rate</p>
                    <p class="text-3xl font-bold text-orange-900">{{ number_format($packageStats['delivery_rate'] ?? 0, 1) }}%</p>
                    @if(isset($packageStats['status_breakdown']['ready']))
                        <p class="text-xs text-orange-600 mt-1">
                            {{ $packageStats['status_breakdown']['ready'] }} ready for pickup
                        </p>
                    @endif
                </div>
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    @if($canViewFinancials && !empty($financialSummary))
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 mb-8">
            <div class="px-6 py-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="ml-3 text-xl font-semibold text-gray-900">Financial Breakdown</h3>
                    </div>
                    <button wire:click="refreshDataType('financial')" class="text-sm text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Freight Costs -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-600">Freight Costs</p>
                                <p class="text-2xl font-bold text-blue-900">${{ number_format($financialSummary['cost_breakdown']['freight'] ?? 0, 0) }}</p>
                                <p class="text-xs text-blue-600">{{ $financialSummary['cost_percentages']['freight'] ?? 0 }}% of total</p>
                            </div>
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Customs Duty -->
                    <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border border-red-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-red-600">Customs Duty</p>
                                <p class="text-2xl font-bold text-red-900">${{ number_format($financialSummary['cost_breakdown']['customs'] ?? 0, 0) }}</p>
                                <p class="text-xs text-red-600">{{ $financialSummary['cost_percentages']['customs'] ?? 0 }}% of total</p>
                            </div>
                            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Storage Fees -->
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-4 border border-yellow-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-yellow-600">Storage Fees</p>
                                <p class="text-2xl font-bold text-yellow-900">${{ number_format($financialSummary['cost_breakdown']['storage'] ?? 0, 0) }}</p>
                                <p class="text-xs text-yellow-600">{{ $financialSummary['cost_percentages']['storage'] ?? 0 }}% of total</p>
                            </div>
                            <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h8a2 2 0 002-2V8m-9 4h4"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Fees -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-600">Delivery Fees</p>
                                <p class="text-2xl font-bold text-green-900">${{ number_format($financialSummary['cost_breakdown']['delivery'] ?? 0, 0) }}</p>
                                <p class="text-xs text-green-600">{{ $financialSummary['cost_percentages']['delivery'] ?? 0 }}% of total</p>
                            </div>
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cost Range Information -->
                @if(isset($financialSummary['cost_range']))
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-500">Highest Package Cost</p>
                                <p class="text-lg font-bold text-gray-900">${{ number_format($financialSummary['cost_range']['highest_package'] ?? 0, 2) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-500">Average Package Cost</p>
                                <p class="text-lg font-bold text-gray-900">${{ number_format($financialSummary['average_per_package'] ?? 0, 2) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-500">Lowest Package Cost</p>
                                <p class="text-lg font-bold text-gray-900">${{ number_format($financialSummary['cost_range']['lowest_package'] ?? 0, 2) }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Package Status Breakdown -->
    @if(!empty($packageStats['status_breakdown']))
    <div class="bg-white shadow-sm rounded-xl border border-gray-100 mb-8">
        <div class="px-6 py-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="ml-3 text-xl font-semibold text-gray-900">Package Status Overview</h3>
                </div>
                <button wire:click="refreshDataType('packages')" class="text-sm text-gray-500 hover:text-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Delivered -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-green-600">Delivered</p>
                            <p class="text-3xl font-bold text-green-900">{{ $packageStats['status_breakdown']['delivered'] ?? 0 }}</p>
                        </div>
                        <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- In Transit -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-600">In Transit</p>
                            <p class="text-3xl font-bold text-blue-900">{{ $packageStats['status_breakdown']['in_transit'] ?? 0 }}</p>
                        </div>
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Ready for Pickup -->
                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-6 border border-yellow-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-yellow-600">Ready</p>
                            <p class="text-3xl font-bold text-yellow-900">{{ $packageStats['status_breakdown']['ready_for_pickup'] ?? 0 }}</p>
                        </div>
                        <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Delayed -->
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-6 border border-red-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-red-600">Delayed</p>
                            <p class="text-3xl font-bold text-red-900">{{ $packageStats['status_breakdown']['delayed'] ?? 0 }}</p>
                        </div>
                        <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Status Details -->
            @if(isset($packageStats['status_breakdown']['processing']) || isset($packageStats['status_breakdown']['pending']))
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @if(isset($packageStats['status_breakdown']['processing']))
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-500">Processing</p>
                                <p class="text-lg font-bold text-gray-900">{{ $packageStats['status_breakdown']['processing'] }}</p>
                            </div>
                        @endif
                        @if(isset($packageStats['status_breakdown']['pending']))
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="text-lg font-bold text-gray-900">{{ $packageStats['status_breakdown']['pending'] }}</p>
                            </div>
                        @endif
                        @if(isset($packageStats['status_breakdown']['customs']))
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-500">Customs</p>
                                <p class="text-lg font-bold text-gray-900">{{ $packageStats['status_breakdown']['customs'] }}</p>
                            </div>
                        @endif
                        @if(isset($packageStats['status_breakdown']['shipped']))
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-500">Shipped</p>
                                <p class="text-lg font-bold text-gray-900">{{ $packageStats['status_breakdown']['shipped'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Enhanced Package History -->
    {{--
    @if($canViewPackages)
        @livewire('customers.package-history', ['customer' => $customer])
    @endif
    --}}

    <!-- Loading Overlay -->
    <div wire:loading.flex class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 items-center justify-center">
        <div class="bg-white rounded-lg p-6 shadow-xl">
            <div class="flex items-center space-x-3">
                <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-900 font-medium">Refreshing customer data...</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.addEventListener('show-alert', event => {
        // Create a more sophisticated alert system
        const alertType = event.detail.type || 'info';
        const message = event.detail.message;
        
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `fixed top-4 right-4 z-50 max-w-sm w-full shadow-lg rounded-lg p-4 ${getAlertClasses(alertType)}`;
        alertDiv.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    ${getAlertIcon(alertType)}
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    });
    
    function getAlertClasses(type) {
        switch(type) {
            case 'success':
                return 'bg-green-50 border border-green-200 text-green-800';
            case 'error':
                return 'bg-red-50 border border-red-200 text-red-800';
            case 'warning':
                return 'bg-yellow-50 border border-yellow-200 text-yellow-800';
            default:
                return 'bg-blue-50 border border-blue-200 text-blue-800';
        }
    }
    
    function getAlertIcon(type) {
        switch(type) {
            case 'success':
                return '<svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
            case 'error':
                return '<svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
            case 'warning':
                return '<svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
            default:
                return '<svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
        }
    }
</script>
@endpush