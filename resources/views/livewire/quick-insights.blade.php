<div>
  <div class="mt-5">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900">Quick Insights</h3>
      @if($delayedPackages > 0)
        <div class="flex items-center bg-red-50 border border-red-200 rounded-full px-3 py-1">
          <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse mr-2"></div>
          <span class="text-sm font-medium text-red-700">{{ $delayedPackages }} delayed</span>
        </div>
      @endif
    </div>

    <!-- Stats Overview Bar -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <!-- In Transit -->
        <div class="text-center">
          <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg mx-auto mb-3">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
            </svg>
          </div>
          <div class="text-2xl font-bold text-gray-900">{{ $inComingAir + $inComingSea }}</div>
          <div class="text-sm text-gray-500">In Transit</div>
        </div>

        <!-- Ready -->
        <div class="text-center">
          <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mx-auto mb-3">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="text-2xl font-bold text-gray-900">{{ $availableAir + $availableSea }}</div>
          <div class="text-sm text-gray-500">Ready</div>
        </div>

        <!-- Delayed -->
        <div class="text-center">
          <div class="flex items-center justify-center w-12 h-12 {{ $delayedPackages > 0 ? 'bg-red-100' : 'bg-gray-100' }} rounded-lg mx-auto mb-3">
            <x-timer class="w-6 h-6 {{ $delayedPackages > 0 ? 'text-red-600' : 'text-gray-400' }}" />
          </div>
          <div class="text-2xl font-bold {{ $delayedPackages > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $delayedPackages }}</div>
          <div class="text-sm text-gray-500">Delayed</div>
        </div>

        <!-- Balance -->
        <div class="text-center">
          <div class="flex items-center justify-center w-12 h-12 {{ $accountBalance >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-lg mx-auto mb-3">
            <svg class="w-6 h-6 {{ $accountBalance >= 0 ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
          </div>
          <div class="text-2xl font-bold {{ $accountBalance >= 0 ? 'text-green-600' : 'text-red-600' }}">${{ number_format(abs($accountBalance), 0) }}</div>
          <div class="text-sm text-gray-500">Balance</div>
        </div>
      </div>
    </div>

    <!-- Detailed Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Package Status Section -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-4 border-b border-gray-100">
          <h4 class="text-lg font-semibold text-gray-900">Package Status</h4>
          <p class="text-sm text-gray-600 mt-1">Track your shipments</p>
        </div>
        
        <div class="p-6 space-y-4">
          <!-- In Transit Section -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h5 class="text-sm font-medium text-gray-700 flex items-center">
                <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse mr-2"></div>
                In Transit
              </h5>
              <span class="text-sm text-gray-500">{{ $inComingAir + $inComingSea }} {{ Str::plural('package', $inComingAir + $inComingSea) }}</span>
            </div>
            
            <div class="grid grid-cols-2 gap-3">
              <div class="bg-sky-50 border border-sky-200 rounded-lg p-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <x-air class="w-5 h-5 text-sky-600 mr-2" />
                    <span class="text-sm font-medium text-sky-800">Air</span>
                  </div>
                  <span class="text-lg font-bold text-sky-700">{{ $inComingAir }}</span>
                </div>
                <div class="text-xs text-sky-600 mt-1">2-3 days</div>
              </div>
              
              <div class="bg-teal-50 border border-teal-200 rounded-lg p-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <x-sea class="w-5 h-5 text-teal-600 mr-2" />
                    <span class="text-sm font-medium text-teal-800">Sea</span>
                  </div>
                  <span class="text-lg font-bold text-teal-700">{{ $inComingSea }}</span>
                </div>
                <div class="text-xs text-teal-600 mt-1">1-2 weeks</div>
              </div>
            </div>
          </div>

          <!-- Ready Section -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h5 class="text-sm font-medium text-gray-700 flex items-center">
                <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                Ready for Pickup
              </h5>
              <span class="text-sm text-gray-500">{{ $availableAir + $availableSea }} {{ Str::plural('package', $availableAir + $availableSea) }}</span>
            </div>
            
            @if(($availableAir + $availableSea) > 0)
            <div class="grid grid-cols-2 gap-3">
              <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <x-air class="w-5 h-5 text-emerald-600 mr-2" />
                    <span class="text-sm font-medium text-emerald-800">Air</span>
                  </div>
                  <span class="text-lg font-bold text-emerald-700">{{ $availableAir }}</span>
                </div>
              </div>
              
              <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <x-sea class="w-5 h-5 text-green-600 mr-2" />
                    <span class="text-sm font-medium text-green-800">Sea</span>
                  </div>
                  <span class="text-lg font-bold text-green-700">{{ $availableSea }}</span>
                </div>
              </div>
            </div>
            
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  </svg>
                  <span class="text-sm font-medium text-indigo-800">Visit our office to collect</span>
                </div>
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
            @else
            <div class="text-center py-4">
              <div class="text-gray-400 text-sm">No packages ready for pickup</div>
            </div>
            @endif
          </div>

          <!-- Delay Alert -->
          @if($delayedPackages > 0)
          <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
              </div>
              <div class="ml-3">
                <h6 class="text-sm font-medium text-red-800">Delayed Packages Alert</h6>
                <p class="text-sm text-red-700 mt-1">
                  {{ $delayedPackages }} {{ $delayedPackages === 1 ? 'package is' : 'packages are' }} experiencing delays. 
                  We're working to resolve this and will update you soon.
                </p>
                <div class="mt-2">
                  <button class="text-xs font-medium text-red-800 hover:text-red-900 underline">
                    View details →
                  </button>
                </div>
              </div>
            </div>
          </div>
          @endif
        </div>
      </div>

      <!-- Financial Overview Section -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-4 border-b border-gray-100">
          <h4 class="text-lg font-semibold text-gray-900">Financial Overview</h4>
          <p class="text-sm text-gray-600 mt-1">Account balance and charges</p>
        </div>
        
        <div class="p-6 space-y-4">
          <!-- Account Balance -->
          <div class="bg-gradient-to-r {{ $accountBalance >= 0 ? 'from-green-50 to-green-100 border-green-200' : 'from-red-50 to-red-100 border-red-200' }} border rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  @if($accountBalance >= 0)
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                  @else
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  @endif
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium {{ $accountBalance >= 0 ? 'text-green-800' : 'text-red-800' }}">
                    Account Balance
                  </p>
                  <p class="text-xs {{ $accountBalance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $accountBalance < 0 ? 'Amount owed' : 'Available funds' }}
                  </p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-2xl font-bold {{ $accountBalance >= 0 ? 'text-green-700' : 'text-red-700' }}">
                  ${{ number_format(abs($accountBalance), 2) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Additional Financial Info -->
          <div class="space-y-3">
            @if($creditBalance > 0)
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <div class="flex items-center">
                <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span class="text-sm text-gray-700">Credit Balance</span>
              </div>
              <span class="text-sm font-semibold text-blue-600">${{ number_format($creditBalance, 2) }}</span>
            </div>
            @endif

            @if($pendingPackageCharges > 0)
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <div class="flex items-center">
                <svg class="w-4 h-4 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <span class="text-sm text-gray-700">Pending Charges</span>
              </div>
              <span class="text-sm font-semibold text-orange-600">${{ number_format($pendingPackageCharges, 2) }}</span>
            </div>
            @endif

            <div class="flex items-center justify-between py-2 font-medium">
              <span class="text-sm text-gray-900">Net Available</span>
              <span class="text-lg font-bold {{ $totalAvailableBalance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                ${{ number_format($totalAvailableBalance, 2) }}
              </span>
            </div>
          </div>

          @if($totalAmountNeeded > 0)
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div>
                  <p class="text-sm font-bold text-yellow-800">Payment Required</p>
                  <p class="text-xs text-yellow-600">To collect all packages</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-xl font-bold text-yellow-800">
                  ${{ number_format($totalAmountNeeded, 2) }}
                </p>
              </div>
            </div>
          </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>