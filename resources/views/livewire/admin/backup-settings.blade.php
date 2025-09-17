<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Backup Settings</h1>
                <p class="mt-1 text-sm text-gray-600">Configure automated backup schedules, retention policies, and notifications</p>
            </div>
            <div class="space-x-2">
                <button 
                    wire:click="openAddScheduleModal"
                    dusk="add-schedule-button"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                >
                    Add Schedule
                </button>
            </div>
        </div>
    </div>

    <!-- Error Messages -->
    @if ($errors->has('general'))
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-800">{{ $errors->first('general') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Backup Schedules Section -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Automated Backup Schedules</h2>
            <p class="mt-1 text-sm text-gray-600">Configure when and how often backups should run automatically</p>
        </div>
        
        <div class="p-6">
            @if (count($schedules) > 0)
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequency</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Retention</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($schedules as $schedule)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $schedule['name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($schedule['type'] === 'database') bg-blue-100 text-blue-800
                                            @elseif($schedule['type'] === 'files') bg-green-100 text-green-800
                                            @else bg-purple-100 text-purple-800 @endif">
                                            {{ ucfirst($schedule['type']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ ucfirst($schedule['frequency']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $schedule['time'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button 
                                            wire:click="toggleScheduleStatus({{ $schedule['id'] }})"
                                            dusk="toggle-schedule-{{ $schedule['id'] }}"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-colors
                                                @if($schedule['is_active']) bg-green-100 text-green-800 hover:bg-green-200
                                                @else bg-red-100 text-red-800 hover:bg-red-200 @endif"
                                        >
                                            {{ $schedule['is_active'] ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $schedule['retention_days'] }} days
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <button 
                                            wire:click="editSchedule({{ $schedule['id'] }})"
                                            dusk="edit-schedule-{{ $schedule['id'] }}"
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            Edit
                                        </button>
                                        <button 
                                            wire:click="confirmDeleteSchedule({{ $schedule['id'] }})"
                                            dusk="delete-schedule-{{ $schedule['id'] }}"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No backup schedules</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating your first automated backup schedule.</p>
                    <div class="mt-6">
                        <button 
                            wire:click="openAddScheduleModal"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                        >
                            Add Schedule
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Retention Policy Section -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Retention Policies</h2>
            <p class="mt-1 text-sm text-gray-600">Configure how long backup files should be kept before automatic cleanup</p>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="database_retention" class="block text-sm font-medium text-gray-700">Database Backup Retention</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input 
                            type="number" 
                            id="database_retention"
                            dusk="database-retention"
                            wire:model="retentionSettings.database_days"
                            class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md"
                            min="1" 
                            max="365"
                        >
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">days</span>
                        </div>
                    </div>
                    @error('retentionSettings.database_days')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="files_retention" class="block text-sm font-medium text-gray-700">File Backup Retention</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input 
                            type="number" 
                            id="files_retention"
                            dusk="files-retention"
                            wire:model="retentionSettings.files_days"
                            class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md"
                            min="1" 
                            max="365"
                        >
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">days</span>
                        </div>
                    </div>
                    @error('retentionSettings.files_days')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button 
                        wire:click="saveRetentionSettings"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        dusk="save-retention-button"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        <span wire:loading.remove wire:target="saveRetentionSettings">Save Retention Settings</span>
                        <span wire:loading wire:target="saveRetentionSettings">Saving...</span>
                    </button>
                    <button 
                        wire:click="runCleanupNow"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        dusk="cleanup-now-button"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        <span wire:loading.remove wire:target="runCleanupNow">Run Cleanup Now</span>
                        <span wire:loading wire:target="runCleanupNow">Running...</span>
                    </button>
                </div>
            </div>

            @error('retention')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- File Backup Configuration Section -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">File Backup Configuration</h2>
            <p class="mt-1 text-sm text-gray-600">Directories and files included in file backups</p>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Included Directories</h3>
                    <div class="bg-gray-50 rounded-md p-3">
                        <ul class="space-y-1 text-sm text-gray-600">
                            @foreach($this->getBackupDirectories() as $directory)
                                <li class="flex items-center">
                                    <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <code class="text-xs bg-white px-2 py-1 rounded">{{ $directory }}</code>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Excluded Patterns</h3>
                    <div class="bg-gray-50 rounded-md p-3">
                        <ul class="space-y-1 text-sm text-gray-600">
                            @foreach($this->getExcludePatterns() as $pattern)
                                <li class="flex items-center">
                                    <svg class="h-4 w-4 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    <code class="text-xs bg-white px-2 py-1 rounded">{{ $pattern }}</code>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 p-4 bg-blue-50 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">File Backup Configuration</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>File backups will include all files and subdirectories from the listed directories, excluding files matching the exclude patterns. To modify these settings, update the <code>config/backup.php</code> file.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Settings Section -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Notification Settings</h2>
            <p class="mt-1 text-sm text-gray-600">Configure email notifications for backup operations</p>
        </div>
        
        <div class="p-6">
            <div class="space-y-6">
                <div>
                    <label for="notification_email" class="block text-sm font-medium text-gray-700">Notification Email</label>
                    <input 
                        type="email" 
                        id="notification_email"
                        dusk="notification-email"
                        wire:model="notificationSettings.email"
                        class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                        placeholder="admin@example.com"
                    >
                    @error('notificationSettings.email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-4">
                    <div class="flex items-center">
                        <input 
                            id="notify_success" 
                            type="checkbox" 
                            dusk="notify-success"
                            wire:model="notificationSettings.notify_on_success"
                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                        >
                        <label for="notify_success" class="ml-2 block text-sm text-gray-900">
                            Send notifications for successful backups
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input 
                            id="notify_failure" 
                            type="checkbox" 
                            dusk="notify-failure"
                            wire:model="notificationSettings.notify_on_failure"
                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                        >
                        <label for="notify_failure" class="ml-2 block text-sm text-gray-900">
                            Send notifications for failed backups
                        </label>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <button 
                        wire:click="saveNotificationSettings"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        dusk="save-notification-button"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        <span wire:loading.remove wire:target="saveNotificationSettings">Save Notification Settings</span>
                        <span wire:loading wire:target="saveNotificationSettings">Saving...</span>
                    </button>
                    <button 
                        wire:click="testNotificationEmail"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        dusk="test-email-button"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        <span wire:loading.remove wire:target="testNotificationEmail">Send Test Email</span>
                        <span wire:loading wire:target="testNotificationEmail">Sending...</span>
                    </button>
                </div>

                @error('notifications')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Add/Edit Schedule Modal -->
    @if ($showAddScheduleModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeAddScheduleModal">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white" dusk="schedule-modal" wire:click.stop>
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ $editingScheduleId ? 'Edit Backup Schedule' : 'Add Backup Schedule' }}
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="schedule_name" class="block text-sm font-medium text-gray-700">Schedule Name</label>
                            <input 
                                type="text" 
                                id="schedule_name"
                                dusk="schedule-name"
                                wire:model="newSchedule.name"
                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                placeholder="Daily Database Backup"
                            >
                            @error('newSchedule.name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="schedule_type" class="block text-sm font-medium text-gray-700">Backup Type</label>
                            <select 
                                id="schedule_type"
                                dusk="schedule-type"
                                wire:model="newSchedule.type"
                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            >
                                <option value="database">Database Only</option>
                                <option value="files">Files Only</option>
                                <option value="full">Full Backup (Database + Files)</option>
                            </select>
                            @error('newSchedule.type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="schedule_frequency" class="block text-sm font-medium text-gray-700">Frequency</label>
                                <select 
                                    id="schedule_frequency"
                                    dusk="schedule-frequency"
                                    wire:model="newSchedule.frequency"
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                                @error('newSchedule.frequency')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="schedule_time" class="block text-sm font-medium text-gray-700">Time</label>
                                <input 
                                    type="time" 
                                    id="schedule_time"
                                    dusk="schedule-time"
                                    wire:model="newSchedule.time"
                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                >
                                @error('newSchedule.time')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="schedule_retention" class="block text-sm font-medium text-gray-700">Retention Period (days)</label>
                            <input 
                                type="number" 
                                id="schedule_retention"
                                dusk="schedule-retention"
                                wire:model="newSchedule.retention_days"
                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                min="1" 
                                max="365"
                            >
                            @error('newSchedule.retention_days')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center">
                            <input 
                                id="schedule_active" 
                                type="checkbox" 
                                dusk="schedule-active"
                                wire:model="newSchedule.is_active"
                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                            >
                            <label for="schedule_active" class="ml-2 block text-sm text-gray-900">
                                Schedule is active
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button 
                            wire:click="closeAddScheduleModal"
                            dusk="cancel-schedule-button"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="saveSchedule"
                            dusk="save-schedule-button"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            {{ $editingScheduleId ? 'Update Schedule' : 'Create Schedule' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteConfirm)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" dusk="delete-confirmation-modal">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mt-2">Delete Backup Schedule</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to delete this backup schedule? This action cannot be undone.
                        </p>
                    </div>
                    <div class="flex justify-center space-x-3 mt-4">
                        <button 
                            wire:click="cancelDelete"
                            dusk="cancel-delete-button"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="deleteSchedule"
                            dusk="confirm-delete-button"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
