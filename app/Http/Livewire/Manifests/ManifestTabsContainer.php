<?php

namespace App\Http\Livewire\Manifests;

use App\Models\Manifest;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ManifestTabsContainer extends Component
{
    public Manifest $manifest;
    public string $activeTab = 'individual';
    public bool $isLoading = false;
    public bool $hasError = false;
    public string $errorMessage = '';
    
    protected $queryString = ['activeTab' => ['except' => 'individual']];
    
    protected $listeners = [
        'tabStateChanged' => 'handleTabStateChange',
        'preserveState' => 'preserveTabState'
    ];

    public function mount(Manifest $manifest, string $activeTab = 'individual')
    {
        try {
            $this->manifest = $manifest;
            $this->activeTab = $this->validateTab($activeTab);
            
            // Try to restore tab state from session if no specific tab was requested
            if ($activeTab === 'individual') {
                $this->restoreTabState();
            }
            
            $this->preserveTabState();
            $this->clearError();
        } catch (\Exception $e) {
            $this->handleError('Failed to initialize tab container', $e);
        }
    }

    public function switchTab(string $tabName)
    {
        try {
            // Validate CSRF token for tab switching
            $this->validateTabSwitchRequest($tabName);
            
            $validatedTab = $this->validateTab($tabName);
            
            if ($validatedTab !== $this->activeTab) {
                $this->isLoading = true;
                $this->clearError();
                
                $this->activeTab = $validatedTab;
                
                // Preserve new tab state after switching
                $this->preserveTabState();
                $this->updateUrl();
                
                // Emit events to notify child components to refresh
                $this->emit('tabSwitched', $this->activeTab);
                $this->emit('refreshTabContent', $this->activeTab);
                $this->emit('preserveTabState', $this->activeTab);
                
                // Emit specific events for each tab component
                if ($this->activeTab === 'individual') {
                    $this->emit('refreshIndividualPackages');
                } else if ($this->activeTab === 'consolidated') {
                    $this->emit('refreshConsolidatedPackages');
                }
                
                // Update browser history
                $this->dispatchBrowserEvent('tab-switched', [
                    'tab' => $this->activeTab,
                    'manifestId' => $this->manifest->id
                ]);
                
                // Force re-render of the component
                $this->render();
                
                $this->isLoading = false;
            }
        } catch (ValidationException $e) {
            $this->handleValidationError('Invalid tab switch request', $e);
        } catch (\Exception $e) {
            $this->handleError('Failed to switch tabs', $e);
        }
    }

    public function updateUrl()
    {
        // Update the URL to reflect current tab state
        $this->dispatchBrowserEvent('update-url', [
            'tab' => $this->activeTab,
            'manifestId' => $this->manifest->id
        ]);
    }

    public function preserveTabState()
    {
        try {
            // Store tab state in session for persistence
            $sessionKey = "manifest_tabs_{$this->manifest->id}";
            
            $tabState = [
                'activeTab' => $this->activeTab,
                'timestamp' => now()->timestamp,
                'manifestId' => $this->manifest->id,
                'checksum' => $this->generateStateChecksum($this->activeTab, $this->manifest->id)
            ];
            
            session()->put($sessionKey, $tabState);
            
            // Also emit event for child components to preserve their state
            $this->emit('preserveTabState', $this->activeTab);
        } catch (\Exception $e) {
            Log::warning('Failed to preserve tab state', [
                'manifest_id' => $this->manifest->id,
                'active_tab' => $this->activeTab,
                'error' => $e->getMessage()
            ]);
            
            // Continue execution - state preservation failure shouldn't break functionality
        }
    }

    public function handleTabStateChange($data)
    {
        // Handle tab state changes from child components
        if (isset($data['tab']) && $data['tab'] !== $this->activeTab) {
            $this->switchTab($data['tab']);
        }
    }

    public function restoreTabState()
    {
        try {
            $sessionKey = "manifest_tabs_{$this->manifest->id}";
            $tabState = session()->get($sessionKey);
            
            if ($tabState && isset($tabState['activeTab'])) {
                // Validate state integrity
                if ($this->validateSessionState($tabState)) {
                    // Only restore if the state is recent (within last hour)
                    $stateAge = now()->timestamp - ($tabState['timestamp'] ?? 0);
                    if ($stateAge < 3600) {
                        $this->activeTab = $this->validateTab($tabState['activeTab']);
                    } else {
                        // Clear expired state
                        session()->forget($sessionKey);
                    }
                } else {
                    // Clear corrupted state
                    session()->forget($sessionKey);
                    Log::warning('Corrupted tab state detected and cleared', [
                        'manifest_id' => $this->manifest->id,
                        'session_key' => $sessionKey
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to restore tab state', [
                'manifest_id' => $this->manifest->id,
                'error' => $e->getMessage()
            ]);
            
            // Default to individual tab on restore failure
            $this->activeTab = 'individual';
        }
    }

    public function getTabsProperty()
    {
        // Get individual packages (packages without consolidated_package_id)
        $individualPackagesCount = $this->manifest->packages()
            ->whereNull('consolidated_package_id')
            ->count();
            
        // Get consolidated packages through packages that have consolidated_package_id
        $consolidatedPackagesCount = $this->manifest->packages()
            ->whereNotNull('consolidated_package_id')
            ->distinct('consolidated_package_id')
            ->count('consolidated_package_id');
        
        return [
            'individual' => [
                'name' => 'Individual Packages',
                'icon' => 'cube',
                'count' => $individualPackagesCount,
                'aria_label' => 'View individual packages for this manifest'
            ],
            'consolidated' => [
                'name' => 'Consolidated Packages',
                'icon' => 'archive-box',
                'count' => $consolidatedPackagesCount,
                'aria_label' => 'View consolidated packages for this manifest'
            ]
        ];
    }

    public function getActiveTabDataProperty()
    {
        $tabs = $this->tabs;
        return $tabs[$this->activeTab] ?? $tabs['individual'];
    }

    protected function validateTab(string $tab): string
    {
        $validTabs = ['consolidated', 'individual'];
        
        // Sanitize input
        $tab = trim(strtolower($tab));
        
        // Validate against allowed tabs
        if (!in_array($tab, $validTabs)) {
            Log::info('Invalid tab name provided, defaulting to individual', [
                'provided_tab' => $tab,
                'manifest_id' => $this->manifest->id ?? null
            ]);
            return 'individual';
        }
        
        return $tab;
    }

    /**
     * Validate tab switch request for security
     */
    protected function validateTabSwitchRequest(string $tabName): void
    {
        // Basic input validation
        if (empty($tabName) || strlen($tabName) > 20) {
            throw ValidationException::withMessages([
                'tab' => 'Invalid tab name provided'
            ]);
        }

        // Check for suspicious patterns
        if (preg_match('/[<>"\']/', $tabName)) {
            throw ValidationException::withMessages([
                'tab' => 'Tab name contains invalid characters'
            ]);
        }
    }

    /**
     * Validate session state integrity
     */
    protected function validateSessionState(array $tabState): bool
    {
        // Check required fields
        $requiredFields = ['activeTab', 'timestamp', 'manifestId'];
        foreach ($requiredFields as $field) {
            if (!isset($tabState[$field])) {
                return false;
            }
        }

        // Validate manifest ID matches
        if ($tabState['manifestId'] !== $this->manifest->id) {
            return false;
        }

        // Validate checksum if present
        if (isset($tabState['checksum'])) {
            $expectedChecksum = $this->generateStateChecksum(
                $tabState['activeTab'], 
                $tabState['manifestId']
            );
            if ($tabState['checksum'] !== $expectedChecksum) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate checksum for state validation
     */
    protected function generateStateChecksum(string $activeTab, int $manifestId): string
    {
        return hash('sha256', $activeTab . '|' . $manifestId . '|' . config('app.key'));
    }

    /**
     * Handle general errors
     */
    protected function handleError(string $message, \Exception $e): void
    {
        $this->hasError = true;
        $this->errorMessage = 'An error occurred. Please refresh the page.';
        $this->isLoading = false;

        Log::error($message, [
            'manifest_id' => $this->manifest->id ?? null,
            'active_tab' => $this->activeTab,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        $this->dispatchBrowserEvent('toastr:error', [
            'message' => $this->errorMessage
        ]);
    }

    /**
     * Handle validation errors
     */
    protected function handleValidationError(string $message, ValidationException $e): void
    {
        $this->hasError = true;
        $this->errorMessage = 'Invalid request. Please try again.';
        $this->isLoading = false;

        Log::warning($message, [
            'manifest_id' => $this->manifest->id ?? null,
            'active_tab' => $this->activeTab,
            'validation_errors' => $e->errors()
        ]);

        $this->dispatchBrowserEvent('toastr:warning', [
            'message' => $this->errorMessage
        ]);
    }

    /**
     * Clear error state
     */
    protected function clearError(): void
    {
        $this->hasError = false;
        $this->errorMessage = '';
    }

    public function render()
    {
        return view('livewire.manifests.manifest-tabs-container', [
            'tabs' => $this->tabs,
            'activeTabData' => $this->activeTabData
        ]);
    }
}