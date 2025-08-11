<div>
  <h3 class="mt-5 text-base font-semibold text-gray-900">Quick Insights</h3>
  <dl class="mt-5 grid grid-cols-1 divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow md:grid-cols-3 md:divide-x md:divide-y-0">
    <div class="px-4 py-5 sm:p-6">
      <dt class="text-base font-bold text-gray-900">Incoming Packages (transit)</dt>
      <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
        <div class="flex items-baseline text-2xl font-semibold text-wax-flower-600">
          <div class="flex items-center mr-8">
            <!-- Airplane icon -->
            <x-air class="w-auto h-8 mx-auto mr-2 text-wax-flower-600" />
            {{ $inComingAir }}
          </div>
          <div class="flex items-center mr-8">
            <!-- Ship icon -->
            <x-sea class="w-auto h-8 mx-auto mr-2 text-wax-flower-600" />
            {{ $inComingSea }}
          </div>
          <div class="flex items-center">
            <!-- Delayed icon -->
            <x-timer class="w-auto h-8 mx-auto mr-2 text-wax-flower-600" />
            {{ $delayedPackages }}
          </div>
        </div>
        <!-- <div class="inline-flex items-baseline rounded-full bg-green-100 px-2.5 py-0.5 text-sm font-medium text-green-800 md:mt-2 lg:mt-0">
          <svg class="-ml-1 mr-0.5 size-5 shrink-0 self-center text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd" />
          </svg>
          <span class="sr-only"> Increased by </span>
          0%
        </div> -->
      </dd>
    </div>
    <div class="px-4 py-5 sm:p-6">
      <dt class="text-base font-bold text-gray-900">Packages Available for Pickup</dt>
      <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
        <div class="flex items-baseline text-2xl font-semibold text-wax-flower-600">
          <div class="flex items-center mr-8">
            <!-- Airplane icon -->
            <x-air class="w-auto h-8 mx-auto mr-2 text-wax-flower-600" />
            {{ $availableAir }}
          </div>
          <div class="flex items-center">
            <!-- Ship icon -->
            <x-sea class="w-auto h-8 mx-auto mr-2 text-wax-flower-600" />
            {{ $availableSea }}
          </div>
        </div>
      </dd>
    </div>
    <div class="px-4 py-5 sm:p-6">
      <dt class="text-base font-bold text-gray-900 mb-3">Financial Summary</dt>
      <dd class="space-y-3">
        <!-- Main Balance Cards -->
        <div class="grid grid-cols-1 gap-3">
          <!-- Account Balance Card -->
          <div class="bg-gradient-to-r {{ $accountBalance >= 0 ? 'from-green-50 to-green-100 border-green-200' : 'from-red-50 to-red-100 border-red-200' }} border rounded-lg p-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  @if($accountBalance >= 0)
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                  @else
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  @endif
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium {{ $accountBalance >= 0 ? 'text-green-800' : 'text-red-800' }}">
                    Account {{ $accountBalance < 0 ? 'Balance (Owed)' : 'Balance' }}
                  </p>
                  <p class="text-xs {{ $accountBalance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $accountBalance < 0 ? 'Amount you owe' : 'Available funds' }}
                  </p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-lg font-bold {{ $accountBalance >= 0 ? 'text-green-700' : 'text-red-700' }}">
                  ${{ number_format($accountBalance, 2) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Credit Balance Card (if exists) -->
          @if($creditBalance > 0)
          <div class="bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-blue-800">Credit Balance</p>
                  <p class="text-xs text-blue-600">From overpayments</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-lg font-bold text-blue-700">
                  ${{ number_format($creditBalance, 2) }}
                </p>
              </div>
            </div>
          </div>
          @endif

          <!-- Pending Charges Card (if exists) -->
          @if($pendingPackageCharges > 0)
          <div class="bg-gradient-to-r from-orange-50 to-orange-100 border border-orange-200 rounded-lg p-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-orange-800">Pending Charges</p>
                  <p class="text-xs text-orange-600">For ready packages</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-lg font-bold text-orange-700">
                  ${{ number_format($pendingPackageCharges, 2) }}
                </p>
              </div>
            </div>
          </div>
          @endif
        </div>

        <!-- Summary Section -->
        <div class="border-t pt-3 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700">Net Available:</span>
            <span class="text-base font-semibold {{ $totalAvailableBalance >= 0 ? 'text-green-600' : 'text-red-600' }}">
              ${{ number_format($totalAvailableBalance, 2) }}
            </span>
          </div>
          
          @if($totalAmountNeeded > 0)
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-2">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div>
                  <p class="text-sm font-bold text-yellow-800">Payment Needed</p>
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
      </dd>
    </div>
  </dl>
</div>