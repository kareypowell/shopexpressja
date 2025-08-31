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

    /**
     * Store a newly created address in storage
     */
    public function store(Request $request)
    {
        $this->authorize('create', Address::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean'
        ]);

        $validated['is_active'] = $request->has('is_active');

        Address::create($validated);

        return redirect()->route('admin.addresses.index')
            ->with('success', 'Address created successfully.');
    }

    /**
     * Update the specified address in storage
     */
    public function update(Request $request, Address $address)
    {
        $this->authorize('update', $address);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean'
        ]);

        $validated['is_active'] = $request->has('is_active');

        $address->update($validated);

        return redirect()->route('admin.addresses.show', $address)
            ->with('success', 'Address updated successfully.');
    }

    /**
     * Remove the specified address from storage
     */
    public function destroy(Address $address)
    {
        $this->authorize('delete', $address);
        
        $address->delete();

        return redirect()->route('admin.addresses.index')
            ->with('success', 'Address deleted successfully.');
    }
}