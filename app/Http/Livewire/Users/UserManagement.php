<?php

namespace App\Http\Livewire\Users;

use App\Http\Livewire\Concerns\HasBreadcrumbs;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use AuthorizesRequests, HasBreadcrumbs, WithPagination;

    // Search and filtering
    public $search = '';
    public $roleFilter = '';
    public $statusFilter = 'active';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    // Pagination
    public $perPage = 15;
    
    // UI state
    public $showFilters = false;
    public $selectedUsers = [];
    public $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'statusFilter' => ['except' => 'active'],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        // Check if user can view users
        $this->authorize('viewAny', User::class);
        
        $this->setUserManagementBreadcrumbs();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        
        $this->resetPage();
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->statusFilter = 'active';
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedUsers = $this->users->pluck('id')->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    public function getUsersProperty()
    {
        $currentUser = auth()->user();
        
        $query = User::with(['role', 'profile']);
        
        // Apply role-based filtering
        if ($currentUser->isAdmin()) {
            // Admin can only see customers
            $query->withRole('customer');
        }
        // Superadmin can see all users (no additional filtering needed)
        
        $query->when($this->search, function ($query) {
                $query->search($this->search);
            })
            ->when($this->roleFilter, function ($query) {
                $query->withRole($this->roleFilter);
            })
            ->when($this->statusFilter, function ($query) {
                if ($this->statusFilter === 'active') {
                    $query->whereNull('deleted_at');
                } elseif ($this->statusFilter === 'deleted') {
                    $query->onlyTrashed();
                }
                // 'all' doesn't add any condition
            });

        // Apply sorting
        if ($this->sortField === 'name') {
            $query->orderBy('first_name', $this->sortDirection)
                  ->orderBy('last_name', $this->sortDirection);
        } elseif ($this->sortField === 'role') {
            $query->join('roles', 'users.role_id', '=', 'roles.id')
                  ->orderBy('roles.name', $this->sortDirection)
                  ->select('users.*');
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        return $query->paginate($this->perPage);
    }

    public function getAvailableRolesProperty()
    {
        $currentUser = auth()->user();
        
        if ($currentUser->isSuperAdmin()) {
            return Role::orderBy('name')->get();
        } elseif ($currentUser->isAdmin()) {
            // Admin can only filter by customer role
            return Role::where('name', 'customer')->orderBy('name')->get();
        }
        
        return collect();
    }

    public function getRoleStatsProperty()
    {
        $currentUser = auth()->user();
        $stats = [];
        
        if ($currentUser->isSuperAdmin()) {
            // Superadmin can see all role stats
            $roles = Role::withCount('users')->get();
            foreach ($roles as $role) {
                $stats[$role->name] = $role->users_count;
            }
        } elseif ($currentUser->isAdmin()) {
            // Admin can only see customer stats
            $customerRole = Role::where('name', 'customer')->withCount('users')->first();
            if ($customerRole) {
                $stats['customer'] = $customerRole->users_count;
            }
        }
        
        return $stats;
    }

    public function editUser($userId)
    {
        $user = User::findOrFail($userId);
        
        // Check if user can update this user
        $this->authorize('update', $user);
        
        return redirect()->route('admin.users.edit', $user);
    }

    public function viewUser($userId)
    {
        $user = User::findOrFail($userId);
        
        // Check if user can view this user
        $this->authorize('view', $user);
        
        return redirect()->route('admin.users.show', $user);
    }

    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);
        
        // Check if user can delete this user
        $this->authorize('delete', $user);
        
        try {
            $user->delete();
            
            session()->flash('success', "User {$user->full_name} has been deleted successfully.");
            
            // Reset selection if this user was selected
            $this->selectedUsers = array_diff($this->selectedUsers, [$userId]);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    public function restoreUser($userId)
    {
        $user = User::withTrashed()->findOrFail($userId);
        
        // Check if user can restore this user
        $this->authorize('restore', $user);
        
        try {
            $user->restore();
            
            session()->flash('success', "User {$user->full_name} has been restored successfully.");
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to restore user: ' . $e->getMessage());
        }
    }

    public function bulkDelete()
    {
        if (empty($this->selectedUsers)) {
            session()->flash('warning', 'No users selected for deletion.');
            return;
        }

        $users = User::whereIn('id', $this->selectedUsers)->get();
        $deletedCount = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                $this->authorize('delete', $user);
                $user->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to delete {$user->full_name}: " . $e->getMessage();
            }
        }

        if ($deletedCount > 0) {
            session()->flash('success', "Successfully deleted {$deletedCount} user(s).");
        }

        if (!empty($errors)) {
            session()->flash('error', implode('<br>', $errors));
        }

        $this->selectedUsers = [];
        $this->selectAll = false;
    }

    public function bulkRestore()
    {
        if (empty($this->selectedUsers)) {
            session()->flash('warning', 'No users selected for restoration.');
            return;
        }

        $users = User::withTrashed()->whereIn('id', $this->selectedUsers)->get();
        $restoredCount = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                $this->authorize('restore', $user);
                $user->restore();
                $restoredCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to restore {$user->full_name}: " . $e->getMessage();
            }
        }

        if ($restoredCount > 0) {
            session()->flash('success', "Successfully restored {$restoredCount} user(s).");
        }

        if (!empty($errors)) {
            session()->flash('error', implode('<br>', $errors));
        }

        $this->selectedUsers = [];
        $this->selectAll = false;
    }

    /**
     * Set breadcrumbs for user management page.
     */
    protected function setUserManagementBreadcrumbs()
    {
        $this->setBreadcrumbs([
            $this->getHomeBreadcrumb(),
            [
                'title' => 'User Management',
                'url' => null
            ]
        ]);
    }

    public function render()
    {
        return view('livewire.users.user-management', [
            'users' => $this->users,
            'availableRoles' => $this->availableRoles,
            'roleStats' => $this->roleStats,
        ])
        ->extends('layouts.app')
        ->section('content');
    }
}