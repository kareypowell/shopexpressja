<?php

namespace App\Http\Livewire\Auth;

use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Services\AccountNumberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Livewire\Component;

class Register extends Component
{
    /** @var string */
    public $firstName = '';

    /** @var string */
    public $lastName = '';

    /** @var string */
    public $email = '';

    /** @var string */
    public $password = '';

    /** @var string */
    public $passwordConfirmation = '';

    /** @var string */
    public $taxNumber = '';

    /** @var string */
    public $telephoneNumber = '';

    /** @var string */
    public $streetAddress = '';

    /** @var string */
    public $townCity = '';

    /** @var string */
    public $parish = '';

    /** @var boolean */
    public $termsAccepted = false;

    public function register()
    {
        $this->validate([
            'firstName' => ['required'],
            'lastName' => ['required'],
            'email' => ['required', 'email', 'unique:users'],
            'taxNumber' => ['required', 'numeric'],
            'telephoneNumber' => ['required', 'numeric'],
            'password' => ['required', 'min:8', 'same:passwordConfirmation'],
        ]);

        $user = User::create([
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'password' => Hash::make($this->password),
            'role_id' => Role::where('name', 'customer')->first()->id,
        ]);

        $accountNumberService = new AccountNumberService();
        
        $user->profile()->create([
            'account_number' => $accountNumberService->generate(),
            'tax_number' => $this->taxNumber,
            'telephone_number' => $this->telephoneNumber,
            'street_address' => $this->streetAddress,
            'city_town' => $this->townCity,
            'parish' => $this->parish,
        ]);

        event(new Registered($user));

        Auth::login($user, true);

        return redirect()->intended(route('home'));
    }

    public function render()
    {
        return view('livewire.auth.register')->extends('layouts.auth');
    }

 
}
