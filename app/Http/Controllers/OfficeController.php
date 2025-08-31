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
}