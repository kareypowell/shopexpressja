<?php

namespace App\Http\Livewire;

use Livewire\Component;

class Package extends Component
{
    public int $inComingAir = 0;
    public int $inComingSea = 0;
    public int $availableAir = 0;
    public int $availableSea = 0;
    public float $accountBalance = 0;
    
    public function render()
    {
        return view('livewire.packages.package');
    }
}
