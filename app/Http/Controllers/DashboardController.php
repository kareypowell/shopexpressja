<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user()->load('role');
        
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            // Redirect admin and superadmin users to the dedicated admin dashboard
            return redirect()->route('admin.dashboard');
        }
        
        // For regular users (customers), render the dashboard Livewire component
        return view('dashboard');
    }
}