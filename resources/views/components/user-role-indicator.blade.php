@props(['user' => null])

@php
    $user = $user ?? auth()->user();
    $allowedSections = $user->getAllowedAdministrationSections();
@endphp

<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">
                Current Role: {{ ucfirst($user->role->name ?? 'Unknown') }}
            </h3>
            <div class="mt-2 text-sm text-blue-700">
                <p><strong>Access Level:</strong> 
                    @if($user->isSuperAdmin())
                        <span class="text-green-600 font-semibold">Full System Access</span>
                    @elseif($user->isAdmin())
                        <span class="text-yellow-600 font-semibold">Limited Admin Access</span>
                    @else
                        <span class="text-gray-600">Standard User</span>
                    @endif
                </p>
                
                @if($user->canAccessAdministration())
                <p class="mt-1"><strong>Administration Sections:</strong></p>
                <ul class="list-disc list-inside ml-4 mt-1">
                    @if(in_array('user_management', $allowedSections))
                        <li>User Management</li>
                    @endif
                    @if(in_array('offices', $allowedSections))
                        <li>Offices</li>
                    @endif
                    @if(in_array('shipping_addresses', $allowedSections))
                        <li>Shipping Addresses</li>
                    @endif
                    @if(in_array('role_management', $allowedSections))
                        <li class="text-green-600 font-semibold">Role Management (Superadmin Only)</li>
                    @endif
                    @if(in_array('backup_management', $allowedSections))
                        <li class="text-green-600 font-semibold">Backup Management (Superadmin Only)</li>
                    @endif
                </ul>
                @endif
            </div>
        </div>
    </div>
</div>