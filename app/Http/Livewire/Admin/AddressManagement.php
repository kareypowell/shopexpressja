<?php

namespace App\Http\Livewire\Admin;

use App\Models\Address;
use Livewire\Component;
use Livewire\WithPagination;

class AddressManagement extends Component
{
    use WithPagination;

    public $searchTerm = '';
    public $showDeleteModal = false;
    public $selectedAddress = null;

    protected $paginationTheme = 'tailwind';

    protected $listeners = ['refreshAddresses' => '$refresh'];

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function confirmDelete($addressId)
    {
        $this->selectedAddress = Address::find($addressId);
        $this->showDeleteModal = true;
    }

    public function deleteAddress()
    {
        if ($this->selectedAddress) {
            try {
                $this->selectedAddress->delete();
                session()->flash('success', 'Address deleted successfully.');
                $this->emit('refreshAddresses');
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to delete address. Please try again.');
            }
        }

        $this->cancelDelete();
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->selectedAddress = null;
    }

    public function render()
    {
        $addresses = Address::query()
            ->when($this->searchTerm, function ($query) {
                $query->search($this->searchTerm);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.address-management', [
            'addresses' => $addresses,
        ]);
    }
}