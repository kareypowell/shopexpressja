<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Services\ReportMonitoringService;
use App\Services\ReportErrorHandlingService;

class ReportErrorHandler extends Component
{
    public $errorMessage = '';
    public $errorId = '';
    public $showRetry = false;
    public $showSupport = false;
    public $fallbackData = null;
    public $isVisible = false;

    protected $listeners = [
        'reportError' => 'handleReportError',
        'clearError' => 'clearError'
    ];

    /**
     * Handle report error display
     */
    public function handleReportError($errorData)
    {
        $this->errorMessage = $errorData['message'] ?? 'An error occurred while generating the report.';
        $this->errorId = $errorData['error_id'] ?? '';
        $this->showRetry = $errorData['retry_suggested'] ?? false;
        $this->showSupport = $errorData['contact_support'] ?? false;
        $this->fallbackData = $errorData['fallback_data'] ?? null;
        $this->isVisible = true;

        // Auto-hide after 10 seconds if it's a minor error
        if (!$this->showSupport) {
            $this->dispatchBrowserEvent('auto-hide-error', ['delay' => 10000]);
        }
    }

    /**
     * Clear the error display
     */
    public function clearError()
    {
        $this->reset(['errorMessage', 'errorId', 'showRetry', 'showSupport', 'fallbackData', 'isVisible']);
    }

    /**
     * Retry the failed operation
     */
    public function retryOperation()
    {
        $this->clearError();
        $this->emit('retryReportGeneration');
    }

    /**
     * Show fallback data if available
     */
    public function showFallbackData()
    {
        if ($this->fallbackData) {
            $this->emit('showFallbackData', $this->fallbackData);
            $this->clearError();
        }
    }

    /**
     * Contact support action
     */
    public function contactSupport()
    {
        // Emit event to show support modal or redirect
        $this->emit('showSupportModal', [
            'error_id' => $this->errorId,
            'message' => $this->errorMessage
        ]);
    }

    public function render()
    {
        return view('livewire.report-error-handler');
    }
}