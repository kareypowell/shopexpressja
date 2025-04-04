<?php

namespace App\Http\Livewire\Manifests\Packages;

use App\Models\User;
use Livewire\Component;

class CustomersSearchBar extends Component
{
    public $query;
    public $customers;
    public $highlightIndex;

    public function mount()
    {
        $this->resetFields();
    }

    public function resetFields()
    {
        $this->query = '';
        $this->customers = [];
        $this->highlightIndex = 0;
    }

    public function incrementHighlight()
    {
        if ($this->highlightIndex === count($this->customers) - 1) {
            $this->highlightIndex = 0;
            return;
        }
        $this->highlightIndex++;
    }

    public function decrementHighlight()
    {
        if ($this->highlightIndex === 0) {
            $this->highlightIndex = count($this->customers) - 1;
            return;
        }
        $this->highlightIndex--;
    }

    public function selectCustomer()
    {
        $customer = $this->customers[$this->highlightIndex] ?? null;
        if ($customer) {
            $this->redirect(route('show-customer', $customer['id']));
        }
    }

    public function updatedQuery()
    {
        $this->customers = User::with('profile')
                                ->where('first_name', 'like', '%' . $this->query . '%')
                                ->orWhere('last_name', 'like', '%' . $this->query . '%')
                                // ->where('profile.account_number', 'like', '%' . $this->query . '%')
                                // ->where('profile.tax_number', 'like', '%' . $this->query . '%')
                                ->get()
                                ->toArray();
    }

    public function render()
    {
        return view('livewire.manifests.packages.customers-search-bar');
    }
}
