<?php

namespace App\Http\Middleware;

use App\Services\GuardService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GuardMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'guard' => $guard,
                ]
            ], 401);
        }

        $user = Auth::user();

        // Check if user can use this guard
        if (!GuardService::canAuthenticateWithGuard($user, $guard)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You do not have permission to use this guard.',
                'error' => [
                    'code' => 'GUARD_ACCESS_DENIED',
                    'guard' => $guard,
                    'available_guards' => GuardService::getAvailableGuardsForUser($user),
                ]
            ], 403);
        }

        // Check if user is active
        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive.',
                'error' => [
                    'code' => 'ACCOUNT_INACTIVE',
                    'guard' => $guard,
                ]
            ], 403);
        }

        // Check if user is locked
        if ($user->isLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Account is temporarily locked due to multiple failed login attempts.',
                'error' => [
                    'code' => 'ACCOUNT_LOCKED',
                    'guard' => $guard,
                    'locked_until' => $user->locked_until,
                ]
            ], 423);
        }

        // Check rate limiting
        $identifier = $user->id . ':' . $request->ip();
        if (!GuardService::checkRateLimit($guard, $identifier)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'guard' => $guard,
                ]
            ], 429);
        }

        // Check IP whitelist for admin guards
        if (in_array($guard, ['admin', 'api_admin'])) {
            if (!GuardService::checkIpWhitelist($guard, $request->ip())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Your IP address is not whitelisted for admin access.',
                    'error' => [
                        'code' => 'IP_NOT_WHITELISTED',
                        'guard' => $guard,
                        'ip' => $request->ip(),
                    ]
                ], 403);
            }
        }

        // Check 2FA requirement
        if (GuardService::requiresTwoFactor($guard) && !$user->hasTwoFactorEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is required for this guard.',
                'error' => [
                    'code' => '2FA_REQUIRED',
                    'guard' => $guard,
                ]
            ], 403);
        }

        // Check max tokens for API guards
        if (str_starts_with($guard, 'api_')) {
            if (!GuardService::checkMaxTokens($user, $guard)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum number of tokens exceeded for this guard.',
                    'error' => [
                        'code' => 'MAX_TOKENS_EXCEEDED',
                        'guard' => $guard,
                    ]
                ], 403);
            }
        }

        // Log guard activity
        GuardService::logGuardActivity($user, $guard, 'access', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);

        // Set the guard in the request for later use
        $request->attributes->set('guard', $guard);

        return $next($request);
    }
}
