<div>
    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 sm:px-6 lg:max-w-7xl lg:mx-auto lg:px-8">
            <div class="py-6">
                <div class="md:flex md:items-center md:justify-between">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
                        <p class="mt-1 text-sm text-gray-500">Manage system users and their roles</p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        @can('user.create')
                            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Create User
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="bg-gray-50 py-4">
        <div class="px-4 sm:px-6 lg:max-w-7xl lg:mx-auto lg:px-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-medium text-gray-900">User Overview</h3>
                    <div class="text-2xl font-bold text-indigo-600">{{ $users->total() }}</div>
                </div>
                
                @if($roleStats && count($roleStats) > 0)
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach($roleStats as $role => $count)
                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded-md">
                                <div class="flex items-center">
                                    <div class="w-2 h-2 rounded-full mr-2 bg-{{ $role === 'superadmin' ? 'red' : ($role === 'admin' ? 'blue' : ($role === 'purchaser' ? 'green' : 'gray')) }}-500"></div>
                                    <span class="text-xs font-medium text-gray-700">{{ ucfirst($role) }}</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">No role statistics available</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 sm:px-6 lg:max-w-7xl lg:mx-auto lg:px-8 py-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                <!-- Search -->
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input wire:model.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors duration-200" placeholder="Search users by name or email...">
                    </div>
                </div>

                <!-- Quick Filters -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Role Filter -->
                    <div class="flex items-center space-x-2">
                        <label for="roleFilter" class="text-sm font-medium text-gray-700">Role:</label>
                        <select wire:model="roleFilter" id="roleFilter" class="block pl-3 pr-8 py-2 text-sm border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 rounded-md transition-colors duration-200">
                            <option value="">All</option>
                            @foreach($availableRoles as $role)
                                <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="flex items-center space-x-2">
                        <label for="statusFilter" class="text-sm font-medium text-gray-700">Status:</label>
                        <select wire:model="statusFilter" id="statusFilter" class="block pl-3 pr-8 py-2 text-sm border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 rounded-md transition-colors duration-200">
                            <option value="active">Active</option>
                            <option value="deleted">Deleted</option>
                            <option value="all">All</option>
                        </select>
                    </div>

                    <!-- Clear Filters -->
                    @if($search || $roleFilter || $statusFilter !== 'active')
                        <button wire:click="clearFilters" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                            <svg class="-ml-1 mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Clear
                        </button>
                    @endif
                </div>
            </div>

            <!-- Bulk Actions -->
            @if(!empty($selectedUsers))
                <div class="mt-4 flex items-center justify-between bg-indigo-50 border border-indigo-200 rounded-lg px-4 py-3">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-indigo-900">{{ count($selectedUsers) }} user{{ count($selectedUsers) > 1 ? 's' : '' }} selected</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        @if($statusFilter === 'active')
                            <button wire:click="confirmBulkDelete" class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                                <svg class="-ml-1 mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete Selected
                            </button>
                        @elseif($statusFilter === 'deleted')
                            <button wire:click="bulkRestore" wire:confirm="Are you sure you want to restore the selected users?" class="inline-flex items-center px-3 py-2 border border-green-300 shadow-sm text-sm font-medium rounded-md text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                <svg class="-ml-1 mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Restore Selected
                            </button>
                        @endif
                        <button wire:click="$set('selectedUsers', [])" class="text-sm text-gray-500 hover:text-gray-700">
                            Clear selection
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white">
        <div class="px-4 sm:px-6 lg:max-w-7xl lg:mx-auto lg:px-8">
            @if($users->count() > 0)
                <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="relative w-12 px-6 py-3 sm:px-6">
                                    <input type="checkbox" wire:model="selectAll" class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </th>
                                <th scope="col" class="min-w-[12rem] py-3 pl-4 pr-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider sm:pl-6">
                                    <button wire:click="sortBy('name')" class="group inline-flex items-center hover:text-gray-700">
                                        User
                                        @if($sortField === 'name')
                                            @if($sortDirection === 'asc')
                                                <svg class="ml-1 h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                </svg>
                                            @else
                                                <svg class="ml-1 h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            @endif
                                        @else
                                            <svg class="ml-1 h-3 w-3 text-gray-400 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                    <button wire:click="sortBy('email')" class="group inline-flex items-center hover:text-gray-700">
                                        Email
                                        @if($sortField === 'email')
                                            @if($sortDirection === 'asc')
                                                <svg class="ml-1 h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                </svg>
                                            @else
                                                <svg class="ml-1 h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            @endif
                                        @else
                                            <svg class="ml-1 h-3 w-3 text-gray-400 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                    <button wire:click="sortBy('role')" class="group inline-flex items-center hover:text-gray-700">
                                        Role
                                        @if($sortField === 'role')
                                            @if($sortDirection === 'asc')
                                                <svg class="ml-1 h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                </svg>
                                            @else
                                                <svg class="ml-1 h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            @endif
                                        @else
                                            <svg class="ml-1 h-3 w-3 text-gray-400 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                    <button wire:click="sortBy('created_at')" class="group inline-flex items-center hover:text-gray-700">
                                        Created
                                        @if($sortField === 'created_at')
                                            @if($sortDirection === 'asc')
                                                <svg class="ml-1 h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                </svg>
                                            @else
                                                <svg class="ml-1 h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            @endif
                                        @else
                                            <svg class="ml-1 h-3 w-3 text-gray-400 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                                <th scope="col" class="relative py-3 pl-3 pr-4 sm:pr-6 w-20">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($users as $user)
                                <tr class="hover:bg-gray-50 transition-colors duration-150 {{ $user->trashed() ? 'opacity-60 bg-gray-50' : '' }}">
                                    <td class="relative px-6 py-4 sm:w-12 sm:px-6">
                                        <input type="checkbox" wire:model="selectedUsers" value="{{ $user->id }}" class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    </td>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center shadow-sm">
                                                    <span class="text-sm font-semibold text-white">
                                                        {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="font-medium text-gray-900">{{ $user->full_name }}</div>
                                                @if($user->profile && $user->profile->account_number)
                                                    <div class="text-gray-500 text-xs">Account: {{ $user->profile->account_number }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-500">
                                        <div class="text-gray-900 truncate max-w-xs">{{ $user->email }}</div>
                                        <div class="flex items-center mt-1">
                                            @if($user->email_verified_at)
                                                <svg class="h-3 w-3 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                <span class="text-xs text-green-600">Verified</span>
                                            @else
                                                <svg class="h-3 w-3 text-red-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                                <span class="text-xs text-red-600">Unverified</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium 
                                            @if($user->role->name === 'superadmin') bg-red-100 text-red-800
                                            @elseif($user->role->name === 'admin') bg-blue-100 text-blue-800
                                            @elseif($user->role->name === 'purchaser') bg-green-100 text-green-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($user->role->name) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <div>{{ $user->created_at->format('M j, Y') }}</div>
                                        <div class="text-xs text-gray-400">{{ $user->created_at->format('g:i A') }}</div>
                                    </td>
                                    <td class="relative py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <div class="flex items-center justify-end space-x-1">
                                            @can('user.view', $user)
                                                <button wire:click="viewUser({{ $user->id }})" class="text-indigo-600 hover:text-indigo-900 transition-colors duration-150 px-2 py-1 text-xs">
                                                    View
                                                </button>
                                            @endcan
                                            
                                            @if($user->trashed())
                                                @can('user.restore', $user)
                                                    <button wire:click="restoreUser({{ $user->id }})" wire:confirm="Are you sure you want to restore this user?" class="text-green-600 hover:text-green-900 transition-colors duration-150 px-2 py-1 text-xs">
                                                        Restore
                                                    </button>
                                                @endcan
                                            @else
                                                @can('user.update', $user)
                                                    <button wire:click="editUser({{ $user->id }})" class="text-indigo-600 hover:text-indigo-900 transition-colors duration-150 px-2 py-1 text-xs">
                                                        Edit
                                                    </button>
                                                @endcan
                                                
                                                @can('user.delete', $user)
                                                    <button wire:click="confirmDelete({{ $user->id }})" class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 border border-red-300 rounded hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors duration-150">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                {{ $users->links() }}
            @else
                <div class="text-center py-16">
                    <div class="mx-auto h-24 w-24 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="mt-6 text-lg font-medium text-gray-900">
                        @if($search || $roleFilter || $statusFilter !== 'active')
                            No users match your criteria
                        @else
                            No users found
                        @endif
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">
                        @if($search || $roleFilter || $statusFilter !== 'active')
                            Try adjusting your search terms or filters to find what you're looking for.
                        @else
                            Get started by creating your first user account.
                        @endif
                    </p>
                    
                    <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                        @if($search || $roleFilter || $statusFilter !== 'active')
                            <button wire:click="clearFilters" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Clear Filters
                            </button>
                        @endif
                        
                        @can('user.create')
                            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Create User
                            </a>
                        @endcan
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="cancelDelete"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                @if($bulkDeleteMode)
                                    Delete Selected Users
                                @else
                                    Delete User
                                @endif
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    @if($bulkDeleteMode)
                                        Are you sure you want to delete the {{ count($selectedUsers) }} selected user(s)? This action cannot be undone and will permanently remove all user accounts and their associated data.
                                    @elseif($userToDelete)
                                        Are you sure you want to delete <strong>{{ $userToDelete->full_name }}</strong>? This action cannot be undone and will permanently remove:
                                        <ul class="mt-2 list-disc list-inside text-xs">
                                            <li>User account and profile</li>
                                            <li>All associated data</li>
                                            <li>Access permissions</li>
                                        </ul>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button wire:click="executeDelete" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            @if($bulkDeleteMode)
                                Delete Users
                            @else
                                Delete User
                            @endif
                        </button>
                        <button wire:click="cancelDelete" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>