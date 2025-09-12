<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        // Check if user can view this user
        $this->authorize('user.view', $user);
        
        // For now, redirect back to the user management page
        // In the future, this could show a dedicated user profile page
        return redirect()->route('admin.users.index')
            ->with('success', "Viewing user: {$user->full_name}");
    }
}