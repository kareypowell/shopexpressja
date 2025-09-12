<?php

namespace App\Http\Livewire\Roles;

use App\Models\Role as RoleModel;
use App\Models\RoleChangeAudit;
use App\Services\RoleChangeAuditService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Role extends Component
{
    use WithPagination, AuthorizesRequests;

    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showAuditModal = false;
    public $showAssignmentModal = false;

    // Form fields
    public $name = '';
    public $description = '';
    public $selectedRoleId = null;
    public $selectedRole = null;

    // Audit trail
    public $auditTrail = [];

    // User counts cache
    public $userCounts = [];

    protected $rules = [
        'name' => 'required|string|max:255|unique:roles,name|regex:/^[a-zA-Z0-9_\-\s]+$/',
        'description' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'name.required' => 'Role name is required.',
        'name.unique' => 'A role with this name already exists.',
        'name.regex' => 'Role name can only contain letters, numbers, spaces, hyphens, and underscores.',
        'name.max' => 'Role name cannot exceed 255 characters.',
        'description.max' => 'Description cannot exceed 500 characters.',
    ];

    protected $listeners = [
        'roleCreated' => 'refreshComponent',
        'roleUpdated' => 'refreshComponent',
        'roleDeleted' => 'refreshComponent',
    ];

    public function mount()
    {
        $this->authorize('viewAny', RoleModel::class);
        $this->loadUserCounts();
    }

    public function render()
    {
        $roles = RoleModel::withCount('users')->get();
        
        return view('livewire.roles.role', [
            'roles' => $roles,
        ]);
    }

    /**
     * Load user counts for all roles
     */
    public function loadUserCounts()
    {
        $this->userCounts = RoleModel::withCount('users')
            ->get()
            ->pluck('users_count', 'id')
            ->toArray();
    }

    /**
     * Get user count for a specific role
     */
    public function getUserCountByRole($roleId)
    {
        return $this->userCounts[$roleId] ?? 0;
    }

    /**
     * Show create role modal
     */
    public function showCreateModal()
    {
        $this->authorize('create', RoleModel::class);
        
        $this->resetForm();
        $this->showCreateModal = true;
    }

    /**
     * Create a new role
     */
    public function createRole()
    {
        $this->authorize('create', RoleModel::class);
        
        // Custom validation for case-insensitive uniqueness
        $this->validateRoleUniqueness();
        $this->validate();

        try {
            RoleModel::create([
                'name' => trim($this->name),
                'description' => $this->description,
            ]);

            $this->showCreateModal = false;
            $this->resetForm();
            $this->loadUserCounts();
            
            session()->flash('message', 'Role created successfully.');
            $this->emit('roleCreated');
            
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') { // Integrity constraint violation
                session()->flash('error', 'A role with this name already exists.');
            } else {
                session()->flash('error', 'Failed to create role: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * Show edit role modal
     */
    public function showEditModal($roleId)
    {
        $role = RoleModel::findOrFail($roleId);
        $this->authorize('update', $role);
        
        $this->selectedRoleId = $roleId;
        $this->selectedRole = $role;
        $this->name = $role->name;
        $this->description = $role->description;
        
        // Update validation rules to exclude current role from unique check
        $this->rules['name'] = 'required|string|max:255|unique:roles,name,' . $roleId;
        
        $this->showEditModal = true;
    }

    /**
     * Update an existing role
     */
    public function updateRole()
    {
        $role = RoleModel::findOrFail($this->selectedRoleId);
        $this->authorize('update', $role);
        
        // Custom validation for case-insensitive uniqueness
        $this->validateRoleUniqueness($this->selectedRoleId);
        $this->validate();

        try {
            $role->update([
                'name' => trim($this->name),
                'description' => $this->description,
            ]);

            $this->showEditModal = false;
            $this->resetForm();
            
            session()->flash('message', 'Role updated successfully.');
            $this->emit('roleUpdated');
            
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') { // Integrity constraint violation
                session()->flash('error', 'A role with this name already exists.');
            } else {
                session()->flash('error', 'Failed to update role: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    /**
     * Show delete confirmation modal
     */
    public function showDeleteModal($roleId)
    {
        $role = RoleModel::findOrFail($roleId);
        $this->authorize('delete', $role);
        
        $this->selectedRoleId = $roleId;
        $this->selectedRole = $role;
        $this->showDeleteModal = true;
    }

    /**
     * Delete a role
     */
    public function deleteRole()
    {
        $role = RoleModel::findOrFail($this->selectedRoleId);
        $this->authorize('delete', $role);

        try {
            if (!$role->canBeDeleted()) {
                session()->flash('error', 'Cannot delete this role. It is either a system role or has users assigned to it.');
                return;
            }

            $role->delete();

            $this->showDeleteModal = false;
            $this->resetForm();
            $this->loadUserCounts();
            
            session()->flash('message', 'Role deleted successfully.');
            $this->emit('roleDeleted');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    /**
     * Show role audit trail modal
     */
    public function showAuditModal($roleId = null)
    {
        $this->authorize('viewAuditTrail', RoleModel::class);
        
        $query = RoleChangeAudit::with(['user', 'changedBy', 'oldRole', 'newRole'])
            ->orderBy('created_at', 'desc');
            
        if ($roleId) {
            $query->where(function($q) use ($roleId) {
                $q->where('old_role_id', $roleId)
                  ->orWhere('new_role_id', $roleId);
            });
        }
        
        $this->auditTrail = $query->limit(50)->get()->toArray();
        $this->showAuditModal = true;
    }

    /**
     * Show role assignment management modal
     */
    public function showAssignmentModal()
    {
        $this->authorize('manageAssignments', RoleModel::class);
        $this->showAssignmentModal = true;
    }

    /**
     * Reset form fields
     */
    public function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->selectedRoleId = null;
        $this->selectedRole = null;
        $this->rules['name'] = 'required|string|max:255|unique:roles,name';
        $this->resetValidation();
    }

    /**
     * Close all modals
     */
    public function closeModals()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->showAuditModal = false;
        $this->showAssignmentModal = false;
        $this->resetForm();
    }

    /**
     * Refresh component data
     */
    public function refreshComponent()
    {
        $this->loadUserCounts();
    }

    /**
     * Validate role name uniqueness (case-insensitive)
     */
    protected function validateRoleUniqueness($excludeId = null)
    {
        $query = RoleModel::whereRaw('LOWER(name) = ?', [strtolower(trim($this->name))]);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        if ($query->exists()) {
            $this->addError('name', 'A role with this name already exists.');
        }
    }

    /**
     * Real-time validation for role name
     */
    public function updatedName()
    {
        $this->validateOnly('name');
        
        // Check for case-insensitive duplicates in real-time
        if (!empty($this->name)) {
            $this->validateRoleUniqueness($this->selectedRoleId);
        }
    }
}
