<?php

namespace App\Http\Livewire\Customers;

use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Services\AccountNumberService;
use App\Mail\WelcomeUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Registered;
use Livewire\Component;

class CustomerCreate extends Component
{
    // Customer basic information
    public $firstName = '';
    public $lastName = '';
    public $email = '';
    public $password = '';
    public $passwordConfirmation = '';
    
    // Profile information
    public $telephoneNumber = '';
    public $taxNumber = '';
    public $streetAddress = '';
    public $cityTown = '';
    public $parish = '';
    public $country = 'Jamaica';
    public $pickupLocation = '';
    
    // Options
    public $sendWelcomeEmail = true;
    public $generatePassword = true;
    
    // UI state
    public $isCreating = false;

    protected $rules = [
        'firstName' => 'required|string|max:255',
        'lastName' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required_if:generatePassword,false|min:8|same:passwordConfirmation',
        'telephoneNumber' => 'required|string|max:20',
        'taxNumber' => 'nullable|string|max:20',
        'streetAddress' => 'required|string|max:500',
        'cityTown' => 'required|string|max:100',
        'parish' => 'required|string|max:50',
        'country' => 'required|string|max:50',
        'pickupLocation' => 'required|string|max:100',
    ];

    protected $validationAttributes = [
        'firstName' => 'first name',
        'lastName' => 'last name',
        'telephoneNumber' => 'telephone number',
        'taxNumber' => 'tax number',
        'streetAddress' => 'street address',
        'cityTown' => 'city/town',
        'pickupLocation' => 'pickup location',
        'passwordConfirmation' => 'password confirmation',
    ];

    public function mount()
    {
        // Generate a random password by default
        if ($this->generatePassword) {
            $this->password = $this->generateRandomPassword();
            $this->passwordConfirmation = $this->password;
        }
    }

    public function updatedGeneratePassword()
    {
        if ($this->generatePassword) {
            $this->password = $this->generateRandomPassword();
            $this->passwordConfirmation = $this->password;
        } else {
            $this->password = '';
            $this->passwordConfirmation = '';
        }
    }

    public function create()
    {
        $this->isCreating = true;

        try {
            // Validate the form data
            $this->validate();

            DB::beginTransaction();

            // Get customer role
            $customerRole = Role::where('name', 'customer')->first();
            if (!$customerRole) {
                throw new \Exception('Customer role not found in the system.');
            }

            // Create the user
            $user = User::create([
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role_id' => $customerRole->id,
            ]);

            // Generate account number
            $accountNumberService = new AccountNumberService();
            $accountNumber = $accountNumberService->generate();

            // Create the profile
            $user->profile()->create([
                'account_number' => $accountNumber,
                'tax_number' => $this->taxNumber,
                'telephone_number' => $this->telephoneNumber,
                'street_address' => $this->streetAddress,
                'city_town' => $this->cityTown,
                'parish' => $this->parish,
                'country' => $this->country,
                'pickup_location' => $this->pickupLocation,
            ]);

            // Send welcome email if requested
            if ($this->sendWelcomeEmail) {
                $this->sendWelcomeEmailToCustomer($user);
            }

            // Fire registered event
            event(new Registered($user));

            DB::commit();

            // Show success message
            session()->flash('success', "Customer {$user->full_name} has been created successfully with account number {$accountNumber}.");

            // Redirect to customer profile
            return redirect()->route('admin.customers.profile', $user);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            $this->isCreating = false;
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->isCreating = false;
            session()->flash('error', 'Failed to create customer: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return redirect()->route('admin.customers');
    }

    /**
     * Send welcome email to the newly created customer.
     *
     * @param User $user
     * @return void
     */
    private function sendWelcomeEmailToCustomer(User $user): void
    {
        try {
            Mail::to($user->email)->send(new WelcomeUser($user->first_name));
        } catch (\Exception $e) {
            // Log the error but don't fail the customer creation
            \Log::error('Failed to send welcome email to customer: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            
            session()->flash('warning', 'Customer created successfully, but welcome email could not be sent.');
        }
    }

    /**
     * Generate a random password for the customer.
     *
     * @return string
     */
    private function generateRandomPassword(): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < 12; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $password;
    }

    /**
     * Get the list of available parishes in Jamaica.
     *
     * @return array
     */
    public function getParishesProperty()
    {
        return [
            'Kingston',
            'St. Andrew',
            'St. Thomas',
            'Portland',
            'St. Mary',
            'St. Ann',
            'Trelawny',
            'St. James',
            'Hanover',
            'Westmoreland',
            'St. Elizabeth',
            'Manchester',
            'Clarendon',
            'St. Catherine',
        ];
    }

    /**
     * Get the list of available pickup locations.
     *
     * @return array
     */
    public function getPickupLocationsProperty()
    {
        return [
            'Kingston Office',
            'Spanish Town Office',
            'Montego Bay Office',
            'Mandeville Office',
            'May Pen Office',
        ];
    }

    public function render()
    {
        return view('livewire.customers.customer-create')
            ->extends('layouts.app')
            ->section('content');
    }
}