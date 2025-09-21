<?php

namespace App\Http\Livewire\Admin;

use App\Models\AuditLog;
use Livewire\Component;
use Carbon\Carbon;

class AuditLogViewer extends Component
{
    public $auditLogId;
    public $auditLog;
    public $relatedLogs;
    public $showModal = false;
    public $activeTab = 'details';
    
    protected $listeners = ['showAuditLogDetails'];

    public function mount($auditLogId = null)
    {
        $this->relatedLogs = collect();
        
        if ($auditLogId) {
            $this->loadAuditLog($auditLogId);
        }
    }

    public function showAuditLogDetails($auditLogId)
    {
        $this->loadAuditLog($auditLogId);
        $this->showModal = true;
        $this->activeTab = 'details';
    }

    public function loadAuditLog($auditLogId)
    {
        $this->auditLogId = $auditLogId;
        
        $this->auditLog = AuditLog::with(['user'])
            ->find($auditLogId);
            
        if (!$this->auditLog) {
            $this->addError('auditLog', 'Audit log entry not found.');
            return;
        }

        $this->loadRelatedLogs();
    }

    protected function loadRelatedLogs()
    {
        if (!$this->auditLog) {
            return;
        }

        // Get related logs based on different criteria
        $relatedQueries = collect();

        // 1. Same user activity within 1 hour
        if ($this->auditLog->user_id) {
            $relatedQueries->push([
                'title' => 'Same User Activity',
                'logs' => AuditLog::with(['user'])
                    ->where('user_id', $this->auditLog->user_id)
                    ->where('id', '!=', $this->auditLog->id)
                    ->whereBetween('created_at', [
                        $this->auditLog->created_at->copy()->subHour(),
                        $this->auditLog->created_at->copy()->addHour()
                    ])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
            ]);
        }

        // 2. Same model/entity changes
        if ($this->auditLog->auditable_type && $this->auditLog->auditable_id) {
            $relatedQueries->push([
                'title' => 'Same Entity Changes',
                'logs' => AuditLog::with(['user'])
                    ->where('auditable_type', $this->auditLog->auditable_type)
                    ->where('auditable_id', $this->auditLog->auditable_id)
                    ->where('id', '!=', $this->auditLog->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
            ]);
        }

        // 3. Same IP address activity (security context)
        if ($this->auditLog->ip_address) {
            $relatedQueries->push([
                'title' => 'Same IP Activity',
                'logs' => AuditLog::with(['user'])
                    ->where('ip_address', $this->auditLog->ip_address)
                    ->where('id', '!=', $this->auditLog->id)
                    ->whereBetween('created_at', [
                        $this->auditLog->created_at->copy()->subHours(24),
                        $this->auditLog->created_at->copy()->addHours(24)
                    ])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
            ]);
        }

        // 4. Same session activity (if we have session data)
        if ($this->auditLog->additional_data && isset($this->auditLog->additional_data['session_id'])) {
            $sessionId = $this->auditLog->additional_data['session_id'];
            $relatedQueries->push([
                'title' => 'Same Session Activity',
                'logs' => AuditLog::with(['user'])
                    ->whereRaw("JSON_EXTRACT(additional_data, '$.session_id') = ?", [$sessionId])
                    ->where('id', '!=', $this->auditLog->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
            ]);
        }

        $this->relatedLogs = $relatedQueries->filter(function ($query) {
            return $query['logs']->isNotEmpty();
        });
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->auditLog = null;
        $this->relatedLogs = collect();
        $this->auditLogId = null;
    }

    public function getFormattedOldValuesProperty()
    {
        if (!$this->auditLog || !$this->auditLog->old_values) {
            return null;
        }

        return $this->formatJsonData($this->auditLog->old_values);
    }

    public function getFormattedNewValuesProperty()
    {
        if (!$this->auditLog || !$this->auditLog->new_values) {
            return null;
        }

        return $this->formatJsonData($this->auditLog->new_values);
    }

    public function getFormattedAdditionalDataProperty()
    {
        if (!$this->auditLog || !$this->auditLog->additional_data) {
            return null;
        }

        return $this->formatJsonData($this->auditLog->additional_data);
    }

    public function getValueChangesProperty()
    {
        if (!$this->auditLog || !$this->auditLog->old_values || !$this->auditLog->new_values) {
            return [];
        }

        $oldValues = $this->auditLog->old_values;
        $newValues = $this->auditLog->new_values;
        $changes = [];

        // Get all keys from both old and new values
        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($allKeys as $key) {
            $oldValue = $oldValues[$key] ?? null;
            $newValue = $newValues[$key] ?? null;

            // Skip if values are the same
            if ($oldValue === $newValue) {
                continue;
            }

            $changes[] = [
                'field' => $key,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'change_type' => $this->getChangeType($oldValue, $newValue)
            ];
        }

        return $changes;
    }

    protected function getChangeType($oldValue, $newValue)
    {
        if ($oldValue === null && $newValue !== null) {
            return 'added';
        }
        
        if ($oldValue !== null && $newValue === null) {
            return 'removed';
        }
        
        return 'modified';
    }

    protected function formatJsonData($data)
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            return $decoded ?: $data;
        }

        return $data;
    }

    public function formatValue($value)
    {
        if ($value === null) {
            return '<span class="text-gray-400 italic">null</span>';
        }

        if (is_bool($value)) {
            return $value ? '<span class="text-green-600 font-medium">true</span>' : '<span class="text-red-600 font-medium">false</span>';
        }

        if (is_array($value) || is_object($value)) {
            return '<pre class="text-xs bg-gray-100 p-2 rounded overflow-x-auto">' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
        }

        if (is_string($value) && strlen($value) > 100) {
            return '<div class="text-sm">' . htmlspecialchars(substr($value, 0, 100)) . '... <button class="text-blue-600 hover:text-blue-800 text-xs" onclick="this.previousSibling.textContent=\'' . htmlspecialchars($value) . '\'; this.style.display=\'none\';">Show More</button></div>';
        }

        return htmlspecialchars((string) $value);
    }

    public function getEventTypeColorProperty()
    {
        if (!$this->auditLog) {
            return 'bg-gray-100 text-gray-800';
        }

        switch ($this->auditLog->event_type) {
            case 'authentication':
                return 'bg-blue-100 text-blue-800';
            case 'authorization':
                return 'bg-purple-100 text-purple-800';
            case 'security_event':
                return 'bg-red-100 text-red-800';
            case 'model_created':
            case 'model_updated':
            case 'model_deleted':
            case 'model_restored':
                return 'bg-green-100 text-green-800';
            case 'business_action':
                return 'bg-yellow-100 text-yellow-800';
            case 'financial_transaction':
                return 'bg-indigo-100 text-indigo-800';
            case 'system_event':
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    public function getUserContextProperty()
    {
        if (!$this->auditLog) {
            return [
                'user' => null,
                'ip_address' => null,
                'user_agent' => null,
                'url' => null,
                'timestamp' => null,
            ];
        }

        $context = [
            'user' => $this->auditLog->user,
            'ip_address' => $this->auditLog->ip_address,
            'user_agent' => $this->auditLog->user_agent,
            'url' => $this->auditLog->url,
            'timestamp' => $this->auditLog->created_at,
        ];

        // Add session information if available
        if ($this->auditLog->additional_data && isset($this->auditLog->additional_data['session_id'])) {
            $context['session_id'] = $this->auditLog->additional_data['session_id'];
        }

        return $context;
    }

    public function render()
    {
        return view('livewire.admin.audit-log-viewer', [
            'formattedOldValues' => $this->formattedOldValues,
            'formattedNewValues' => $this->formattedNewValues,
            'formattedAdditionalData' => $this->formattedAdditionalData,
            'valueChanges' => $this->valueChanges,
            'eventTypeColor' => $this->eventTypeColor,
            'userContext' => $this->userContext,
        ]);
    }
}