<div>
    <!-- Page Header -->
    <div class="bg-white shadow">
        <div class="px-4 sm:px-6 lg:max-w-6xl lg:mx-auto lg:px-8">
            <div class="py-6 md:flex md:items-center md:justify-between lg:border-t lg:border-gray-200">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center">
                        <div>
                            <div class="flex items-center">
                                <h1 class="ml-3 text-2xl font-bold leading-7 text-gray-900 sm:leading-9 sm:truncate">
                                    Edit User: {{ $user->full_name }}
                                </h1>
                            </div>
                            <dl class="mt-6 flex flex-col sm:ml-3 sm:mt-1 sm:flex-row sm:flex-wrap">
                                <dt class="sr-only">Current role</dt>
                                <dd class="flex items-center text-sm text-gray-500 font-medium capitalize sm:mr-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @if($user->role->name === 'superadmin') bg-red-100 text-red-800
                                        @elseif($user->role->name === 'admin') bg-blue-100 text-blue-800
                                        @elseif($user->role->name === 'purchaser') bg-green-100 text-green-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($user->role->name) }}
                                    </span>
                                </dd>
                                <dt class="sr-only">Member since</dt>
                                <dd class="flex items-center text-sm text-gray-500 font-medium capitalize sm:mr-6">
                                    <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                    </svg>
                                    Member since {{ $user->created_at->format('M j, Y') }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex space-x-3 md:mt-0 md:ml-4">
                    <button wire:click="cancel" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </button>
                    <button wire:click="update" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                        <span wire:loading.remove wire:target="update">Update User</span>
                        <span wire:loading wire:target="update">Updating...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 sm:px-6 lg:max-w-6xl lg:mx-auto lg:px-8">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="setActiveTab('basic')" class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'basic' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Basic Information
                </button>
                @if($canChangeRole)
                    <button wire:click="setActiveTab('role')" class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'role' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Role Management
                    </button>
                @endif
                <button wire:click="setActiveTab('security')" class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'security' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Security
                </button>
                @if($roleChangeHistory->count() > 0)
                    <button wire:click="setActiveTab('audit')" class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'audit' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Audit Trail
                    </button>
                @endif
            </nav>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Tab Content -->
    <div class="bg-white shadow">
        <div class="px-4 sm:px-6 lg:max-w-6xl lg:mx-auto lg:px-8 py-6">
            
            <!-- Basic Information Tab -->
            @if($activeTab === 'basic')
                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="firstName" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input wire:model.defer="firstName" type="text" id="firstName" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            @error('firstName') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="lastName" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input wire:model.defer="lastName" type="text" id="lastName" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            @error('lastName') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input wire:model.defer="email" type="email" id="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if($user->profile)
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Profile Information</h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Account Number</label>
                                    <div class="mt-1 text-sm text-gray-900">{{ $user->profile->account_number }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Telephone</label>
                                    <div class="mt-1 text-sm text-gray-900">{{ $user->profile->telephone_number }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Address</label>
                                    <div class="mt-1 text-sm text-gray-900">
                                        {{ $user->profile->street_address }}<br>
                                        {{ $user->profile->city_town }}, {{ $user->profile->parish }}<br>
                                        {{ $user->profile->country }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Role Management Tab -->
            @if($activeTab === 'role' && $canChangeRole)
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Role Assignment</h3>
                        <p class="mt-1 text-sm text-gray-500">Change the user's role to modify their permissions and access level.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Current Role</label>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium 
                                    @if($currentRole === 'superadmin') bg-red-100 text-red-800
                                    @elseif($currentRole === 'admin') bg-blue-100 text-blue-800
                                    @elseif($currentRole === 'purchaser') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($currentRole) }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label for="newRole" class="block text-sm font-medium text-gray-700">New Role</label>
                            <select wire:model="newRole" id="newRole" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                @foreach($availableRoles as $role)
                                    <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                @endforeach
                            </select>
                            @error('newRole') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @error('roleChangeReason') 
                        <div class="bg-red-50 border border-red-200 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Role Change Reason Required</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>{{ $message }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @enderror

                    @if($currentRole !== $newRole)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Role Change Detected</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>You are changing this user's role from <strong>{{ ucfirst($currentRole) }}</strong> to <strong>{{ ucfirst($newRole) }}</strong>. This will immediately affect their permissions and access level.</p>
                                        @if(empty($roleChangeReason))
                                            <div class="mt-3">
                                                <p class="mb-2 font-medium">⚠️ You must provide a reason for this role change before updating.</p>
                                                <button wire:click="openRoleChangeModal" type="button" class="inline-flex items-center px-3 py-2 border border-yellow-300 shadow-sm text-sm leading-4 font-medium rounded-md text-yellow-800 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    Provide Reason for Role Change
                                                </button>
                                            </div>
                                        @else
                                            <p class="mt-2 text-green-700">✓ Role change reason provided: "{{ $roleChangeReason }}"</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Security Tab -->
            @if($activeTab === 'security')
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Password Management</h3>
                        <p class="mt-1 text-sm text-gray-500">Update the user's password or security settings.</p>
                    </div>

                    <div class="flex items-center">
                        <input wire:model="changePassword" id="changePassword" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        <label for="changePassword" class="ml-2 block text-sm text-gray-900">
                            Change Password
                        </label>
                    </div>

                    @if($changePassword)
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="newPassword" class="block text-sm font-medium text-gray-700">New Password</label>
                                <input wire:model.defer="newPassword" type="password" id="newPassword" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @error('newPassword') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="newPasswordConfirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input wire:model.defer="newPasswordConfirmation" type="password" id="newPasswordConfirmation" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>
                    @endif

                    <div class="border-t border-gray-200 pt-6">
                        <h4 class="text-base font-medium text-gray-900">Account Status</h4>
                        <div class="mt-4 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">Email Verification</div>
                                    <div class="text-sm text-gray-500">User's email verification status</div>
                                </div>
                                <div>
                                    @if($user->email_verified_at)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Verified
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Unverified
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">Account Status</div>
                                    <div class="text-sm text-gray-500">Current account status</div>
                                </div>
                                <div>
                                    @if($user->trashed())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Deleted
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Audit Trail Tab -->
            @if($activeTab === 'audit' && $roleChangeHistory->count() > 0)
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Role Change History</h3>
                        <p class="mt-1 text-sm text-gray-500">Track all role changes made to this user account.</p>
                    </div>

                    <div class="flow-root">
                        <ul class="-mb-8">
                            @foreach($roleChangeHistory as $audit)
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        Role changed from <span class="font-medium text-gray-900">{{ ucfirst($audit->oldRole->name) }}</span> to <span class="font-medium text-gray-900">{{ ucfirst($audit->newRole->name) }}</span>
                                                        by <span class="font-medium text-gray-900">{{ $audit->changedBy->full_name }}</span>
                                                    </p>
                                                    @if($audit->reason)
                                                        <p class="mt-1 text-sm text-gray-600">
                                                            <strong>Reason:</strong> {{ $audit->reason }}
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                    <time datetime="{{ $audit->created_at->toISOString() }}">{{ $audit->created_at->format('M j, Y g:i A') }}</time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Role Change Modal -->
    @if($showRoleChangeModal)
        <div class="fixed inset-0 z-10 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Confirm Role Change
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    You are about to change this user's role from <strong>{{ ucfirst($currentRole) }}</strong> to <strong>{{ ucfirst($newRole) }}</strong>. Please provide a reason for this change.
                                </p>
                            </div>
                            <div class="mt-4">
                                <label for="roleChangeReason" class="block text-sm font-medium text-gray-700 text-left">Reason for Role Change</label>
                                <textarea wire:model.defer="roleChangeReason" id="roleChangeReason" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Explain why this role change is necessary..."></textarea>
                                @error('roleChangeReason') <p class="mt-2 text-sm text-red-600 text-left">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button wire:click="confirmRoleChange" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                            Confirm Change
                        </button>
                        <button wire:click="cancelRoleChange" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>