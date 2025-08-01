<?php

namespace App\Http\Livewire\Profile;

use App\Models\Office;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;

class Profile extends Component
{
    public $firstName = '';

    public $lastName = '';

    public $email = '';

    public $password = '';

    public $passwordConfirmation = '';

    public $taxNumber = '';

    public $telephoneNumber = '';

    public $streetAddress = '';

    public $cityTown = '';

    public $parish = '';

    public $currentPassword = '';

    public $newPassword = '';

    public $confirmPassword = '';

    public $pickupLocation = '';

    public $pickupLocations = [];

    /**
     * Load all the default values.
     */
    public function mount() {
        $user = auth()->user();
        $this->firstName = $user->first_name;
        $this->lastName = $user->last_name;
        $this->email = $user->email;
        
        // Handle case where user doesn't have a profile yet
        if ($user->profile) {
            $this->taxNumber = $user->profile->tax_number;
            $this->telephoneNumber = $user->profile->telephone_number;
            $this->streetAddress = $user->profile->street_address;
            $this->cityTown = $user->profile->city_town;
            $this->parish = $user->profile->parish;
            $this->pickupLocation = $user->profile->pickup_location;
        } else {
            // Set default values for users without profiles
            $this->taxNumber = '';
            $this->telephoneNumber = '';
            $this->streetAddress = '';
            $this->cityTown = '';
            $this->parish = '';
            $this->pickupLocation = '';
        }

        $this->pickupLocations = Office::orderBy('name', 'asc')->get();
    }

    /**
     * Update the user's profile.
     */
    public function updateProfile()
    {
        $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'taxNumber' => ['required', 'numeric'],
            'telephoneNumber' => ['required', 'numeric'],
            'streetAddress' => ['required', 'string', 'max:255'],
            'cityTown' => ['required', 'string', 'max:255'],
            'parish' => ['required', 'string', 'max:255'],
            'pickupLocation' => ['required'],
        ]);

        auth()->user()->update([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
        ]);

        auth()->user()->profile()->update([
            'tax_number' => $this->taxNumber,
            'telephone_number' => $this->telephoneNumber,
            'street_address' => $this->streetAddress,
            'city_town' => $this->cityTown,
            'parish' => $this->parish,
            'pickup_location' => $this->pickupLocation,
        ]);

        // check for validation errors; if no validation errors, show toast message
        if ($this->firstName && $this->lastName && $this->email && $this->taxNumber && $this->telephoneNumber && $this->streetAddress && $this->cityTown && $this->parish &&
        $this->pickupLocation) {
            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Profile Updated Successfully.',
            ]);
        } else {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'Profile Update Failed.',
            ]);
        }
    }

    /**
     * Update user's password by first checking if the old password is correct. Then compare the new password with the password confirmation.
     */
    public function updatePassword() {
        $this->validate([
            'currentPassword' => ['required'],
            'newPassword' => ['required', 'min:8', 'same:confirmPassword'],
        ]);

        // check if the old password is correct
        if (!Hash::check($this->currentPassword, auth()->user()->password)) {
            $this->dispatchBrowserEvent('toastr:error', [
                'message' => 'There is something wrong with the current password.',
            ]);
            return;
        } else {
            auth()->user()->update([
                'password' => bcrypt($this->newPassword),
            ]);

            $this->dispatchBrowserEvent('toastr:success', [
                'message' => 'Password Updated Successfully.',
            ]);

            $this->resetInputFields();
        }
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    private function resetInputFields()
    {
        $this->currentPassword = '';
        $this->newPassword = '';
        $this->confirmPassword = '';
    }
    
    public function render()
    {
        return view('livewire.profile.profile');
    }
}
