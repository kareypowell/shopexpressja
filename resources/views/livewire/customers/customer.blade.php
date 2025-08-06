<div>
    <x-breadcrumb :items="$breadcrumbs" />
    
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between lg:items-center lg:justify-between mb-5 space-y-3 sm:space-y-0">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Customers
        </h3>
        <div class="flex-shrink-0">
            <a href="{{ route('admin.customers.create') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Add Customer
            </a>
        </div>
    </div>

    <livewire:customers.admin-customers-table />
</div>