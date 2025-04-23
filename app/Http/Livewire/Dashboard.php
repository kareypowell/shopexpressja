<?php

namespace App\Http\Livewire;

use App\Models\Package;
use Livewire\Component;
use Illuminate\Database\Eloquent\Builder;

class Dashboard extends Component
{
    public int $inComingAir = 0;
    public int $inComingSea = 0;
    public int $availableAir = 0;
    public int $availableSea = 0;
    public float $accountBalance = 0;
    public int $delayedPackages = 0;

    public function mount()
    {
        $this->inComingAir = Package::whereHas('manifest', function (Builder $query) {
                                        $query->where('type', 'air');
                                    })
                                    ->where('user_id', auth()->id())
                                    ->whereIn('status', ['processing', 'shipped'])
                                    ->count();

        $this->inComingSea = Package::whereHas('manifest', function (Builder $query) {
                                        $query->where('type', 'sea');
                                    })
                                    ->where('user_id', auth()->id())
                                    ->whereIn('status', ['processing', 'shipped'])
                                    ->count();

        $this->availableAir = Package::whereHas('manifest', function (Builder $query) {
                                        $query->where('type', 'air');
                                    })
                                    ->where('user_id', auth()->id())
                                    ->whereIn('status', ['ready'])
                                    ->count();

        $this->availableSea = Package::whereHas('manifest', function (Builder $query) {
                                        $query->where('type', 'sea');
                                    })
                                    ->where('user_id', auth()->id())
                                    ->whereIn('status', ['ready'])
                                    ->count();

        $this->accountBalance = Package::where('user_id', auth()->id())
                                        ->whereIn('status', ['ready'])
                                        ->sum('freight_price');

        $this->delayedPackages = Package::where('user_id', auth()->id())
                                        ->where('status', 'delayed')->count();
    }

    public function render()
    {
        if (auth()->user()->role_id == 1) {
            return view('livewire.admin-dashboard');
        } else {
            return view('livewire.dashboard');
        }
    }
}
