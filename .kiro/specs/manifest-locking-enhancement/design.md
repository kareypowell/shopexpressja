# Design Document

## Overview

This design document outlines the implementation of a manifest locking mechanism that controls package editing permissions, automatically closes manifests when all packages are delivered, and provides controlled unlocking functionality with audit trails. The solution builds upon the existing Laravel/Livewire architecture and integrates with the current manifest and package management systems.

The enhancement includes:
- Conditional editing controls based on manifest `is_open` status
- Automatic manifest closure when all packages reach "delivered" status
- Manual unlock functionality with reason tracking and audit logging
- Visual indicators for manifest status throughout the UI
- Comprehensive audit trail for all locking/unlocking operations

## Architecture

### Manifest Locking System
The manifest locking system will be implemented using the existing `is_open` field on the manifests table with the following components:
- **Manifest Lock Service**: Handles locking/unlocking logic and validation
- **Package Status Observer**: Monitors package status changes to trigger auto-closure
- **Manifest Audit Model**: Tracks all locking/unlocking events
- **UI State Management**: Controls visibility of editing elements based on lock status

### Auto-Closure Mechanism
The auto-closure system will monitor package status changes and automatically lock manifests:
- **Package Observer Enhancement**: Extends existing observer to check manifest completion
- **Manifest Completion Checker**: Service to validate if all packages are delivered
- **Event Logging**: Records automatic closure events for audit purposes

### Unlock Authorization System
The unlock system provides controlled access to modify locked manifests:
- **Permission Validation**: Checks user authorization for unlock operations
- **Reason Validation**: Ensures unlock reasons meet business requirements
- **Notification System**: Alerts stakeholders of unlock events
- **Audit Trail**: Comprehensive logging of all unlock activities

## Components and Interfaces

### 1. Core Services

#### Manifest Lock Service
```php
class ManifestLockService
{
    public function canEdit(Manifest $manifest, User $user): bool
    {
        return $manifest->is_open && $user->can('edit', $manifest);
    }
    
    public function autoCloseIfComplete(Manifest $manifest): bool
    {
        if (!$manifest->is_open) {
            return false;
        }
        
        $allDelivered = $manifest->packages()
            ->where('status', '!=', 'delivered')
            ->count() === 0;
            
        if ($allDelivered && $manifest->packages()->count() > 0) {
            return $this->closeManifeest($manifest, 'auto_complete');
        }
        
        return false;
    }
    
    public function unlockManifest(Manifest $manifest, User $user, string $reason): array
    {
        if (!$user->can('unlock', $manifest)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        if (empty(trim($reason))) {
            return ['success' => false, 'message' => 'Reason is required'];
        }
        
        DB::transaction(function() use ($manifest, $user, $reason) {
            $manifest->update(['is_open' => true]);
            
            ManifestAudit::create([
                'manifest_id' => $manifest->id,
                'user_id' => $user->id,
                'action' => 'unlocked',
                'reason' => $reason,
                'performed_at' => now()
            ]);
        });
        
        // Send notification to stakeholders
        $this->notifyUnlock($manifest, $user, $reason);
        
        return ['success' => true, 'message' => 'Manifest unlocked successfully'];
    }
    
    private function closeManifest(Manifest $manifest, string $reason): bool
    {
        return DB::transaction(function() use ($manifest, $reason) {
            $manifest->update(['is_open' => false]);
            
            ManifestAudit::create([
                'manifest_id' => $manifest->id,
                'user_id' => auth()->id(),
                'action' => 'closed',
                'reason' => $reason,
                'performed_at' => now()
            ]);
            
            return true;
        });
    }
}
```

#### Package Status Observer Enhancement
```php
class PackageObserver
{
    public function updated(Package $package)
    {
        // Existing observer logic...
        
        // Check for manifest auto-closure
        if ($package->isDirty('status') && $package->status === 'delivered') {
            $manifestLockService = app(ManifestLockService::class);
            $manifestLockService->autoCloseIfComplete($package->manifest);
        }
    }
}
```

### 2. Data Models

#### Manifest Audit Model
```php
class ManifestAudit extends Model
{
    protected $fillable = [
        'manifest_id',
        'user_id', 
        'action',
        'reason',
        'performed_at'
    ];
    
    protected $casts = [
        'performed_at' => 'datetime'
    ];
    
    public function manifest()
    {
        return $this->belongsTo(Manifest::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'closed' => 'Closed',
            'unlocked' => 'Unlocked',
            'auto_complete' => 'Auto-closed (All Delivered)',
            default => ucfirst($this->action)
        };
    }
}
```

#### Manifest Model Enhancement
```php
class Manifest extends Model
{
    // Existing model code...
    
    public function audits()
    {
        return $this->hasMany(ManifestAudit::class)->orderBy('performed_at', 'desc');
    }
    
    public function getStatusLabelAttribute(): string
    {
        return $this->is_open ? 'Open' : 'Closed';
    }
    
    public function getStatusBadgeClassAttribute(): string
    {
        return $this->is_open ? 'success' : 'secondary';
    }
    
    public function canBeEdited(): bool
    {
        return $this->is_open;
    }
    
    public function allPackagesDelivered(): bool
    {
        return $this->packages()->where('status', '!=', 'delivered')->count() === 0 
               && $this->packages()->count() > 0;
    }
}
```

### 3. Livewire Components

#### Manifest Lock Status Component
```php
class ManifestLockStatus extends Component
{
    public Manifest $manifest;
    public bool $showUnlockModal = false;
    public string $unlockReason = '';
    
    public function render()
    {
        return view('livewire.manifests.lock-status');
    }
    
    public function showUnlockModal()
    {
        if (!auth()->user()->can('unlock', $this->manifest)) {
            $this->addError('unlock', 'You do not have permission to unlock this manifest.');
            return;
        }
        
        $this->showUnlockModal = true;
    }
    
    public function unlockManifest()
    {
        $this->validate([
            'unlockReason' => 'required|string|min:10|max:500'
        ]);
        
        $lockService = app(ManifestLockService::class);
        $result = $lockService->unlockManifest(
            $this->manifest, 
            auth()->user(), 
            $this->unlockReason
        );
        
        if ($result['success']) {
            $this->manifest->refresh();
            $this->showUnlockModal = false;
            $this->unlockReason = '';
            $this->emit('manifestUnlocked');
            session()->flash('success', $result['message']);
        } else {
            $this->addError('unlock', $result['message']);
        }
    }
    
    public function cancelUnlock()
    {
        $this->showUnlockModal = false;
        $this->unlockReason = '';
        $this->resetErrorBag();
    }
}
```

#### Enhanced Manifest Package Component
```php
class ManifestPackage extends Component
{
    public Manifest $manifest;
    public $packages;
    
    protected $listeners = ['manifestUnlocked' => 'refreshPackages'];
    
    public function mount()
    {
        $this->refreshPackages();
    }
    
    public function render()
    {
        return view('livewire.manifests.manifest-package', [
            'canEdit' => $this->manifest->canBeEdited() && auth()->user()->can('edit', $this->manifest)
        ]);
    }
    
    public function refreshPackages()
    {
        $this->packages = $this->manifest->packages()->with('user')->get();
    }
    
    // Existing package management methods only work if manifest is open
    public function updatePackage($packageId, $field, $value)
    {
        if (!$this->manifest->canBeEdited()) {
            $this->addError('edit', 'Cannot edit packages on a closed manifest.');
            return;
        }
        
        // Existing update logic...
    }
}
```

### 4. UI Templates

#### Manifest Lock Status Template
```blade
<!-- resources/views/livewire/manifests/lock-status.blade.php -->
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center space-x-3">
        <span class="text-sm font-medium text-gray-700">Status:</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                     {{ $manifest->is_open ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
            {{ $manifest->status_label }}
        </span>
        
        @if(!$manifest->is_open)
            <span class="text-xs text-gray-500">
                (Locked - View Only)
            </span>
        @endif
    </div>
    
    <div class="flex space-x-2">
        @if(!$manifest->is_open && auth()->user()->can('unlock', $manifest))
            <button wire:click="showUnlockModal" 
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                </svg>
                Unlock Manifest
            </button>
        @endif
    </div>
    
    <!-- Unlock Modal -->
    @if($showUnlockModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Unlock Manifest</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Please provide a reason for unlocking this manifest. This action will be logged for audit purposes.
                    </p>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Unlocking</label>
                        <textarea wire:model="unlockReason" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                  rows="3" 
                                  placeholder="Enter detailed reason for unlocking this manifest..."></textarea>
                        @error('unlockReason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        @error('unlock') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button wire:click="cancelUnlock" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button wire:click="unlockManifest" 
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Unlock Manifest
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
```

#### Enhanced Package List Template
```blade
<!-- Enhanced package display with conditional editing -->
@if($canEdit)
    <!-- Existing editable package interface -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <!-- Package editing interface -->
        </div>
    </div>
@else
    <!-- Read-only package display -->
    <div class="bg-gray-50 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Packages</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    Read Only
                </span>
            </div>
            
            <!-- Read-only package list -->
            <div class="space-y-4">
                @foreach($packages as $package)
                    <div class="border border-gray-200 rounded-lg p-4 bg-white">
                        <!-- Package details in read-only format -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tracking Number</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $package->tracking_number }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $package->status_badge_class }}">
                                    {{ $package->status_label }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Customer</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $package->user->name }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
```

### 5. Database Schema

#### Manifest Audits Table Migration
```php
Schema::create('manifest_audits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('manifest_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('action'); // 'closed', 'unlocked', 'auto_complete'
    $table->text('reason');
    $table->timestamp('performed_at');
    $table->timestamps();
    
    $table->index(['manifest_id', 'performed_at']);
    $table->index(['user_id', 'performed_at']);
});
```

### 6. Authorization Policies

#### Manifest Policy Enhancement
```php
class ManifestPolicy
{
    // Existing policy methods...
    
    public function edit(User $user, Manifest $manifest): bool
    {
        return $manifest->is_open && ($user->isAdmin() || $user->isSuperAdmin());
    }
    
    public function unlock(User $user, Manifest $manifest): bool
    {
        return !$manifest->is_open && ($user->isAdmin() || $user->isSuperAdmin());
    }
    
    public function viewAudit(User $user, Manifest $manifest): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }
}
```

## Error Handling

### Validation and Business Rules
- Validate unlock reasons are meaningful (minimum 10 characters)
- Prevent unlocking if user lacks permissions
- Handle concurrent modification attempts gracefully
- Validate manifest state before allowing operations

### Auto-Closure Error Handling
- Handle database transaction failures during auto-closure
- Log errors when auto-closure fails
- Implement retry mechanism for failed closure attempts
- Notify administrators of persistent closure failures

### UI Error Handling
- Display clear error messages for unauthorized actions
- Handle network failures during unlock operations
- Provide user feedback for all state changes
- Implement loading states for async operations

## Testing Strategy

### Unit Tests
- **ManifestLockService Tests**: Test locking/unlocking logic and validation
- **Package Observer Tests**: Test auto-closure trigger conditions
- **Manifest Model Tests**: Test status methods and relationships
- **Policy Tests**: Validate authorization rules for editing and unlocking

### Feature Tests
- **Auto-Closure Tests**: Test complete workflow from package delivery to manifest closure
- **Unlock Process Tests**: Test unlock workflow with reason validation
- **Permission Tests**: Test access control for different user roles
- **Audit Trail Tests**: Verify comprehensive logging of all operations

### Browser Tests
- **UI State Tests**: Test conditional display of editing controls
- **Unlock Modal Tests**: Test unlock interface and validation
- **Status Display Tests**: Test visual indicators across different states
- **Responsive Tests**: Verify functionality across device sizes

## Security Considerations

### Authorization
- Implement role-based access control for unlock operations
- Validate user permissions before displaying unlock controls
- Log all unlock attempts for security monitoring
- Implement rate limiting for unlock operations

### Data Integrity
- Use database transactions for all locking operations
- Validate manifest state before allowing modifications
- Implement optimistic locking to prevent race conditions
- Audit all changes with user attribution

### Input Validation
- Sanitize unlock reason inputs to prevent XSS
- Validate reason length and content requirements
- Implement CSRF protection on unlock forms
- Rate limit unlock attempts per user

## Performance Considerations

### Database Optimization
- Add indexes for manifest audit queries
- Optimize package status checking queries
- Use eager loading for manifest-package relationships
- Implement caching for frequently accessed manifest states

### UI Performance
- Use conditional rendering to minimize DOM updates
- Implement lazy loading for audit history
- Cache manifest status in component state
- Optimize re-rendering on status changes

### Notification Performance
- Queue unlock notifications for async processing
- Batch notifications for multiple stakeholders
- Implement notification preferences and filtering
- Monitor notification delivery performance