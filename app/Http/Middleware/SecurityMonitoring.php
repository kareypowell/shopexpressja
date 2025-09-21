<?php

namespace App\Http\Middleware;

use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityMonitoring
{
    protected SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Monitor for suspicious activity after request processing
        if (Auth::check()) {
            $this->securityService->detectSuspiciousActivity(
                Auth::id(),
                $request->ip()
            );
        }

        return $response;
    }
}