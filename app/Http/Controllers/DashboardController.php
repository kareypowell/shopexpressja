<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user()->load('role');
        
        if ($user->isSuperAdmin()) {
            // Redirect admin users to the dedicated admin dashboard
            return redirect()->route('admin.dashboard');
        }
        
        // For regular users, render the dashboard Livewire component
        return view('dashboard');
    }
}