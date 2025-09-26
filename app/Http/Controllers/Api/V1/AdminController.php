<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="API Endpoints for Admin Management"
 * )
 */
class AdminController extends BaseApiController
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/admin/dashboard",
     *     summary="Get admin dashboard data",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dashboard data retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="stats", type="object",
     *                     @OA\Property(property="total_users", type="integer", example=150),
     *                     @OA\Property(property="total_merchants", type="integer", example=25),
     *                     @OA\Property(property="total_bookings", type="integer", example=500),
     *                     @OA\Property(property="active_bookings", type="integer", example=45)
     *                 ),
     *                 @OA\Property(property="recent_activity", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->error('Access denied. Admin role required.', 403);
            }

            // Mock dashboard data
            $dashboardData = [
                'stats' => [
                    'total_users' => User::count(),
                    'total_merchants' => User::role('vendor')->count(),
                    'total_bookings' => 500, // Mock data
                    'active_bookings' => 45, // Mock data
                ],
                'recent_activity' => [
                    [
                        'id' => 1,
                        'type' => 'user_registration',
                        'message' => 'New user registered: john@example.com',
                        'timestamp' => now()->subMinutes(5)->toISOString(),
                    ],
                    [
                        'id' => 2,
                        'type' => 'booking_created',
                        'message' => 'New booking created for Haircut service',
                        'timestamp' => now()->subMinutes(15)->toISOString(),
                    ],
                ]
            ];

            return $this->success($dashboardData, 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve dashboard data', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/logs",
     *     summary="Get system logs",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="level",
     *         in="query",
     *         description="Log level filter",
     *         @OA\Schema(type="string", enum={"error", "warning", "info", "debug"})
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logs retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logs retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function logs(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->error('Access denied. Admin role required.', 403);
            }

            // Mock logs data
            $logs = [
                [
                    'id' => 1,
                    'level' => 'info',
                    'message' => 'User logged in successfully',
                    'context' => ['user_id' => 1, 'ip' => '127.0.0.1'],
                    'timestamp' => now()->subMinutes(10)->toISOString(),
                ],
                [
                    'id' => 2,
                    'level' => 'error',
                    'message' => 'Database connection failed',
                    'context' => ['error' => 'Connection timeout'],
                    'timestamp' => now()->subMinutes(30)->toISOString(),
                ],
            ];

            return $this->success($logs, 'Logs retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve logs', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/log-stats",
     *     summary="Get log statistics",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Log statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Log statistics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_logs", type="integer", example=1000),
     *                 @OA\Property(property="error_count", type="integer", example=50),
     *                 @OA\Property(property="warning_count", type="integer", example=100),
     *                 @OA\Property(property="info_count", type="integer", example=800),
     *                 @OA\Property(property="debug_count", type="integer", example=50)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function logStats(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->error('Access denied. Admin role required.', 403);
            }

            // Mock log statistics
            $stats = [
                'total_logs' => 1000,
                'error_count' => 50,
                'warning_count' => 100,
                'info_count' => 800,
                'debug_count' => 50,
            ];

            return $this->success($stats, 'Log statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve log statistics', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/cleanup-logs",
     *     summary="Cleanup old logs",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="days", type="integer", example=30, description="Number of days to keep logs")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logs cleaned up successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logs cleaned up successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="deleted_count", type="integer", example=150)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function cleanupLogs(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->error('Access denied. Admin role required.', 403);
            }

            $request->validate([
                'days' => 'required|integer|min:1|max:365',
            ]);

            // Mock log cleanup
            $deletedCount = rand(100, 200);

            return $this->success(['deleted_count' => $deletedCount], 'Logs cleaned up successfully');
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to cleanup logs', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/roles",
     *     summary="Get all roles",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Roles retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Roles retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function roles(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->error('Access denied. Admin role required.', 403);
            }

            $roles = Role::with('permissions')->get();

            return $this->success($roles, 'Roles retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve roles', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/permissions",
     *     summary="Get all permissions",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Permissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function permissions(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->error('Access denied. Admin role required.', 403);
            }

            $permissions = Permission::all();

            return $this->success($permissions, 'Permissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve permissions', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/assign-role",
     *     summary="Assign role to user",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","role"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="role", type="string", example="admin"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role assigned successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function assignRole(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->error('Access denied. Admin role required.', 403);
            }

            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'role' => 'required|string|exists:roles,name',
            ]);

            $targetUser = User::findOrFail($request->user_id);
            $targetUser->assignRole($request->role);

            return $this->success([], 'Role assigned successfully');
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to assign role', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/remove-role",
     *     summary="Remove role from user",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","role"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="role", type="string", example="admin"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role removed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function removeRole(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->error('Access denied. Admin role required.', 403);
            }

            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'role' => 'required|string|exists:roles,name',
            ]);

            $targetUser = User::findOrFail($request->user_id);
            $targetUser->removeRole($request->role);

            return $this->success([], 'Role removed successfully');
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to remove role', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/give-permission",
     *     summary="Give permission to user",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","permission"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="permission", type="string", example="view users"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission given successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission given successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function givePermission(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->error('Access denied. Admin role required.', 403);
            }

            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'permission' => 'required|string|exists:permissions,name',
            ]);

            $targetUser = User::findOrFail($request->user_id);
            $targetUser->givePermissionTo($request->permission);

            return $this->success([], 'Permission given successfully');
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to give permission', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/revoke-permission",
     *     summary="Revoke permission from user",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","permission"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="permission", type="string", example="view users"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission revoked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission revoked successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function revokePermission(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->error('Access denied. Admin role required.', 403);
            }

            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'permission' => 'required|string|exists:permissions,name',
            ]);

            $targetUser = User::findOrFail($request->user_id);
            $targetUser->revokePermissionTo($request->permission);

            return $this->success([], 'Permission revoked successfully');
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to revoke permission', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/user-activity/{userId}",
     *     summary="Get user activity",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User activity retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User activity retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function userActivity($userId)
    {
        try {
            $user = Auth::user();
            
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->error('Access denied. Admin role required.', 403);
            }

            // Mock user activity data
            $activity = [
                [
                    'id' => 1,
                    'action' => 'login',
                    'description' => 'User logged in',
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0...',
                    'timestamp' => now()->subMinutes(30)->toISOString(),
                ],
                [
                    'id' => 2,
                    'action' => 'profile_update',
                    'description' => 'User updated profile',
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0...',
                    'timestamp' => now()->subHours(2)->toISOString(),
                ],
            ];

            return $this->success($activity, 'User activity retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user activity', 500, $e->getMessage());
        }
    }
}
