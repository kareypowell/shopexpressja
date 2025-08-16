<?php

namespace App\Http\Livewire\Manifests;

use App\Models\Manifest;
use Livewire\Component;

class ManifestTabsContainer extends Component
{
    public Manifest $manifest;
    public string $activeTab = 'consolidated';
    
    protected $queryString = ['activeTab' => ['except' => 'consolidated']];
    
    protected $listeners = [
        'tabStateChanged' => 'handleTabStateChange',
        'preserveState' => 'preserveTabState'
    ];

    public function mount(Manifest $manifest, string $activeTab = 'consolidated')
    {
        $this->manifest = $manifest;
        $this->activeTab = $this->validateTab($activeTab);
        
        // Try to restore tab state from session if no specific tab was requested
        if ($activeTab === 'consolidated') {
            $this->restoreTabState();
        }
        
        $this->preserveTabState();
    }

    public function switchTab(string $tabName)
    {
        $validatedTab = $this->validateTab($tabName);
        
        if ($validatedTab !== $this->activeTab) {
            $this->activeTab = $validatedTab;
            
            // Preserve new tab state after switching
            $this->preserveTabState();
            $this->updateUrl();
            
            // Emit event to notify other components
            $this->emit('tabSwitched', $this->activeTab);
            $this->emit('preserveTabState', $this->activeTab);
            
            // Update browser history
            $this->dispatchBrowserEvent('tab-switched', [
                'tab' => $this->activeTab,
                'manifestId' => $this->manifest->id
            ]);
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
        // Store tab state in session for persistence
        $sessionKey = "manifest_tabs_{$this->manifest->id}";
        
        $tabState = [
            'activeTab' => $this->activeTab,
            'timestamp' => now()->timestamp,
            'manifestId' => $this->manifest->id
        ];
        
        session()->put($sessionKey, $tabState);
        
        // Also emit event for child components to preserve their state
        $this->emit('preserveTabState', $this->activeTab);
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
        $sessionKey = "manifest_tabs_{$this->manifest->id}";
        $tabState = session()->get($sessionKey);
        
        if ($tabState && isset($tabState['activeTab'])) {
            // Only restore if the state is recent (within last hour)
            $stateAge = now()->timestamp - ($tabState['timestamp'] ?? 0);
            if ($stateAge < 3600) {
                $this->activeTab = $this->validateTab($tabState['activeTab']);
            }
        }
    }

    public function getTabsProperty()
    {
        // Get consolidated packages through packages that have consolidated_package_id
        $consolidatedPackagesCount = $this->manifest->packages()
            ->whereNotNull('consolidated_package_id')
            ->distinct('consolidated_package_id')
            ->count('consolidated_package_id');
            
        // Get individual packages (packages without consolidated_package_id)
        $individualPackagesCount = $this->manifest->packages()
            ->whereNull('consolidated_package_id')
            ->count();
        
        return [
            'consolidated' => [
                'name' => 'Consolidated Packages',
                'icon' => 'archive-box',
                'count' => $consolidatedPackagesCount,
                'aria_label' => 'View consolidated packages for this manifest'
            ],
            'individual' => [
                'name' => 'Individual Packages',
                'icon' => 'cube',
                'count' => $individualPackagesCount,
                'aria_label' => 'View individual packages for this manifest'
            ]
        ];
    }

    public function getActiveTabDataProperty()
    {
        $tabs = $this->tabs;
        return $tabs[$this->activeTab] ?? $tabs['consolidated'];
    }

    protected function validateTab(string $tab): string
    {
        $validTabs = ['consolidated', 'individual'];
        return in_array($tab, $validTabs) ? $tab : 'consolidated';
    }

    public function render()
    {
        return view('livewire.manifests.manifest-tabs-container', [
            'tabs' => $this->tabs,
            'activeTabData' => $this->activeTabData
        ]);
    }
}