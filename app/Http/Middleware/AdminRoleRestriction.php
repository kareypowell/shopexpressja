<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminRoleRestriction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Allow superadmins to access everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }
        
        // Check if regular admin is trying to access restricted areas
        if ($user->isAdmin()) {
            $restrictedRoutes = [
                'admin.roles',
                'backup-dashboard',
                'backup-history', 
                'backup-settings'
            ];
            
            $currentRoute = $request->route()->getName();
            
            if (in_array($currentRoute, $restrictedRoutes)) {
                abort(403, 'Access denied. This section is restricted to Super Administrators only.');
            }
        }
        
        return $next($request);
    }
}