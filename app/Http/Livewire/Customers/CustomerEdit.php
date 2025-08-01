<?php

namespace App\Http\Livewire\Customers;

use App\Models\User;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;

class CustomerEdit extends Component
{
    use AuthorizesRequests;

    public User $customer;
    public $offices;
    
    // User fields
    public $firstName;
    public $lastName;
    public $email;
    
    // Profile fields
    public $telephoneNumber;
    public $taxNumber;
    public $streetAddress;
    public $cityTown;
    public $parish;
    public $country;
    public $pickupLocation;

    protected function rules()
    {
        return [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->customer->id)
            ],
            'telephoneNumber' => 'required|string|max:20',
            'taxNumber' => 'nullable|string|max:20',
            'streetAddress' => 'required|string|max:500',
            'cityTown' => 'required|string|max:100',
            'parish' => 'required|string|max:50',
            'country' => 'required|string|max:50',
            'pickupLocation' => 'required|integer|exists:offices,id',
        ];
    }

    protected $validationAttributes = [
        'firstName' => 'first name',
        'lastName' => 'last name',
        'telephoneNumber' => 'telephone number',
        'taxNumber' => 'tax number',
        'streetAddress' => 'street address',
        'cityTown' => 'city/town',
        'pickupLocation' => 'pickup location',
    ];

    public function mount(User $customer)
    {
        // Use customer-specific authorization
        $this->authorize('customer.update', $customer);
        
        $this->customer = $customer->load('profile');
        $this->offices = \App\Models\Office::all();
        
        // Populate form fields with existing data
        $this->firstName = $customer->first_name;
        $this->lastName = $customer->last_name;
        $this->email = $customer->email;
        
        if ($customer->profile) {
            $this->telephoneNumber = $customer->profile->telephone_number;
            $this->taxNumber = $customer->profile->tax_number;
            $this->streetAddress = $customer->profile->street_address;
            $this->cityTown = $customer->profile->city_town;
            $this->parish = $customer->profile->parish;
            $this->country = $customer->profile->country;
            $this->pickupLocation = $customer->profile->pickup_location;
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        // Re-check authorization before saving
        $this->authorize('customer.update', $this->customer);
        
        $this->validate();

        try {
            // Update user fields
            $this->customer->update([
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email,
            ]);

            // Update or create profile
            $this->customer->profile()->updateOrCreate(
                ['user_id' => $this->customer->id],
                [
                    'telephone_number' => $this->telephoneNumber,
                    'tax_number' => $this->taxNumber ?: null,
                    'street_address' => $this->streetAddress,
                    'city_town' => $this->cityTown,
                    'parish' => $this->parish,
                    'country' => $this->country,
                    'pickup_location' => $this->pickupLocation,
                ]
            );

            session()->flash('success', 'Customer information updated successfully.');
            
            // Redirect based on user role
            if (auth()->user()->isSuperAdmin()) {
                return redirect()->route('customers');
            } else {
                return redirect()->route('admin.customers.index');
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while updating customer information. Please try again.');
            
            // Log the error for debugging
            \Log::error('Customer update failed: ' . $e->getMessage(), [
                'customer_id' => $this->customer->id,
                'user_id' => auth()->id()
            ]);
        }
    }

    public function cancel()
    {
        // Redirect based on user role
        if (auth()->user()->isSuperAdmin()) {
            return redirect()->route('customers');
        } else {
            return redirect()->route('admin.customers.index');
        }
    }

    public function render()
    {
        return view('livewire.customers.customer-edit');
    }
}