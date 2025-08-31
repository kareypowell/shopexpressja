<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(Address::class, 'address');
    }

    /**
     * Display a listing of addresses
     */
    public function index()
    {
        $this->authorize('viewAny', Address::class);
        return view('admin.addresses.index');
    }

    /**
     * Show the form for creating a new address
     */
    public function create()
    {
        $this->authorize('create', Address::class);
        return view('admin.addresses.create');
    }

    /**
     * Display the specified address
     */
    public function show(Address $address)
    {
        $this->authorize('view', $address);
        return view('admin.addresses.show', compact('address'));
    }

    /**
     * Show the form for editing the specified address
     */
    public function edit(Address $address)
    {
        $this->authorize('update', $address);
        return view('admin.addresses.edit', compact('address'));
    }
}