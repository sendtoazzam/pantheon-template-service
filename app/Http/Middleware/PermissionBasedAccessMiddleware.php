<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class PermissionBasedAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'permission_required' => $permission,
                ]
            ], 401);
        }

        $user = Auth::user();

        // Check if user has the required permission
        if (!$user->can($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You do not have the required permission.',
                'error' => [
                    'code' => 'PERMISSION_DENIED',
                    'permission_required' => $permission,
                    'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'user_roles' => $user->getRoleNames()->toArray(),
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
                    'permission_required' => $permission,
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
                    'permission_required' => $permission,
                    'locked_until' => $user->locked_until,
                ]
            ], 423);
        }

        // Log permission access
        \App\Models\AuditTrail::create([
            'user_id' => $user->id,
            'action' => 'permission_access',
            'resource_type' => 'Permission',
            'resource_id' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'description' => "User {$user->name} accessed resource requiring permission: {$permission}",
            'status' => 'success',
            'metadata' => [
                'permission' => $permission,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ],
            'performed_at' => now(),
        ]);

        return $next($request);
    }
}
