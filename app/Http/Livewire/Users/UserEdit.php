<?php

namespace App\Http\Livewire\Users;

use App\Http\Livewire\Concerns\HasBreadcrumbs;
use App\Models\User;
use App\Models\Role;
use App\Services\RoleChangeAuditService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class UserEdit extends Component
{
    use AuthorizesRequests, HasBreadcrumbs;

    public User $user;
    
    // User basic information
    public $firstName = '';
    public $lastName = '';
    public $email = '';
    public $currentRole = '';
    public $newRole = '';
    
    // Password management
    public $changePassword = false;
    public $newPassword = '';
    public $newPasswordConfirmation = '';
    
    // Role change
    public $roleChangeReason = '';
    public $showRoleChangeModal = false;
    
    // UI state
    public $isUpdating = false;
    public $activeTab = 'basic';

    protected function rules()
    {
        $rules = [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->user->id,
            'newRole' => 'required|exists:roles,name',
        ];

        if ($this->changePassword) {
            $rules['newPassword'] = 'required|min:8|same:newPasswordConfirmation';
        }

        if ($this->currentRole !== $this->newRole) {
            $rules['roleChangeReason'] = 'required|string|min:10|max:500';
        }

        return $rules;
    }

    protected $validationAttributes = [
        'firstName' => 'first name',
        'lastName' => 'last name',
        'newPassword' => 'new password',
        'newPasswordConfirmation' => 'password confirmation',
        'newRole' => 'role',
        'roleChangeReason' => 'role change reason',
    ];

    public function mount(User $user)
    {
        // Check if user can update this user
        $this->authorize('user.update', $user);
        
        $this->user = $user;
        $this->firstName = $user->first_name;
        $this->lastName = $user->last_name;
        $this->email = $user->email;
        $this->currentRole = $user->role->name;
        $this->newRole = $user->role->name;
        
        $this->setUserEditBreadcrumbs();
    }

    public function updatedNewRole()
    {
        // Show role change modal if role is being changed
        if ($this->currentRole !== $this->newRole) {
            $this->showRoleChangeModal = true;
        } else {
            $this->showRoleChangeModal = false;
            $this->roleChangeReason = '';
        }
    }

    public function updatedChangePassword()
    {
        if (!$this->changePassword) {
            $this->newPassword = '';
            $this->newPasswordConfirmation = '';
        }
    }

    public function confirmRoleChange()
    {
        $this->validate([
            'roleChangeReason' => 'required|string|min:10|max:500'
        ]);
        
        $this->showRoleChangeModal = false;
    }

    public function cancelRoleChange()
    {
        $this->newRole = $this->currentRole;
        $this->roleChangeReason = '';
        $this->showRoleChangeModal = false;
    }

    public function update()
    {
        // Re-check authorization before updating
        $this->authorize('user.update', $this->user);
        
        $this->isUpdating = true;

        try {
            // Validate the form data
            $this->validate();

            DB::beginTransaction();

            // Update basic user information
            $this->user->update([
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email,
            ]);

            // Update password if requested
            if ($this->changePassword && !empty($this->newPassword)) {
                $this->user->update([
                    'password' => Hash::make($this->newPassword)
                ]);
            }

            // Handle role change
            if ($this->currentRole !== $this->newRole) {
                $this->handleRoleChange();
            }

            DB::commit();

            session()->flash('success', 'User updated successfully.');
            
            // Reset password fields
            $this->changePassword = false;
            $this->newPassword = '';
            $this->newPasswordConfirmation = '';
            
            // Update current role
            $this->currentRole = $this->newRole;
            $this->roleChangeReason = '';

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            $this->isUpdating = false;
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->isUpdating = false;
            session()->flash('error', 'Failed to update user: ' . $e->getMessage());
        }

        $this->isUpdating = false;
    }

    private function handleRoleChange()
    {
        $oldRole = Role::where('name', $this->currentRole)->first();
        $newRole = Role::where('name', $this->newRole)->first();

        if (!$newRole) {
            throw new \Exception("Role '{$this->newRole}' not found in the system.");
        }

        // Update user role
        $this->user->update(['role_id' => $newRole->id]);

        // Log the role change
        $auditService = app(RoleChangeAuditService::class);
        $auditService->logRoleChange(
            $this->user,
            $oldRole ? $oldRole->id : null,
            $newRole->id,
            $this->roleChangeReason,
            request(),
            auth()->user()
        );

        // Refresh the user model to get the new role
        $this->user->refresh();
        $this->user->load('role');
    }

    public function cancel()
    {
        return redirect()->route('admin.users.index');
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function getAvailableRolesProperty()
    {
        $user = auth()->user();
        
        // Superadmin can assign any role
        if ($user->isSuperAdmin()) {
            return Role::orderBy('name')->get();
        }
        
        // Admin can only assign customer role
        if ($user->isAdmin()) {
            return Role::where('name', 'customer')->get();
        }
        
        // Others cannot change roles
        return collect();
    }

    public function getCanChangeRoleProperty()
    {
        $user = auth()->user();
        
        // Superadmin can change any role
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        // Admin can change customer roles only
        if ($user->isAdmin() && $this->user->isCustomer()) {
            return true;
        }
        
        return false;
    }

    public function getRoleChangeHistoryProperty()
    {
        return $this->user->roleChangeAudits()
            ->with(['changedBy', 'oldRole', 'newRole'])
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Set breadcrumbs for user edit page.
     */
    protected function setUserEditBreadcrumbs()
    {
        $this->setBreadcrumbs([
            $this->getHomeBreadcrumb(),
            [
                'title' => 'Users',
                'url' => route('admin.users.index')
            ],
            [
                'title' => $this->user->full_name,
                'url' => route('admin.users.show', $this->user)
            ],
            [
                'title' => 'Edit',
                'url' => null
            ]
        ]);
    }

    public function render()
    {
        return view('livewire.users.user-edit', [
            'availableRoles' => $this->availableRoles,
            'canChangeRole' => $this->canChangeRole,
            'roleChangeHistory' => $this->roleChangeHistory,
        ])
        ->extends('layouts.app')
        ->section('content');
    }
}