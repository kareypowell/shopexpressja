<?php

namespace App\Http\Middleware;

use App\Services\FailedAuthenticationTracker;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BlockFailedAuthenticationMiddleware
{
    protected $failedAuthTracker;

    public function __construct(FailedAuthenticationTracker $failedAuthTracker)
    {
        $this->failedAuthTracker = $failedAuthTracker;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Only check authentication routes
        if (!$this->isAuthenticationRoute($request)) {
            return $next($request);
        }

        $ip = $request->ip();
        $email = $request->input('email');

        // Check if IP is blocked
        if ($this->failedAuthTracker->isBlocked($ip, 'ip')) {
            return $this->createBlockedResponse('IP address temporarily blocked due to excessive failed login attempts.');
        }

        // Check if email is blocked
        if ($email && $this->failedAuthTracker->isBlocked($email, 'email')) {
            return $this->createBlockedResponse('Account temporarily locked due to excessive failed login attempts.');
        }

        return $next($request);
    }

    /**
     * Check if this is an authentication route
     */
    protected function isAuthenticationRoute(Request $request): bool
    {
        $authRoutes = [
            'login',
            'password.email',
            'password.update',
        ];

        $routeName = $request->route()?->getName();
        
        return in_array($routeName, $authRoutes) || 
               str_contains($request->path(), 'login') ||
               str_contains($request->path(), 'password');
    }

    /**
     * Create a blocked response
     */
    protected function createBlockedResponse(string $message): Response
    {
        if (request()->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'authentication_blocked'
            ], 429);
        }

        return response()->view('auth.blocked', [
            'message' => $message
        ], 429);
    }
}