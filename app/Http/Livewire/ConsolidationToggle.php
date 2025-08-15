<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ConsolidationToggle extends Component
{
    public $consolidationMode = false;

    public function mount()
    {
        // Initialize consolidation mode from session
        $this->consolidationMode = session('consolidation_mode', false);
    }

    public function toggleConsolidationMode()
    {
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

    public function render()
    {
        return view('livewire.consolidation-toggle');
    }
}