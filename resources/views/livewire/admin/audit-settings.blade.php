<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Audit System Settings</h1>
                <p class="mt-1 text-sm text-gray-600">Configure audit logging retention policies, alert thresholds, and system monitoring</p>
            </div>
            <div class="space-x-2">
                <button 
                    wire:click="clearSettingsCache"
                    dusk="clear-cache-button"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                >
                    Clear Cache
                </button>
                <button 
                    wire:click="initializeDefaultSettings"
                    dusk="initialize-defaults-button"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                >
                    Initialize Defaults
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

    <!-- Tab Navigation -->
    <div class="bg-white shadow rounded-lg">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button 
                    wire:click="setActiveTab('retention')"
                    dusk="retention-tab"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'retention' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Retention Policies
                </button>
                <button 
                    wire:click="setActiveTab('alerts')"
                    dusk="alerts-tab"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'alerts' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Alert Thresholds
                </button>
                <button 
                    wire:click="setActiveTab('notifications')"
                    dusk="notifications-tab"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'notifications' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Notifications
                </button>
                <button 
                    wire:click="setActiveTab('performance')"
                    dusk="performance-tab"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'performance' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Performance
                </button>
                <button 
                    wire:click="setActiveTab('export')"
                    dusk="export-tab"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'export' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Export Settings
                </button>
                <button 
                    wire:click="setActiveTab('health')"
                    dusk="health-tab"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'health' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    System Health
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Retention Policies Tab -->
            @if ($activeTab === 'retention')
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Audit Log Retention Policies</h3>
                        <p class="text-sm text-gray-600 mb-6">Configure how long different types of audit logs should be retained before automatic cleanup.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label for="retention_authentication" class="block text-sm font-medium text-gray-700">Authentication Events</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input 
                                    type="number" 
                                    id="retention_authentication"
                                    dusk="retention-authentication"
                                    wire:model="retentionSettings.authentication"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md"
                                    min="1" 
                                    max="3650"
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">days</span>
                                </div>
                            </div>
                            @error('retentionSettings.authentication')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="retention_authorization" class="block text-sm font-medium text-gray-700">Authorization Events</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input 
                                    type="number" 
                                    id="retention_authorization"
                                    dusk="retention-authorization"
                                    wire:model="retentionSettings.authorization"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md"
                                    min="1" 
                                    max="3650"
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">days</span>
                                </div>
                            </div>
                            @error('retentionSettings.authorization')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="retention_model_created" class="block text-sm font-medium text-gray-700">Model Created</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input 
                                    type="number" 
                                    id="retention_model_created"
                                    dusk="retention-model-created"
                                    wire:model="retentionSettings.model_created"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md"
                                    min="1" 
                                    max="3650"
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">days</span>
                                </div>
                            </div>
                            @error('retentionSettings.model_created')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="retention_model_updated" class="block text-sm font-medium text-gray-700">Model Updated</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input 
                                    type="number" 
                                    id="retention_model_updated"
                                    dusk="retention-model-updated"
                                    wire:model="retentionSettings.model_updated"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md"
                                    min="1" 
                                    max="3650"
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">days</span>
                                </div>
                            </div>
                            @error('retentionSettings.model_updated')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="retention_model_deleted" class="block text-sm font-medium text-gray-700">Model Deleted</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input 
                                    type="number" 
                                    id="retention_model_deleted"
                                    dusk="retention-model-deleted"
                                    wire:model="retentionSettings.model_deleted"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md"
                                    min="1" 
                                    max="3650"
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">days</span>
                                </div>
                            </div>
                            @error('retentionSettings.model_deleted')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="retention_business_action" class="block text-sm font-medium text-gray-700">Business Actions</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input 
                                    type="number" 
                                    id="retention_business_action"
                                    dusk="retention-business-action"
                                    wire:model="retentionSettings.business_action"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md"
                                    min="1" 
                                    max="3650"
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">days</span>
                                </div>
                            </div>
                            @error('retentionSettings.business_action')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="retention_financial_transaction" class="block text-sm font-medium text-gray-700">Financial Transactions</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input 
                                    type="number" 
                                    id="retention_financial_transaction"
                                    dusk="retention-financial-transaction"
                                    wire:model="retentionSettings.financial_transaction"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md"
                                    min="1" 
                                    max="3650"
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">days</span>
                                </div>
                            </div>
                            @error('retentionSettings.financial_transaction')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Recommended: 2555 days (7 years) for compliance</p>
                        </div>

                        <div>
                            <label for="retention_system_event" class="block text-sm font-medium text-gray-700">System Events</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input 
                                    type="number" 
                                    id="retention_system_event"
                                    dusk="retention-system-event"
                                    wire:model="retentionSettings.system_event"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md"
                                    min="1" 
                                    max="3650"
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">days</span>
                                </div>
                            </div>
                            @error('retentionSettings.system_event')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="retention_security_event" class="block text-sm font-medium text-gray-700">Security Events</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input 
                                    type="number" 
                                    id="retention_security_event"
                                    dusk="retention-security-event"
                                    wire:model="retentionSettings.security_event"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md"
                                    min="1" 
                                    max="3650"
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">days</span>
                                </div>
                            </div>
                            @error('retentionSettings.security_event')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
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
                                wire:click="getCleanupPreview"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                dusk="preview-cleanup-button"
                                class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                            >
                                <span wire:loading.remove wire:target="getCleanupPreview">Preview Cleanup</span>
                                <span wire:loading wire:target="getCleanupPreview">Generating...</span>
                            </button>
                            <button 
                                wire:click="runCleanupNow"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                dusk="cleanup-now-button"
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
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
            @endif

            <!-- Alert Thresholds Tab -->
            @if ($activeTab === 'alerts')
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Security Alert Thresholds</h3>
                        <p class="text-sm text-gray-600 mb-6">Configure thresholds for automatic security alert generation.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label for="alert_failed_login" class="block text-sm font-medium text-gray-700">Failed Login Attempts</label>
                            <div class="mt-1">
                                <input 
                                    type="number" 
                                    id="alert_failed_login"
                                    dusk="alert-failed-login"
                                    wire:model="alertThresholds.failed_login_attempts"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    min="1" 
                                    max="100"
                                >
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Number of failed attempts before alert</p>
                            @error('alertThresholds.failed_login_attempts')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="alert_bulk_operation" class="block text-sm font-medium text-gray-700">Bulk Operation Threshold</label>
                            <div class="mt-1">
                                <input 
                                    type="number" 
                                    id="alert_bulk_operation"
                                    dusk="alert-bulk-operation"
                                    wire:model="alertThresholds.bulk_operation_threshold"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    min="1" 
                                    max="1000"
                                >
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Number of operations to trigger bulk alert</p>
                            @error('alertThresholds.bulk_operation_threshold')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="alert_suspicious_score" class="block text-sm font-medium text-gray-700">Suspicious Activity Score</label>
                            <div class="mt-1">
                                <input 
                                    type="number" 
                                    id="alert_suspicious_score"
                                    dusk="alert-suspicious-score"
                                    wire:model="alertThresholds.suspicious_activity_score"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    min="1" 
                                    max="100"
                                >
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Risk score threshold (1-100)</p>
                            @error('alertThresholds.suspicious_activity_score')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <button 
                            wire:click="saveAlertThresholds"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            dusk="save-alerts-button"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            <span wire:loading.remove wire:target="saveAlertThresholds">Save Alert Thresholds</span>
                            <span wire:loading wire:target="saveAlertThresholds">Saving...</span>
                        </button>
                    </div>

                    @error('alerts')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <!-- Notifications Tab -->
            @if ($activeTab === 'notifications')
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Notification Settings</h3>
                        <p class="text-sm text-gray-600 mb-6">Configure email notifications for security alerts and audit reports.</p>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-center">
                            <input 
                                id="security_alerts_enabled" 
                                type="checkbox" 
                                dusk="security-alerts-enabled"
                                wire:model="notificationSettings.security_alerts_enabled"
                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                            >
                            <label for="security_alerts_enabled" class="ml-2 block text-sm text-gray-900">
                                Enable security alert notifications
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Security Alert Recipients</label>
                            <div class="space-y-2">
                                @foreach($notificationSettings['security_alert_recipients'] as $index => $recipient)
                                    <div class="flex items-center space-x-2">
                                        <input 
                                            type="email" 
                                            dusk="recipient-{{ $index }}"
                                            wire:model="notificationSettings.security_alert_recipients.{{ $index }}"
                                            class="flex-1 focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                            placeholder="admin@example.com"
                                        >
                                        <button 
                                            wire:click="removeSecurityAlertRecipient({{ $index }})"
                                            dusk="remove-recipient-{{ $index }}"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>
                                    @error("notificationSettings.security_alert_recipients.{$index}")
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endforeach
                                
                                <button 
                                    wire:click="addSecurityAlertRecipient"
                                    dusk="add-recipient-button"
                                    class="text-blue-600 hover:text-blue-900 text-sm font-medium"
                                >
                                    + Add Recipient
                                </button>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input 
                                    id="daily_summary_enabled" 
                                    type="checkbox" 
                                    dusk="daily-summary-enabled"
                                    wire:model="notificationSettings.daily_summary_enabled"
                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                >
                                <label for="daily_summary_enabled" class="ml-2 block text-sm text-gray-900">
                                    Send daily audit summary reports
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input 
                                    id="weekly_report_enabled" 
                                    type="checkbox" 
                                    dusk="weekly-report-enabled"
                                    wire:model="notificationSettings.weekly_report_enabled"
                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                >
                                <label for="weekly_report_enabled" class="ml-2 block text-sm text-gray-900">
                                    Send weekly audit reports
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center space-x-4 pt-6 border-t border-gray-200">
                            <button 
                                wire:click="saveNotificationSettings"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                dusk="save-notifications-button"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                            >
                                <span wire:loading.remove wire:target="saveNotificationSettings">Save Notification Settings</span>
                                <span wire:loading wire:target="saveNotificationSettings">Saving...</span>
                            </button>
                            <button 
                                wire:click="testSecurityAlert"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                dusk="test-alert-button"
                                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                            >
                                <span wire:loading.remove wire:target="testSecurityAlert">Send Test Alert</span>
                                <span wire:loading wire:target="testSecurityAlert">Sending...</span>
                            </button>
                        </div>

                        @error('notifications')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endif

            <!-- Performance Tab -->
            @if ($activeTab === 'performance')
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Performance Settings</h3>
                        <p class="text-sm text-gray-600 mb-6">Configure audit system performance and processing options.</p>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-center">
                            <input 
                                id="async_processing" 
                                type="checkbox" 
                                dusk="async-processing"
                                wire:model="performanceSettings.async_processing"
                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                            >
                            <label for="async_processing" class="ml-2 block text-sm text-gray-900">
                                Enable asynchronous audit processing
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="batch_size" class="block text-sm font-medium text-gray-700">Batch Processing Size</label>
                                <div class="mt-1">
                                    <input 
                                        type="number" 
                                        id="batch_size"
                                        dusk="batch-size"
                                        wire:model="performanceSettings.batch_size"
                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                        min="1" 
                                        max="1000"
                                    >
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Number of audit entries to process in each batch</p>
                                @error('performanceSettings.batch_size')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="queue_connection" class="block text-sm font-medium text-gray-700">Queue Connection</label>
                                <div class="mt-1">
                                    <select 
                                        id="queue_connection"
                                        dusk="queue-connection"
                                        wire:model="performanceSettings.queue_connection"
                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    >
                                        <option value="default">Default</option>
                                        <option value="database">Database</option>
                                        <option value="redis">Redis</option>
                                        <option value="sync">Synchronous</option>
                                    </select>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Queue connection for audit processing</p>
                                @error('performanceSettings.queue_connection')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                            <button 
                                wire:click="savePerformanceSettings"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                dusk="save-performance-button"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                            >
                                <span wire:loading.remove wire:target="savePerformanceSettings">Save Performance Settings</span>
                                <span wire:loading wire:target="savePerformanceSettings">Saving...</span>
                            </button>
                        </div>

                        @error('performance')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endif

            <!-- Export Settings Tab -->
            @if ($activeTab === 'export')
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Export Settings</h3>
                        <p class="text-sm text-gray-600 mb-6">Configure audit log export options and limitations.</p>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label for="max_export_records" class="block text-sm font-medium text-gray-700">Maximum Export Records</label>
                            <div class="mt-1">
                                <input 
                                    type="number" 
                                    id="max_export_records"
                                    dusk="max-export-records"
                                    wire:model="exportSettings.max_export_records"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    min="100" 
                                    max="100000"
                                >
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Maximum number of records allowed in a single export</p>
                            @error('exportSettings.max_export_records')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Allowed Export Formats</label>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input 
                                        id="format_csv" 
                                        type="checkbox" 
                                        value="csv"
                                        dusk="format-csv"
                                        wire:model="exportSettings.allowed_formats"
                                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                    >
                                    <label for="format_csv" class="ml-2 block text-sm text-gray-900">
                                        CSV (Comma Separated Values)
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input 
                                        id="format_pdf" 
                                        type="checkbox" 
                                        value="pdf"
                                        dusk="format-pdf"
                                        wire:model="exportSettings.allowed_formats"
                                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                    >
                                    <label for="format_pdf" class="ml-2 block text-sm text-gray-900">
                                        PDF (Portable Document Format)
                                    </label>
                                </div>
                            </div>
                            @error('exportSettings.allowed_formats')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center">
                            <input 
                                id="include_sensitive_data" 
                                type="checkbox" 
                                dusk="include-sensitive-data"
                                wire:model="exportSettings.include_sensitive_data"
                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                            >
                            <label for="include_sensitive_data" class="ml-2 block text-sm text-gray-900">
                                Include sensitive data in exports (IP addresses, user agents)
                            </label>
                        </div>

                        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                            <button 
                                wire:click="saveExportSettings"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                dusk="save-export-button"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                            >
                                <span wire:loading.remove wire:target="saveExportSettings">Save Export Settings</span>
                                <span wire:loading wire:target="saveExportSettings">Saving...</span>
                            </button>
                        </div>

                        @error('export')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endif

            <!-- System Health Tab -->
            @if ($activeTab === 'health')
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Audit System Health Monitoring</h3>
                        <p class="text-sm text-gray-600 mb-6">Monitor the health and performance of the audit logging system.</p>
                    </div>

                    @if (isset($systemHealth['error']))
                        <div class="bg-red-50 border border-red-200 rounded-md p-4">
                            <p class="text-sm text-red-800">{{ $systemHealth['error'] }}</p>
                        </div>
                    @else
                        <!-- System Status -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-lg font-medium text-gray-900">System Status</h4>
                                <div class="flex items-center">
                                    <div class="h-3 w-3 rounded-full mr-2 {{ $systemHealth['system_status']['status'] === 'healthy' ? 'bg-green-400' : 'bg-yellow-400' }}"></div>
                                    <span class="text-sm font-medium {{ $systemHealth['system_status']['status'] === 'healthy' ? 'text-green-800' : 'text-yellow-800' }}">
                                        {{ $systemHealth['system_status']['message'] }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="text-2xl font-bold text-gray-900">{{ number_format($systemHealth['total_logs']) }}</div>
                                    <div class="text-sm text-gray-600">Total Audit Logs</div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="text-2xl font-bold text-gray-900">{{ number_format($systemHealth['logs_last_24h']) }}</div>
                                    <div class="text-sm text-gray-600">Logs (Last 24h)</div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="text-2xl font-bold text-gray-900">{{ $systemHealth['avg_logs_per_day'] }}</div>
                                    <div class="text-sm text-gray-600">Avg Logs/Day</div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="text-2xl font-bold text-gray-900">{{ $systemHealth['estimated_storage_mb'] }} MB</div>
                                    <div class="text-sm text-gray-600">Est. Storage Used</div>
                                </div>
                            </div>
                        </div>

                        <!-- Audit Statistics -->
                        @if (!isset($auditStatistics['error']))
                            <div class="bg-white border border-gray-200 rounded-lg p-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Audit Activity (Last 7 Days)</h4>
                                
                                @if (isset($auditStatistics['entries_by_type']))
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        @foreach($auditStatistics['entries_by_type'] as $type => $count)
                                            <div class="bg-gray-50 rounded-lg p-4">
                                                <div class="text-lg font-semibold text-gray-900">{{ number_format($count) }}</div>
                                                <div class="text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', $type)) }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- System Information -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">System Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-700">Oldest Log:</span>
                                    <span class="text-gray-600">{{ $systemHealth['oldest_log_date'] ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Newest Log:</span>
                                    <span class="text-gray-600">{{ $systemHealth['newest_log_date'] ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center space-x-4">
                        <button 
                            wire:click="loadSystemHealth"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            dusk="refresh-health-button"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            <span wire:loading.remove wire:target="loadSystemHealth">Refresh Health Data</span>
                            <span wire:loading wire:target="loadSystemHealth">Refreshing...</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>