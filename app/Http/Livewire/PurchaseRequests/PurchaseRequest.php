<?php

namespace App\Http\Livewire\PurchaseRequests;

use Livewire\Component;
use App\Models\PurchaseRequest as PurchaseRequestModel;

class PurchaseRequest extends Component
{
    public bool $isOpen = false;
    public string $item_name = '';
    public string $item_url = '';
    public $quantity = '';
    public $unit_price = '';
    public $shipping_fee = '';
    public $tax = '';

    public function mount() {
        $this->quantity = 0;
        $this->unit_price = 0.0;
        $this->shipping_fee = 0.0;
        $this->tax = 0.0;
    }
    
    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function openModal()
    {
        $this->isOpen = true;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function closeModal()
    {
        $this->isOpen = false;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    private function resetInputFields()
    {
        $this->item_name = '';
        $this->item_url = '';
        $this->quantity = 0;
        $this->unit_price = 0.0;
        $this->shipping_fee = 0.0;
        $this->tax = 0.0;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function store()
    {
        $this->validate([
            'item_name' => 'required',
            'item_url' => 'required|url',
            'quantity' => 'required|numeric|min:1',
            'unit_price' => 'required',
        ]);

        $purchase_request = PurchaseRequestModel::create([
            'user_id' => auth()->id(),
            'item_name' => $this->item_name,
            'item_url' => $this->item_url,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'shipping_fee' => $this->shipping_fee,
            'tax' => $this->tax,
            'total_price' => $this->calculateFinalPrice($this->unit_price, $this->quantity, $this->shipping_fee, $this->tax),
        ]);

        if ($purchase_request) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Purchase Request Created Successfully.',
            ]);
        } else {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Purchase Request Creation Failed.',
            ]);
        }

        $this->closeModal();
        $this->resetInputFields();

        return redirect('/purchase-requests');
    }

    public function render()
    {
        return view('livewire.purchase-requests.purchase-request');
    }

    // create function to calculate the final price by accepting the requisite parameters
    public function calculateFinalPrice($unit_price, $quantity, $shipping_fee, $tax): float
    {
        return (floatval($unit_price) * floatval($quantity)) + floatval($shipping_fee) + floatval($tax);
    }
}
