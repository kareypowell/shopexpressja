<!-- Static sidebar for desktop -->
<div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
  <!-- Sidebar component, swap this element with another sidebar if you like -->
  <div class="flex-1 flex flex-col min-h-0 bg-gray-800">
    <div class="flex items-center h-16 flex-shrink-0 px-4 bg-gray-900">
      <x-logo-white class="h-8 w-auto" />
    </div>
    <div class="flex-1 flex flex-col overflow-y-auto">
      <nav class="flex-1 px-2 py-4 space-y-2">
        <!-- Dashboard - Always visible -->
        <a href="/" class="{{ \Route::is('home') == true ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-base font-medium rounded-md">
          <svg class="text-gray-300 mr-3 flex-shrink-0 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Dashboard
        </a>

        <!-- 📊 OPERATIONS -->
        <div x-data="{ open: {{ (\Route::is('package-distribution') || \Route::is('admin.manifests.*')) ? 'true' : 'false' }} }" class="space-y-1">
          <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white group w-full flex items-center px-2 py-2 text-left text-base font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span class="mr-3 text-lg">📊</span>
            <span class="flex-1">Operations</span>
            <svg class="ml-3 h-5 w-5 transform transition-colors duration-150 ease-in-out group-hover:text-gray-400" :class="{'rotate-90 text-gray-400': open, 'text-gray-300': !open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
          </button>
          <div x-show="open" x-transition class="space-y-1 pl-4">
            <a href="{{ route('package-distribution') }}" class="{{ \Route::is('package-distribution') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg xmlns="http://www.w3.org/2000/svg" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
              Package Distribution
            </a>
            
            <!-- Manifests Submenu -->
            <div x-data="{ manifestOpen: {{ \Route::is('admin.manifests.*') ? 'true' : 'false' }} }" class="space-y-1">
              <button @click="manifestOpen = !manifestOpen" class="{{ \Route::is('admin.manifests.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group w-full flex items-center px-2 py-2 text-sm font-medium rounded-md text-left">
                <svg class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="flex-1">Manifests</span>
                <svg class="ml-2 h-4 w-4 transform transition-colors" :class="{'rotate-90': manifestOpen}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
              <div x-show="manifestOpen" x-transition class="space-y-1 pl-6">
                <a href="{{ route('admin.manifests.index') }}" class="{{ \Route::is('admin.manifests.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                  All Manifests
                </a>
                <a href="{{ route('admin.manifests.create') }}" class="{{ \Route::is('admin.manifests.create') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                  Create Manifest
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- 👥 CUSTOMER MANAGEMENT -->
        <div x-data="{ open: {{ (\Route::is('admin.customers.*') || \Route::is('customers') || \Route::is('admin.broadcast-messages.*') || \Route::is('view-pre-alerts')) ? 'true' : 'false' }} }" class="space-y-1">
          <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white group w-full flex items-center px-2 py-2 text-left text-base font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span class="mr-3 text-lg">👥</span>
            <span class="flex-1">Customer Management</span>
            <svg class="ml-3 h-5 w-5 transform transition-colors duration-150 ease-in-out group-hover:text-gray-400" :class="{'rotate-90 text-gray-400': open, 'text-gray-300': !open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
          </button>
          <div x-show="open" x-transition class="space-y-1 pl-4">
            <!-- Customers Submenu -->
            <div x-data="{ customerOpen: {{ (\Route::is('admin.customers.*') || \Route::is('customers')) ? 'true' : 'false' }} }" class="space-y-1">
              <button @click="customerOpen = !customerOpen" class="{{ (\Route::is('admin.customers.*') || \Route::is('customers')) ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group w-full flex items-center px-2 py-2 text-sm font-medium rounded-md text-left">
                <svg class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="flex-1">Customers</span>
                <svg class="ml-2 h-4 w-4 transform transition-colors" :class="{'rotate-90': customerOpen}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
              <div x-show="customerOpen" x-transition class="space-y-1 pl-6">
                <a href="{{ route('admin.customers.index') }}" class="{{ \Route::is('admin.customers.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                  All Customers
                </a>
                <a href="{{ route('admin.customers.create') }}" class="{{ \Route::is('admin.customers.create') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                  Create Customer
                </a>
              </div>
            </div>

            <a href="{{ route('admin.broadcast-messages.index') }}" class="{{ \Route::is('admin.broadcast-messages.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
              </svg>
              Broadcast Messages
            </a>

            <a href="/admin/pre-alerts" class="{{ \Route::is('view-pre-alerts') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg xmlns="http://www.w3.org/2000/svg" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5" />
              </svg>
              Pre-Alerts
            </a>
          </div>
        </div>

        <!-- 💰 FINANCIAL -->
        <div x-data="{ open: {{ (\Route::is('view-purchase-requests') || \Route::is('transactions') || \Route::is('customer.transactions') || \Route::is('view-rates')) ? 'true' : 'false' }} }" class="space-y-1">
          <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white group w-full flex items-center px-2 py-2 text-left text-base font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span class="mr-3 text-lg">💰</span>
            <span class="flex-1">Financial</span>
            <svg class="ml-3 h-5 w-5 transform transition-colors duration-150 ease-in-out group-hover:text-gray-400" :class="{'rotate-90 text-gray-400': open, 'text-gray-300': !open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
          </button>
          <div x-show="open" x-transition class="space-y-1 pl-4">
            <a href="/admin/purchase-requests" class="{{ \Route::is('view-purchase-requests') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg xmlns="http://www.w3.org/2000/svg" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M8 11v6h8v-6M8 11H6a2 2 0 00-2 2v6a2 2 0 002 2h12a2 2 0 002-2v-6a2 2 0 00-2-2h-2" />
              </svg>
              Purchase Requests
            </a>

            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
            <a href="/admin/transactions" class="{{ \Route::is('transactions') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
              </svg>
              Transactions
            </a>
            @endif

            @if(auth()->user()->isCustomer())
            <a href="{{ route('customer.transactions') }}" class="{{ \Route::is('customer.transactions') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
              </svg>
              Transaction History
            </a>
            @endif

            <a href="/admin/rates" class="{{ \Route::is('view-rates') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.99 14.993 6-6m6 3.001c0 1.268-.63 2.39-1.593 3.069a3.746 3.746 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043 3.745 3.745 0 0 1-3.068 1.593c-1.268 0-2.39-.63-3.068-1.593a3.745 3.745 0 0 1-3.296-1.043 3.746 3.746 0 0 1-1.043-3.297 3.746 3.746 0 0 1-1.593-3.068c0-1.268.63-2.39 1.593-3.068a3.746 3.746 0 0 1 1.043-3.297 3.745 3.745 0 0 1 3.296-1.042 3.745 3.745 0 0 1 3.068-1.594c1.268 0 2.39.63 3.068 1.593a3.745 3.745 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.297 3.746 3.746 0 0 1 1.593 3.068ZM9.74 9.743h.008v.007H9.74v-.007Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 4.5h.008v.008h-.008v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
              </svg>
              Rates
            </a>
          </div>
        </div>

        <!-- 📈 REPORTS & ANALYTICS -->
        @if(auth()->user()->canAccessAdminPanel())
        <div x-data="{ open: {{ \Route::is('reports.*') ? 'true' : 'false' }} }" class="space-y-1">
          <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white group w-full flex items-center px-2 py-2 text-left text-base font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span class="mr-3 text-lg">📈</span>
            <span class="flex-1">Reports & Analytics</span>
            <svg class="ml-3 h-5 w-5 transform transition-colors duration-150 ease-in-out group-hover:text-gray-400" :class="{'rotate-90 text-gray-400': open, 'text-gray-300': !open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
          </button>
          <div x-show="open" x-transition class="space-y-1 pl-4">
            <a href="{{ route('reports.index') }}" class="{{ \Route::is('reports.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Dashboard
            </a>

            <a href="{{ route('reports.sales') }}" class="{{ \Route::is('reports.sales') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Sales & Collections
            </a>

            <a href="{{ route('reports.manifests') }}" class="{{ \Route::is('reports.manifests') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Manifest Performance
            </a>

            <a href="{{ route('reports.customers') }}" class="{{ \Route::is('reports.customers') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              Customer Analytics
            </a>

            <a href="{{ route('reports.financial') }}" class="{{ \Route::is('reports.financial') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              Financial Summary
            </a>

            <!-- Report Management Submenu -->
            <div x-data="{ reportMgmtOpen: {{ (\Route::is('reports.templates.*') || \Route::is('reports.filters.*') || \Route::is('reports.exports.*')) ? 'true' : 'false' }} }" class="space-y-1">
              <button @click="reportMgmtOpen = !reportMgmtOpen" class="{{ (\Route::is('reports.templates.*') || \Route::is('reports.filters.*') || \Route::is('reports.exports.*')) ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group w-full flex items-center px-2 py-2 text-sm font-medium rounded-md text-left">
                <svg class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="flex-1">Report Management</span>
                <svg class="ml-2 h-4 w-4 transform transition-colors" :class="{'rotate-90': reportMgmtOpen}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
              <div x-show="reportMgmtOpen" x-transition class="space-y-1 pl-6">
                <a href="{{ route('reports.templates.index') }}" class="{{ \Route::is('reports.templates.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                  Report Templates
                </a>
                <a href="{{ route('reports.filters.index') }}" class="{{ \Route::is('reports.filters.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                  Saved Filters
                </a>
                <a href="{{ route('reports.exports.index') }}" class="{{ \Route::is('reports.exports.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                  Export History
                </a>
              </div>
            </div>
          </div>
        </div>
        @endif

        <!-- ⚙️ ADMINISTRATION -->
        @if(auth()->user()->canAccessAdministration())
        <div x-data="{ open: {{ (\Route::is('admin.users.*') || \Route::is('admin.roles') || \Route::is('admin.offices.*') || \Route::is('admin.addresses.*') || \Route::is('security-dashboard') || \Route::is('admin.audit-logs.*') || \Route::is('audit-settings') || \Route::is('backup-dashboard') || \Route::is('backup-history') || \Route::is('backup-settings')) ? 'true' : 'false' }} }" class="space-y-1">
          <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white group w-full flex items-center px-2 py-2 text-left text-base font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span class="mr-3 text-lg">⚙️</span>
            <span class="flex-1">Administration</span>
            <svg class="ml-3 h-5 w-5 transform transition-colors duration-150 ease-in-out group-hover:text-gray-400" :class="{'rotate-90 text-gray-400': open, 'text-gray-300': !open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
          </button>
          <div x-show="open" x-transition class="space-y-1 pl-4">
            <!-- User Management Submenu - Available to all admins -->
            @if(in_array('user_management', auth()->user()->getAllowedAdministrationSections()))
            <div x-data="{ userOpen: {{ \Route::is('admin.users.*') ? 'true' : 'false' }} }" class="space-y-1">
              <button @click="userOpen = !userOpen" class="{{ \Route::is('admin.users.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group w-full flex items-center px-2 py-2 text-sm font-medium rounded-md text-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
                <span class="flex-1">User Management</span>
                <svg class="ml-2 h-4 w-4 transform transition-colors" :class="{'rotate-90': userOpen}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
              <div x-show="userOpen" x-transition class="space-y-1 pl-6">
                <a href="{{ route('admin.users.create') }}" class="{{ \Route::is('admin.users.create') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                  Create User
                </a>
                <a href="{{ route('admin.users.index') }}" class="{{ \Route::is('admin.users.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                  Manage Users
                </a>
              </div>
            </div>
            @endif

            <!-- Role Management Submenu - Only for superadmins -->
            @if(auth()->user()->canAccessRoleManagement())
            <div x-data="{ roleOpen: {{ \Route::is('admin.roles') ? 'true' : 'false' }} }" class="space-y-1">
              <button @click="roleOpen = !roleOpen" class="{{ \Route::is('admin.roles') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group w-full flex items-center px-2 py-2 text-sm font-medium rounded-md text-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.623 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                <span class="flex-1">Role Management</span>
                <svg class="ml-2 h-4 w-4 transform transition-colors" :class="{'rotate-90': roleOpen}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
              <div x-show="roleOpen" x-transition class="space-y-1 pl-6">
                <a href="{{ route('admin.roles') }}" class="{{ \Route::is('admin.roles') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                  Manage Roles
                </a>
              </div>
            </div>
            @endif

            <!-- Offices - Available to all admins -->
            @if(in_array('offices', auth()->user()->getAllowedAdministrationSections()))
            <a href="{{ route('admin.offices.index') }}" class="{{ \Route::is('admin.offices.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
              </svg>
              Offices
            </a>
            @endif

            <!-- Shipping Addresses - Available to all admins -->
            @if(in_array('shipping_addresses', auth()->user()->getAllowedAdministrationSections()))
            <a href="{{ route('admin.addresses.index') }}" class="{{ \Route::is('admin.addresses.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
              </svg>
              Shipping Addresses
            </a>
            @endif



            <!-- Security Dashboard - Only for superadmins -->
            @if(auth()->user()->canAccessAuditLogs())
            <a href="{{ route('security-dashboard') }}" class="{{ \Route::is('security-dashboard') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.623 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
              </svg>
              Security Dashboard
            </a>
            @endif

            <!-- Audit Management Submenu - Only for superadmins -->
            @if(auth()->user()->canAccessAuditLogs())
            <div x-data="{ auditOpen: {{ (\Route::is('admin.audit-logs.*') || \Route::is('audit-settings')) ? 'true' : 'false' }} }" class="space-y-1">
              <button @click="auditOpen = !auditOpen" class="{{ (\Route::is('admin.audit-logs.*') || \Route::is('audit-settings')) ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group w-full flex items-center px-2 py-2 text-sm font-medium rounded-md text-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                </svg>
                Audit Management
                <svg :class="{'rotate-90': auditOpen, 'rotate-0': !auditOpen}" class="ml-auto h-4 w-4 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
              
              <div x-show="auditOpen" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="space-y-1 pl-4">
                <a href="{{ route('admin.audit-logs.index') }}" class="{{ \Route::is('admin.audit-logs.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                  </svg>
                  Audit Logs
                </a>
                
                <a href="{{ route('audit-settings') }}" class="{{ \Route::is('audit-settings') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a6.759 6.759 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  </svg>
                  Audit Settings
                </a>
              </div>
            </div>
            @endif

            <!-- Backup Management Submenu - Only for superadmins -->
            @if(auth()->user()->canAccessBackupManagement())
            <div x-data="{ backupOpen: {{ (\Route::is('backup-dashboard') || \Route::is('backup-history') || \Route::is('backup-settings')) ? 'true' : 'false' }} }" class="space-y-1">
              <button @click="backupOpen = !backupOpen" class="{{ (\Route::is('backup-dashboard') || \Route::is('backup-history') || \Route::is('backup-settings')) ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group w-full flex items-center px-2 py-2 text-sm font-medium rounded-md text-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 0 0-.12-1.03l-2.268-9.64a3.375 3.375 0 0 0-3.285-2.602H7.923a3.375 3.375 0 0 0-3.285 2.602l-2.268 9.64a4.5 4.5 0 0 0-.12 1.03v.228m21.75 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m21.75 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m5.25 0v.375c0 .414.336.75.75.75s.75-.336.75-.75V17.25m-9 0v.375c0 .414.336.75.75.75s.75-.336.75-.75V17.25" />
                </svg>
                <span class="flex-1">Backup Management</span>
                <svg class="ml-2 h-4 w-4 transform transition-colors" :class="{'rotate-90': backupOpen}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
              <div x-show="backupOpen" x-transition class="space-y-1 pl-6">
                <a href="{{ route('backup-dashboard') }}" class="{{ \Route::is('backup-dashboard') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                  Dashboard
                </a>
                <a href="{{ route('backup-history') }}" class="{{ \Route::is('backup-history') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                  Backup History
                </a>
                <a href="{{ route('backup-settings') }}" class="{{ \Route::is('backup-settings') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                  Settings
                </a>
              </div>
            </div>
            @endif
          </div>
        </div>
        @endif




      </nav>
    </div>
  </div>
</div>

<!-- Mobile menu container - requires Alpine.js -->
<div x-data="{ sidebarOpen: false }" class="md:hidden">
  <!-- Mobile menu button -->
  <div class="fixed top-0 left-0 p-4 z-20">
    <button
      type="button"
      @click="sidebarOpen = true"
      class="text-gray-500 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-wax-flower-500">
      <span class="sr-only">Open sidebar</span>
      <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>
  </div>

  <!-- Off-canvas menu for mobile, show/hide based on off-canvas menu state. -->
  <div
    x-show="sidebarOpen"
    class="fixed inset-0 flex z-40"
    role="dialog"
    aria-modal="true">
    <!-- Overlay -->
    <div
      @click="sidebarOpen = false"
      x-show="sidebarOpen"
      x-transition:enter="transition-opacity ease-linear duration-300"
      x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100"
      x-transition:leave="transition-opacity ease-linear duration-300"
      x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0"
      class="fixed inset-0 bg-gray-600 bg-opacity-75"
      aria-hidden="true"></div>

    <!-- Sidebar -->
    <div
      x-show="sidebarOpen"
      x-transition:enter="transition ease-in-out duration-300 transform"
      x-transition:enter-start="-translate-x-full"
      x-transition:enter-end="translate-x-0"
      x-transition:leave="transition ease-in-out duration-300 transform"
      x-transition:leave-start="translate-x-0"
      x-transition:leave-end="-translate-x-full"
      class="relative flex-1 flex flex-col max-w-xs w-full bg-gray-800">

      <!-- Close button -->
      <div class="absolute top-0 right-0 -mr-12 pt-2">
        <button
          @click="sidebarOpen = false"
          class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-wax-flower-500">
          <span class="sr-only">Close sidebar</span>
          <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="flex-shrink-0 flex items-center h-16 px-4 bg-gray-900">
        <x-logo-white class="h-8 w-auto" />
      </div>

      <div class="mt-5 flex-1 h-0 overflow-y-auto">
        <nav class="px-2 space-y-2">
          <!-- Dashboard - Always visible -->
          <a href="/" class="{{ \Route::is('home') == true ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
            <svg class="text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Dashboard
          </a>

          <!-- 📊 OPERATIONS (Mobile) -->
          <div x-data="{ open: {{ (\Route::is('package-distribution') || \Route::is('admin.manifests.*')) ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white group w-full flex items-center pl-2 pr-1 py-2 text-left text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <svg class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <span class="flex-1">📊 Operations</span>
              <svg class="ml-3 h-5 w-5 transform transition-colors" :class="{'rotate-90': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
            <div x-show="open" x-transition class="space-y-1 pl-4">
              <a href="{{ route('package-distribution') }}" class="{{ \Route::is('package-distribution') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Package Distribution
              </a>
              <a href="{{ route('admin.manifests.index') }}" class="{{ \Route::is('admin.manifests.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                All Manifests
              </a>
              <a href="{{ route('admin.manifests.create') }}" class="{{ \Route::is('admin.manifests.create') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Create Manifest
              </a>
            </div>
          </div>

          <!-- 👥 CUSTOMER MANAGEMENT (Mobile) -->
          <div x-data="{ open: {{ (\Route::is('admin.customers.*') || \Route::is('customers') || \Route::is('admin.broadcast-messages.*') || \Route::is('view-pre-alerts')) ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white group w-full flex items-center pl-2 pr-1 py-2 text-left text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <svg class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              <span class="flex-1">👥 Customer Management</span>
              <svg class="ml-3 h-5 w-5 transform transition-colors" :class="{'rotate-90': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
            <div x-show="open" x-transition class="space-y-1 pl-4">
              <a href="{{ route('admin.customers.index') }}" class="{{ \Route::is('admin.customers.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                All Customers
              </a>
              <a href="{{ route('admin.customers.create') }}" class="{{ \Route::is('admin.customers.create') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Create Customer
              </a>
              <a href="{{ route('admin.broadcast-messages.index') }}" class="{{ \Route::is('admin.broadcast-messages.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Broadcast Messages
              </a>
              <a href="/admin/pre-alerts" class="{{ \Route::is('view-pre-alerts') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Pre-Alerts
              </a>
            </div>
          </div>

          <!-- 💰 FINANCIAL (Mobile) -->
          <div x-data="{ open: {{ (\Route::is('view-purchase-requests') || \Route::is('transactions') || \Route::is('customer.transactions') || \Route::is('view-rates')) ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white group w-full flex items-center pl-2 pr-1 py-2 text-left text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <svg class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="flex-1">💰 Financial</span>
              <svg class="ml-3 h-5 w-5 transform transition-colors" :class="{'rotate-90': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
            <div x-show="open" x-transition class="space-y-1 pl-4">
              <a href="/admin/purchase-requests" class="{{ \Route::is('view-purchase-requests') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Purchase Requests
              </a>
              @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
              <a href="/admin/transactions" class="{{ \Route::is('transactions') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Transactions
              </a>
              @endif
              @if(auth()->user()->isCustomer())
              <a href="{{ route('customer.transactions') }}" class="{{ \Route::is('customer.transactions') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Transaction History
              </a>
              @endif
              <a href="/admin/rates" class="{{ \Route::is('view-rates') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Rates
              </a>
            </div>
          </div>

          <!-- 📈 REPORTS & ANALYTICS (Mobile) -->
          @if(auth()->user()->canAccessAdminPanel())
          <div x-data="{ open: {{ \Route::is('reports.*') ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white group w-full flex items-center pl-2 pr-1 py-2 text-left text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <svg class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <span class="flex-1">📈 Reports & Analytics</span>
              <svg class="ml-3 h-5 w-5 transform transition-colors" :class="{'rotate-90': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
            <div x-show="open" x-transition class="space-y-1 pl-4">
              <a href="{{ route('reports.index') }}" class="{{ \Route::is('reports.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Dashboard
              </a>
              <a href="{{ route('reports.sales') }}" class="{{ \Route::is('reports.sales') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Sales & Collections
              </a>
              <a href="{{ route('reports.manifests') }}" class="{{ \Route::is('reports.manifests') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Manifest Performance
              </a>
              <a href="{{ route('reports.customers') }}" class="{{ \Route::is('reports.customers') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Customer Analytics
              </a>
              <a href="{{ route('reports.financial') }}" class="{{ \Route::is('reports.financial') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Financial Summary
              </a>
              <a href="{{ route('reports.templates.index') }}" class="{{ \Route::is('reports.templates.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Report Templates
              </a>
              <a href="{{ route('reports.filters.index') }}" class="{{ \Route::is('reports.filters.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Saved Filters
              </a>
              <a href="{{ route('reports.exports.index') }}" class="{{ \Route::is('reports.exports.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Export History
              </a>
            </div>
          </div>
          @endif

          <!-- ⚙️ ADMINISTRATION (Mobile) -->
          @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
          <div x-data="{ open: {{ (\Route::is('admin.users.*') || \Route::is('admin.roles') || \Route::is('admin.offices.*') || \Route::is('admin.addresses.*') || \Route::is('security-dashboard') || \Route::is('admin.audit-logs.*') || \Route::is('audit-settings') || \Route::is('backup-dashboard') || \Route::is('backup-history') || \Route::is('backup-settings')) ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white group w-full flex items-center pl-2 pr-1 py-2 text-left text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <svg class="text-gray-400 group-hover:text-gray-300 mr-3 flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span class="flex-1">⚙️ Administration</span>
              <svg class="ml-3 h-5 w-5 transform transition-colors" :class="{'rotate-90': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
            <div x-show="open" x-transition class="space-y-1 pl-4">
              <a href="{{ route('admin.users.create') }}" class="{{ \Route::is('admin.users.create') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Create User
              </a>
              <a href="{{ route('admin.users.index') }}" class="{{ \Route::is('admin.users.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Manage Users
              </a>
              @if(auth()->user()->isSuperAdmin())
              <a href="{{ route('admin.roles') }}" class="{{ \Route::is('admin.roles') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Manage Roles
              </a>
              @endif
              <a href="{{ route('admin.offices.index') }}" class="{{ \Route::is('admin.offices.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Offices
              </a>
              <a href="{{ route('admin.addresses.index') }}" class="{{ \Route::is('admin.addresses.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Shipping Addresses
              </a>
              @if(auth()->user()->isSuperAdmin())
              <a href="{{ route('security-dashboard') }}" class="{{ \Route::is('security-dashboard') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Security Dashboard
              </a>
              <a href="{{ route('admin.audit-logs.index') }}" class="{{ \Route::is('admin.audit-logs.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Audit Logs
              </a>
              <a href="{{ route('audit-settings') }}" class="{{ \Route::is('audit-settings') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Audit Settings
              </a>
              <a href="{{ route('backup-dashboard') }}" class="{{ \Route::is('backup-dashboard') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Backup Dashboard
              </a>
              <a href="{{ route('backup-history') }}" class="{{ \Route::is('backup-history') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Backup History
              </a>
              <a href="{{ route('backup-settings') }}" class="{{ \Route::is('backup-settings') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-xs font-medium rounded-md">
                Backup Settings
              </a>
              @endif
            </div>
          </div>
          @endif


            Rates
          </a>

          <!-- Backup Management Expandable Menu (Mobile - visible only to superadmin) -->
          @if(auth()->user()->isSuperAdmin())
          <div x-data="{ open: {{ (\Route::is('backup-dashboard') || \Route::is('backup-history')) ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="open = !open" class="{{ (\Route::is('backup-dashboard') || \Route::is('backup-history')) ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group w-full flex items-center pl-2 pr-1 py-2 text-left text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <!-- Heroicon name: outline/server -->
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="text-gray-400 group-hover:text-gray-300 mr-4 flex-shrink-0 h-6 w-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 0 0-.12-1.03l-2.268-9.64a3.375 3.375 0 0 0-3.285-2.602H7.923a3.375 3.375 0 0 0-3.285 2.602l-2.268 9.64a4.5 4.5 0 0 0-.12 1.03v.228m21.75 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m21.75 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m5.25 0v.375c0 .414.336.75.75.75s.75-.336.75-.75V17.25m-9 0v.375c0 .414.336.75.75.75s.75-.336.75-.75V17.25" />
              </svg>
              <span class="flex-1">Backup Management</span>
              <!-- Expand/collapse icon -->
              <svg class="ml-3 h-5 w-5 transform transition-colors duration-150 ease-in-out group-hover:text-gray-400" :class="{'rotate-90 text-gray-400': open, 'text-gray-300': !open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="space-y-1">
              <a href="{{ route('backup-dashboard') }}" class="{{ \Route::is('backup-dashboard') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group w-full flex items-center pl-11 pr-2 py-2 text-sm font-medium rounded-md">
                Dashboard
              </a>
              <a href="{{ route('backup-history') }}" class="{{ \Route::is('backup-history') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group w-full flex items-center pl-11 pr-2 py-2 text-sm font-medium rounded-md">
                Backup History
              </a>
            </div>
          </div>
          @endif


        </nav>
      </div>
    </div>

    <div class="flex-shrink-0 w-14" aria-hidden="true">
      <!-- Dummy element to force sidebar to shrink to fit close icon -->
    </div>
  </div>
</div>