<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\ConsolidatedPackage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ConsolidationToggle extends Component
{
    use AuthorizesRequests;

    public $consolidationMode = false;

    public function mount()
    {
        // Check if user has permission to use consolidation features
        if (!$this->authorize('create', ConsolidatedPackage::class)) {
            $this->consolidationMode = false;
            return;
        }

        // Initialize consolidation mode from session
        $this->consolidationMode = session('consolidation_mode', false);
    }

    public function toggleConsolidationMode()
    {
        // Check if user has permission to toggle consolidation mode
        try {
            $this->authorize('create', ConsolidatedPackage::class);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->dispatchBrowserEvent('show-error', [
                'message' => 'You do not have permission to use consolidation features.'
            ]);
            return;
        }

        $this->consolidationMode = !$this->consolidationMode;
        
        // Persist the state in session
        session(['consolidation_mode' => $this->consolidationMode]);
        
        // Emit event to notify other components
        $this->emit('consolidationModeChanged', $this->consolidationMode);
        
        // Show feedback message
        $message = $this->consolidationMode 
            ? 'Consolidation mode enabled' 
            : 'Consolidation mode disabled';
            
        $this->dispatchBrowserEvent('show-message', ['message' => $message]);
    }

    /**
     * Check if current user can use consolidation features
     */
    public function getCanUseConsolidationProperty()
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin());
    }

    public function render()
    {
        return view('livewire.consolidation-toggle');
    }
}