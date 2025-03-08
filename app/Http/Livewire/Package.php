<?php

namespace App\Http\Livewire;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\User;
use Livewire\Component;

class Package extends Component
{
    // use AuthorizesRequests;

    public int $inComingAir = 0;
    public int $inComingSea = 0;
    public int $availableAir = 0;
    public int $availableSea = 0;
    public float $accountBalance = 0;

    // public function mount()
    // {
    //     // Check authorization when component is mounted
    //     $this->authorize('viewAny', User::class);
    // }
    
    public function render()
    {
        return view('livewire.packages.package');
    }
}
