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
    public float $creditBalance = 0;
    public float $totalAvailableBalance = 0;
    public float $pendingPackageCharges = 0;
    public float $totalAmountNeeded = 0;
    public int $delayedPackages = 0;

    public function mount()
    {
        $this->inComingAir = Package::whereHas('manifest', function (Builder $query) {
                                        $query->where('type', 'air');
                                    })
                                    ->where('user_id', auth()->id())
                                    ->whereIn('status', ['processing', 'shipped', 'customs'])
                                    ->count();

        $this->inComingSea = Package::whereHas('manifest', function (Builder $query) {
                                        $query->where('type', 'sea');
                                    })
                                    ->where('user_id', auth()->id())
                                    ->whereIn('status', ['processing', 'shipped', 'customs'])
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

        // Get user account balance information
        $user = auth()->user();
        $this->accountBalance = $user->account_balance ?? 0.0;
        $this->creditBalance = $user->credit_balance ?? 0.0;
        $this->totalAvailableBalance = $user->total_available_balance ?? 0.0;
        $this->pendingPackageCharges = $user->pending_package_charges ?? 0.0;
        $this->totalAmountNeeded = $user->total_amount_needed ?? 0.0;

        $this->delayedPackages = Package::where('user_id', auth()->id())
                                        ->where('status', 'delayed')->count();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
