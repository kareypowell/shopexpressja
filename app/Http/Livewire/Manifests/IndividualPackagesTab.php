<?php

namespace App\Http\Livewire\Manifests;

use App\Models\Manifest;
use Livewire\Component;

class IndividualPackagesTab extends Component
{
    public Manifest $manifest;
    
    protected $listeners = [
        'preserveTabState' => 'handlePreserveState',
        'tabSwitched' => 'handleTabSwitch'
    ];

    public function mount(Manifest $manifest)
    {
        $this->manifest = $manifest;
    }

    public function handlePreserveState($activeTab)
    {
        // Preserve component state when tab switches
        // This will be implemented in task 6
    }

    public function handleTabSwitch($tab)
    {
        // Handle tab switch events
        // This will be implemented in task 6
    }

    public function render()
    {
        return view('livewire.manifests.individual-packages-tab');
    }
}