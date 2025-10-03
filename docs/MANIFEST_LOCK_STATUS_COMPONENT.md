# ManifestLockStatus Component Usage

## Overview

The `ManifestLockStatus` Livewire component provides a user interface for displaying manifest lock status and allowing authorized users to unlock closed manifests with proper reason tracking and audit logging.

## Features

- **Status Display**: Shows whether a manifest is open or closed with visual indicators
- **Unlock Interface**: Provides a modal for unlocking closed manifests with reason validation
- **Authorization**: Respects user permissions for unlock operations
- **Audit Logging**: Records all unlock actions with user attribution and reasons
- **Real-time Updates**: Emits events to refresh other components when manifest is unlocked

## Usage

### Basic Integration

Add the component to any view where you need to display and manage manifest lock status:

```blade
<livewire:manifests.manifest-lock-status :manifest="$manifest" />
```

### Integration Example

Here's how to integrate it into an existing manifest view:

```blade
<!-- resources/views/livewire/manifests/packages/manifest-package.blade.php -->
<div>
    <!-- Manifest Lock Status Component -->
    <livewire:manifests.manifest-lock-status :manifest="$manifest" />
    
    <div class="flex items-center justify-between mb-5">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Manifest Packages
            </h3>
            @if($manifest && $manifest->name)
                <p class="text-sm text-gray-600 mt-1">{{ $manifest->name }}</p>
            @endif
        </div>

        <!-- Action buttons only show if manifest is open -->
        @if($manifest->canBeEdited())
            <div class="flex space-x-3">
                <button wire:click="goToWorkflow()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Package Workflow
                </button>

                <button wire:click="create()" class="bg-shiraz-500 hover:bg-shiraz-700 text-white font-bold py-2 px-4 rounded">
                    Add Package
                </button>
            </div>
        @endif
    </div>
    
    <!-- Rest of the component content -->
</div>
```

### Listening for Unlock Events

Other components can listen for the `manifestUnlocked` event to refresh their state:

```php
class ManifestPackage extends Component
{
    protected $listeners = ['manifestUnlocked' => 'refreshPackages'];
    
    public function refreshPackages()
    {
        // Refresh package data and re-enable editing controls
        $this->packages = $this->manifest->packages()->with('user')->get();
    }
}
```

## Component Properties

### Public Properties

- `$manifest` (Manifest): The manifest instance to display status for
- `$showUnlockModal` (bool): Controls modal visibility
- `$unlockReason` (string): The reason for unlocking (validated)

### Validation Rules

- `unlockReason`: Required, minimum 10 characters, maximum 500 characters

## Events

### Emitted Events

- `manifestUnlocked`: Fired when a manifest is successfully unlocked

### Event Listeners

The component doesn't listen for external events but can be extended to do so.

## Authorization

The component respects the following authorization policies:

- `unlock`: Determines if a user can unlock a closed manifest
- Only users with appropriate permissions will see the unlock button
- Unauthorized attempts are blocked with error messages

## Visual States

### Open Manifest
- Green status badge with "Open" text
- "Editing Enabled" indicator
- No unlock button visible

### Closed Manifest
- Gray status badge with "Closed" text
- "Locked - View Only" indicator with lock icon
- "Unlock Manifest" button (for authorized users)

### Unlock Modal
- Warning notice about audit logging
- Textarea for reason input with character counter
- Real-time validation feedback
- Loading state during unlock process

## Error Handling

The component handles various error scenarios:

- **Unauthorized Access**: Shows error message if user lacks permissions
- **Validation Errors**: Displays field-specific validation messages
- **Service Errors**: Shows error messages from the ManifestLockService
- **Network Issues**: Provides loading states and error feedback

## Styling

The component uses Tailwind CSS classes and follows the application's design system:

- Status badges use semantic colors (green for open, gray for closed)
- Modal uses standard overlay and card styling
- Form elements follow consistent input styling
- Icons from Heroicons for visual consistency

## Dependencies

- `ManifestLockService`: Handles the business logic for unlocking
- `ManifestPolicy`: Provides authorization rules
- `ManifestAudit`: Records audit trail entries
- Alpine.js: For modal transitions and interactions

## Testing

The component includes comprehensive tests covering:

- Status display for open/closed manifests
- Modal functionality and form validation
- Authorization and permission checking
- Successful unlock workflow
- Error handling scenarios

Run tests with:
```bash
php artisan test --filter="ManifestLockStatusComponentTest"
```