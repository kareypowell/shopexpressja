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

        $total = Package::where('user_id', auth()->id())
                                        ->whereIn('status', ['ready'])
                                        ->selectRaw('SUM(freight_price + customs_duty + storage_fee + delivery_fee) as total')
                                        ->value('total');
        $this->accountBalance = $total ? $total : 0;

        $this->delayedPackages = Package::where('user_id', auth()->id())
                                        ->where('status', 'delayed')->count();
    }

    public function render()
    {
        if (auth()->user()->isSuperAdmin()) {
            return view('livewire.admin-dashboard');
        } else {
            return view('livewire.dashboard');
        }
    }
}
