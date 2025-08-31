<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(Office::class, 'office');
    }

    /**
     * Display a listing of offices
     */
    public function index()
    {
        $this->authorize('viewAny', Office::class);
        return view('admin.offices.index');
    }

    /**
     * Show the form for creating a new office
     */
    public function create()
    {
        $this->authorize('create', Office::class);
        return view('admin.offices.create');
    }

    /**
     * Display the specified office
     */
    public function show(Office $office)
    {
        $this->authorize('view', $office);
        return view('admin.offices.show', compact('office'));
    }

    /**
     * Show the form for editing the specified office
     */
    public function edit(Office $office)
    {
        $this->authorize('update', $office);
        return view('admin.offices.edit', compact('office'));
    }

    /**
     * Store a newly created office in storage
     */
    public function store(Request $request)
    {
        $this->authorize('create', Office::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean'
        ]);

        $validated['is_active'] = $request->has('is_active');

        Office::create($validated);

        return redirect()->route('admin.offices.index')
            ->with('success', 'Office created successfully.');
    }

    /**
     * Update the specified office in storage
     */
    public function update(Request $request, Office $office)
    {
        $this->authorize('update', $office);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean'
        ]);

        $validated['is_active'] = $request->has('is_active');

        $office->update($validated);

        return redirect()->route('admin.offices.show', $office)
            ->with('success', 'Office updated successfully.');
    }

    /**
     * Remove the specified office from storage
     */
    public function destroy(Office $office)
    {
        $this->authorize('delete', $office);
        
        $office->delete();

        return redirect()->route('admin.offices.index')
            ->with('success', 'Office deleted successfully.');
    }
}