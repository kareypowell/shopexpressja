<?php

namespace App\Http\Livewire\Customers;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;

class CustomerPackages extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $typeFilter = 'all';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'typeFilter' => ['except' => 'all'],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->typeFilter = 'all';
        $this->resetPage();
    }

    public function getPackagesProperty()
    {
        $query = Package::with(['shipper', 'office', 'manifest'])
            ->where('user_id', auth()->id());

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('tracking_number', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Status filter
        if ($this->statusFilter !== 'all') {
            switch ($this->statusFilter) {
                case 'in-transit':
                    $query->whereIn('status', ['processing', 'shipped', 'customs']);
                    break;
                case 'ready':
                    $query->where('status', 'ready');
                    break;
                case 'delivered':
                    $query->where('status', 'delivered');
                    break;
                case 'delayed':
                    $query->where('status', 'delayed');
                    break;
            }
        }

        // Type filter
        if ($this->typeFilter !== 'all') {
            $query->whereHas('manifest', function (Builder $q) {
                $q->where('type', $this->typeFilter);
            });
        }

        // Sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(15);
    }

    public function render()
    {
        return view('livewire.customers.customer-packages', [
            'packages' => $this->packages
        ])->layout('layouts.app');
    }
}