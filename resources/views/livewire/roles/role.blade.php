<div>
    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Role Management
        </h3>

        <div class="flex space-x-2">
            @can('create', App\Models\Role::class)
                <button wire:click="showCreateModal" class="bg-wax-flower-500 hover:bg-wax-flower-700 text-white font-bold py-2 px-4 rounded">
                    Create Role
                </button>
            @endcan
            
            @can('viewAuditTrail', App\Models\Role::class)
                <button wire:click="showAuditModal" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    View Audit Trail
                </button>
            @endcan
        </div>
    </div>

    <!-- Roles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        @foreach($roles as $role)
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-wax-flower-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold text-sm">{{ strtoupper(substr($role->name, 0, 1)) }}</span>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    {{ $role->name }}
                                    @if($role->isSystemRole())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-2">
                                            System
                                        </span>
                                    @endif
                                </dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}
                                </dd>
                                @if($role->description)
                                    <dd class="text-sm text-gray-600 mt-1">
                                        {{ $role->description }}
                                    </dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-end space-x-2">
                        @can('update', $role)
                            <button wire:click="showEditModal({{ $role->id }})" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                Edit
                            </button>
                        @endcan
                        
                        @can('delete', $role)
                            @if($role->canBeDeleted())
                                <button wire:click="showDeleteModal({{ $role->id }})" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                    Delete
                                </button>
                            @else
                                <span class="text-gray-400 text-sm font-medium cursor-not-allowed" title="Cannot delete system role or role with assigned users">
                                    Delete
                                </span>
                            @endif
                        @endcan
                        
                        @can('viewAuditTrail', App\Models\Role::class)
                            <button wire:click="showAuditModal({{ $role->id }})" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                History
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Create Role Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Role</h3>
                    
                    <form wire:submit.prevent="createRole">
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Role Name</label>
                            <input type="text" wire:model="name" id="name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea wire:model="description" id="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500"></textarea>
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="flex justify-end space-x-2">
                            <button type="button" wire:click="closeModals" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </button>
                            <button type="submit" class="bg-wax-flower-500 hover:bg-wax-flower-700 text-white font-bold py-2 px-4 rounded">
                                Create Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Role Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Role</h3>
                    
                    <form wire:submit.prevent="updateRole">
                        <div class="mb-4">
                            <label for="edit_name" class="block text-sm font-medium text-gray-700">Role Name</label>
                            <input type="text" wire:model="name" id="edit_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="edit_description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea wire:model="description" id="edit_description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-wax-flower-500 focus:border-wax-flower-500"></textarea>
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="flex justify-end space-x-2">
                            <button type="button" wire:click="closeModals" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </button>
                            <button type="submit" class="bg-wax-flower-500 hover:bg-wax-flower-700 text-white font-bold py-2 px-4 rounded">
                                Update Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Role Modal -->
    @if($showDeleteModal && $selectedRole)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Delete Role</h3>
                    
                    <p class="text-sm text-gray-600 mb-4">
                        Are you sure you want to delete the role "{{ $selectedRole->name }}"? This action cannot be undone.
                    </p>
                    
                    @if(!$selectedRole->canBeDeleted())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <p class="text-sm">
                                This role cannot be deleted because it is either a system role or has users assigned to it.
                            </p>
                        </div>
                    @endif
                    
                    <div class="flex justify-end space-x-2">
                        <button type="button" wire:click="closeModals" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Cancel
                        </button>
                        @if($selectedRole->canBeDeleted())
                            <button wire:click="deleteRole" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Delete Role
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Audit Trail Modal -->
    @if($showAuditModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-10 mx-auto p-5 border w-4/5 max-w-4xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Role Change Audit Trail</h3>
                        <button wire:click="closeModals" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="max-h-96 overflow-y-auto">
                        @if(empty($auditTrail))
                            <p class="text-gray-500 text-center py-4">No audit trail records found.</p>
                        @else
                            <div class="space-y-4">
                                @foreach($auditTrail as $audit)
                                    <div class="border-l-4 border-wax-flower-500 pl-4 py-2">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">
                                                    {{ $audit['user']['first_name'] ?? 'Unknown' }} {{ $audit['user']['last_name'] ?? 'User' }}
                                                    role changed from 
                                                    <span class="font-semibold">{{ $audit['old_role']['name'] ?? 'Unknown' }}</span>
                                                    to 
                                                    <span class="font-semibold">{{ $audit['new_role']['name'] ?? 'Unknown' }}</span>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    Changed by: {{ $audit['changed_by_user']['first_name'] ?? 'System' }} {{ $audit['changed_by_user']['last_name'] ?? '' }}
                                                </p>
                                                @if($audit['reason'])
                                                    <p class="text-xs text-gray-600 mt-1">
                                                        Reason: {{ $audit['reason'] }}
                                                    </p>
                                                @endif
                                            </div>
                                            <span class="text-xs text-gray-500">
                                                {{ \Carbon\Carbon::parse($audit['created_at'])->format('M j, Y g:i A') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>