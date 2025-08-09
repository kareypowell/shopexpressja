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
        <!-- <div class="inline-flex items-baseline rounded-full bg-green-100 px-2.5 py-0.5 text-sm font-medium text-green-800 md:mt-2 lg:mt-0">
          <svg class="-ml-1 mr-0.5 size-5 shrink-0 self-center text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd" />
          </svg>
          <span class="sr-only"> Increased by </span>
          0.0%
        </div> -->
      </dd>
    </div>
    <div class="px-4 py-5 sm:p-6">
      <dt class="text-base font-bold text-gray-900">Account Balance</dt>
      <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
        <div class="space-y-2">
          <!-- Account Balance -->
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">
              Account {{ $accountBalance < 0 ? '(Owed)' : '' }}:
            </span>
            <span class="text-lg font-semibold {{ $accountBalance >= 0 ? 'text-green-600' : 'text-red-600' }}">
              ${{ number_format($accountBalance, 2) }}
            </span>
          </div>
          
          <!-- Credit Balance -->
          @if($creditBalance > 0)
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Credit (Overpayments):</span>
            <span class="text-lg font-semibold text-blue-600">
              ${{ number_format($creditBalance, 2) }}
            </span>
          </div>
          @endif
          
          <!-- Pending Charges for Ready Packages -->
          @if($pendingPackageCharges > 0)
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Pending Charges: </span>
            <span class="text-lg font-semibold text-orange-600">
              ${{ number_format($pendingPackageCharges, 2) }}
            </span>
          </div>
          @endif
          
          <!-- Total Available -->
          <div class="flex items-center justify-between border-t pt-2">
            <span class="text-sm font-medium text-gray-900">Available Balance: </span>
            <span class="text-lg font-semibold {{ $totalAvailableBalance >= 0 ? 'text-green-600' : 'text-red-600' }}">
              ${{ number_format($totalAvailableBalance, 2) }}
            </span>
          </div>
          
          <!-- Total Amount Needed to Collect All Packages -->
          @if($totalAmountNeeded > 0)
          <div class="flex items-center justify-between border-t pt-2 bg-yellow-50 -mx-2 px-2 py-2 rounded">
            <span class="text-sm font-bold text-gray-900">Amount Needed to Collect All: </span>
            <span class="text-xl font-bold text-red-600">
              ${{ number_format($totalAmountNeeded, 2) }}
            </span>
          </div>
          @endif
        </div>
      </dd>
    </div>
  </dl>
</div>