<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error' => [
                    'message' => 'Authentication required',
                    'details' => []
                ]
            ], 401);
        }

        $user = auth()->user();

        if (!$user->hasAnyPermission($permissions)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
                'error' => [
                    'message' => 'You do not have the required permission to access this resource',
                    'details' => [
                        'required_permissions' => $permissions,
                        'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}