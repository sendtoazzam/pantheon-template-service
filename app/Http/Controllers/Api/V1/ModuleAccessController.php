<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Module Access Management",
 *     description="API Endpoints for Managing User Module Access"
 * )
 */
class ModuleAccessController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/modules",
     *     summary="Get all available modules",
     *     tags={"Module Access Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Modules retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Modules retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="string", example="user_management"),
     *                 @OA\Property(property="name", type="string", example="User Management"),
     *                 @OA\Property(property="description", type="string", example="Manage users, roles, and permissions"),
     *                 @OA\Property(property="icon", type="string", example="users"),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="view users")),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             ))
     *         )
     *     )
     * )
     */
    public function getModules()
    {
        try {
            // Check if user has permission to view modules
            if (!Auth::user()->can('manage permissions')) {
                return $this->error('Access denied. You do not have permission to view modules.', null, 403);
            }

            $modules = $this->getAvailableModules();

            return $this->success($modules, 'Modules retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve modules', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/users/{userId}/module-access",
     *     summary="Get user's module access",
     *     tags={"Module Access Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User module access retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User module access retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
     *                 @OA\Property(property="modules", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="string", example="user_management"),
     *                     @OA\Property(property="name", type="string", example="User Management"),
     *                     @OA\Property(property="has_access", type="boolean", example=true),
     *                     @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *                 ))
     *             )
     *         )
     *     )
     * )
     */
    public function getUserModuleAccess(Request $request, $userId)
    {
        try {
            // Check if user has permission to view module access
            if (!Auth::user()->can('manage permissions')) {
                return $this->error('Access denied. You do not have permission to view module access.', null, 403);
            }

            $user = User::with(['roles', 'permissions'])->find($userId);
            if (!$user) {
                return $this->notFound('User not found');
            }

            $modules = $this->getAvailableModules();
            $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();

            $userModules = collect($modules)->map(function ($module) use ($userPermissions) {
                $modulePermissions = $module['permissions'];
                $hasAccess = !empty(array_intersect($userPermissions, $modulePermissions));
                
                return [
                    'id' => $module['id'],
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'icon' => $module['icon'],
                    'has_access' => $hasAccess,
                    'permissions' => array_intersect($userPermissions, $modulePermissions),
                    'missing_permissions' => array_diff($modulePermissions, $userPermissions),
                ];
            });

            return $this->success([
                'user' => $user,
                'modules' => $userModules,
            ], 'User module access retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user module access', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/users/{userId}/grant-module-access",
     *     summary="Grant module access to user",
     *     tags={"Module Access Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"module_id"},
     *             @OA\Property(property="module_id", type="string", description="Module ID", example="user_management"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"), description="Specific permissions to grant", example={"view users", "create users"}),
     *             @OA\Property(property="grant_all", type="boolean", description="Grant all module permissions", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Module access granted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Module access granted successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function grantModuleAccess(Request $request, $userId)
    {
        try {
            // Check if user has permission to grant module access
            if (!Auth::user()->can('manage permissions')) {
                return $this->error('Access denied. You do not have permission to grant module access.', null, 403);
            }

            $validator = Validator::make($request->all(), [
                'module_id' => 'required|string',
                'permissions' => 'nullable|array',
                'permissions.*' => 'string|exists:permissions,name',
                'grant_all' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $user = User::find($userId);
            if (!$user) {
                return $this->notFound('User not found');
            }

            $moduleId = $request->input('module_id');
            $modules = $this->getAvailableModules();
            $module = collect($modules)->firstWhere('id', $moduleId);

            if (!$module) {
                return $this->error('Invalid module ID', null, 400);
            }

            DB::beginTransaction();

            try {
                if ($request->boolean('grant_all')) {
                    // Grant all module permissions
                    $permissions = $module['permissions'];
                } else {
                    // Grant specific permissions
                    $permissions = $request->input('permissions', []);
                    if (empty($permissions)) {
                        return $this->error('No permissions specified', null, 400);
                    }
                }

                // Grant permissions to user
                foreach ($permissions as $permission) {
                    if (!$user->hasPermissionTo($permission)) {
                        $user->givePermissionTo($permission);
                    }
                }

                DB::commit();

                $user->load(['roles', 'permissions']);

                return $this->success($user, 'Module access granted successfully');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return $this->error('Failed to grant module access', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/users/{userId}/revoke-module-access",
     *     summary="Revoke module access from user",
     *     tags={"Module Access Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"module_id"},
     *             @OA\Property(property="module_id", type="string", description="Module ID", example="user_management"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"), description="Specific permissions to revoke", example={"view users", "create users"}),
     *             @OA\Property(property="revoke_all", type="boolean", description="Revoke all module permissions", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Module access revoked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Module access revoked successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function revokeModuleAccess(Request $request, $userId)
    {
        try {
            // Check if user has permission to revoke module access
            if (!Auth::user()->can('manage permissions')) {
                return $this->error('Access denied. You do not have permission to revoke module access.', null, 403);
            }

            $validator = Validator::make($request->all(), [
                'module_id' => 'required|string',
                'permissions' => 'nullable|array',
                'permissions.*' => 'string|exists:permissions,name',
                'revoke_all' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $user = User::find($userId);
            if (!$user) {
                return $this->notFound('User not found');
            }

            $moduleId = $request->input('module_id');
            $modules = $this->getAvailableModules();
            $module = collect($modules)->firstWhere('id', $moduleId);

            if (!$module) {
                return $this->error('Invalid module ID', null, 400);
            }

            DB::beginTransaction();

            try {
                if ($request->boolean('revoke_all')) {
                    // Revoke all module permissions
                    $permissions = $module['permissions'];
                } else {
                    // Revoke specific permissions
                    $permissions = $request->input('permissions', []);
                    if (empty($permissions)) {
                        return $this->error('No permissions specified', null, 400);
                    }
                }

                // Revoke permissions from user
                foreach ($permissions as $permission) {
                    if ($user->hasPermissionTo($permission)) {
                        $user->revokePermissionTo($permission);
                    }
                }

                DB::commit();

                $user->load(['roles', 'permissions']);

                return $this->success($user, 'Module access revoked successfully');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return $this->error('Failed to revoke module access', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/users/{userId}/bulk-module-access",
     *     summary="Bulk update user module access",
     *     tags={"Module Access Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"modules"},
     *             @OA\Property(property="modules", type="array", @OA\Items(
     *                 @OA\Property(property="module_id", type="string", example="user_management"),
     *                 @OA\Property(property="action", type="string", enum={"grant", "revoke"}, example="grant"),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={"view users", "create users"}),
     *                 @OA\Property(property="grant_all", type="boolean", example=false)
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bulk module access updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bulk module access updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function bulkModuleAccess(Request $request, $userId)
    {
        try {
            // Check if user has permission to manage module access
            if (!Auth::user()->can('manage permissions')) {
                return $this->error('Access denied. You do not have permission to manage module access.', null, 403);
            }

            $validator = Validator::make($request->all(), [
                'modules' => 'required|array',
                'modules.*.module_id' => 'required|string',
                'modules.*.action' => 'required|in:grant,revoke',
                'modules.*.permissions' => 'nullable|array',
                'modules.*.permissions.*' => 'string|exists:permissions,name',
                'modules.*.grant_all' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $user = User::find($userId);
            if (!$user) {
                return $this->notFound('User not found');
            }

            $modules = $this->getAvailableModules();
            $moduleIds = collect($modules)->pluck('id')->toArray();

            DB::beginTransaction();

            try {
                foreach ($request->input('modules') as $moduleData) {
                    $moduleId = $moduleData['module_id'];
                    $action = $moduleData['action'];
                    
                    if (!in_array($moduleId, $moduleIds)) {
                        continue; // Skip invalid module IDs
                    }

                    $module = collect($modules)->firstWhere('id', $moduleId);
                    $permissions = $moduleData['grant_all'] ? $module['permissions'] : ($moduleData['permissions'] ?? []);

                    foreach ($permissions as $permission) {
                        if ($action === 'grant' && !$user->hasPermissionTo($permission)) {
                            $user->givePermissionTo($permission);
                        } elseif ($action === 'revoke' && $user->hasPermissionTo($permission)) {
                            $user->revokePermissionTo($permission);
                        }
                    }
                }

                DB::commit();

                $user->load(['roles', 'permissions']);

                return $this->success($user, 'Bulk module access updated successfully');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return $this->error('Failed to update bulk module access', $e->getMessage(), 500);
        }
    }

    /**
     * Get all available modules with their permissions
     */
    private function getAvailableModules(): array
    {
        return [
            [
                'id' => 'user_management',
                'name' => 'User Management',
                'description' => 'Manage users, roles, and permissions',
                'icon' => 'users',
                'permissions' => [
                    'view users',
                    'create users',
                    'edit users',
                    'delete users',
                    'manage user roles',
                ],
                'is_active' => true,
            ],
            [
                'id' => 'merchant_management',
                'name' => 'Merchant Management',
                'description' => 'Manage merchants and vendor accounts',
                'icon' => 'store',
                'permissions' => [
                    'view merchants',
                    'create merchants',
                    'edit merchants',
                    'delete merchants',
                    'manage merchant settings',
                ],
                'is_active' => true,
            ],
            [
                'id' => 'booking_management',
                'name' => 'Booking Management',
                'description' => 'Manage bookings and reservations',
                'icon' => 'calendar',
                'permissions' => [
                    'view bookings',
                    'create bookings',
                    'edit bookings',
                    'delete bookings',
                    'manage booking status',
                ],
                'is_active' => true,
            ],
            [
                'id' => 'admin_dashboard',
                'name' => 'Admin Dashboard',
                'description' => 'Access to admin dashboard and analytics',
                'icon' => 'chart-bar',
                'permissions' => [
                    'view admin dashboard',
                    'view analytics',
                    'view system logs',
                ],
                'is_active' => true,
            ],
            [
                'id' => 'system_settings',
                'name' => 'System Settings',
                'description' => 'Manage system configuration and settings',
                'icon' => 'cog',
                'permissions' => [
                    'manage system settings',
                    'manage roles',
                    'manage permissions',
                ],
                'is_active' => true,
            ],
            [
                'id' => 'external_api',
                'name' => 'External API Integration',
                'description' => 'Access to external warehouse APIs',
                'icon' => 'cloud',
                'permissions' => [
                    'access external products',
                    'access external packages',
                    'access external insurance',
                    'access external resources',
                    'access external marketing',
                    'access external promotions',
                ],
                'is_active' => true,
            ],
            [
                'id' => 'notification_management',
                'name' => 'Notification Management',
                'description' => 'Manage notifications and communications',
                'icon' => 'bell',
                'permissions' => [
                    'view notifications',
                    'create notifications',
                    'manage notification settings',
                ],
                'is_active' => true,
            ],
            [
                'id' => 'reporting',
                'name' => 'Reporting & Analytics',
                'description' => 'Access to reports and analytics',
                'icon' => 'document-report',
                'permissions' => [
                    'view reports',
                    'export data',
                    'view analytics',
                ],
                'is_active' => true,
            ],
        ];
    }
}
