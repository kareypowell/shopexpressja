<?php

namespace App\Http\Livewire\Customers;

use Livewire\Component;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PackageHistory extends Component
{
    use AuthorizesRequests;

    public User $customer;

    public function mount(User $customer)
    {
        $this->customer = $customer;
        $this->authorize('customer.view', $customer);
    }

    public function getPackageStatsProperty()
    {
        $packages = $this->customer->packages;
        
        return [
            'total_packages' => $packages->count(),
            'total_spent' => $packages->sum(function($package) {
                return $package->total_cost;
            }),
            'average_cost' => $packages->count() > 0 ? 
                $packages->sum(function($package) {
                    return $package->total_cost;
                }) / $packages->count() : 0,
            'status_breakdown' => $packages->groupBy(function($package) {
                return $package->status->value;
            })->map->count(),
        ];
    }

    public function render()
    {
        return view('livewire.customers.package-history', [
            'packageStats' => $this->packageStats,
        ]);
    }
}