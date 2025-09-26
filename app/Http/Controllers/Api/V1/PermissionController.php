<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Permission Management",
 *     description="API Endpoints for Managing User Permissions and Roles"
 * )
 */
class PermissionController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/permissions",
     *     summary="Get all permissions",
     *     tags={"Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Permissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="view users"),
     *                 @OA\Property(property="guard_name", type="string", example="web"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     )
     * )
     */
    public function index()
    {
        try {
            // Check if user has permission to view permissions
            if (!Auth::user()->can('manage permissions')) {
                return $this->error('Access denied. You do not have permission to view permissions.', null, 403);
            }

            $permissions = Permission::all();

            return $this->success($permissions, 'Permissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve permissions', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/roles",
     *     summary="Get all roles",
     *     tags={"Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Roles retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Roles retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="admin"),
     *                 @OA\Property(property="guard_name", type="string", example="web"),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="view users"),
     *                     @OA\Property(property="guard_name", type="string", example="web")
     *                 )),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     )
     * )
     */
    public function getRoles()
    {
        try {
            // Check if user has permission to view roles
            if (!Auth::user()->can('manage roles')) {
                return $this->error('Access denied. You do not have permission to view roles.', null, 403);
            }

            $roles = Role::with('permissions')->get();

            return $this->success($roles, 'Roles retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve roles', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/users/{userId}/assign-role",
     *     summary="Assign role to user",
     *     tags={"Permission Management"},
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
     *             required={"role"},
     *             @OA\Property(property="role", type="string", description="Role name", example="admin"),
     *             @OA\Property(property="remove_other_roles", type="boolean", description="Remove other roles", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role assigned successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function assignRole(Request $request, $userId)
    {
        try {
            // Check if user has permission to manage roles
            if (!Auth::user()->can('manage roles')) {
                return $this->error('Access denied. You do not have permission to assign roles.', null, 403);
            }

            $validator = Validator::make($request->all(), [
                'role' => 'required|string|exists:roles,name',
                'remove_other_roles' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            $user = User::findOrFail($userId);
            $roleName = $request->role;
            $removeOtherRoles = $request->boolean('remove_other_roles', false);

            // Check if trying to assign superadmin role (only superadmin can do this)
            if ($roleName === 'superadmin' && !Auth::user()->hasRole('superadmin')) {
                return $this->error('Access denied. Only superadmin can assign superadmin role.', null, 403);
            }

            // Check if trying to assign admin role (only superadmin can do this)
            if ($roleName === 'admin' && !Auth::user()->hasRole('superadmin')) {
                return $this->error('Access denied. Only superadmin can assign admin role.', null, 403);
            }

            if ($removeOtherRoles) {
                $user->syncRoles([$roleName]);
            } else {
                $user->assignRole($roleName);
            }

            $user->load('roles', 'permissions');

            return $this->success(new \App\Http\Resources\UserResource($user), 'Role assigned successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to assign role', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/users/{userId}/remove-role",
     *     summary="Remove role from user",
     *     tags={"Permission Management"},
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
     *             required={"role"},
     *             @OA\Property(property="role", type="string", description="Role name", example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role removed successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function removeRole(Request $request, $userId)
    {
        try {
            // Check if user has permission to manage roles
            if (!Auth::user()->can('manage roles')) {
                return $this->error('Access denied. You do not have permission to remove roles.', null, 403);
            }

            $validator = Validator::make($request->all(), [
                'role' => 'required|string|exists:roles,name'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            $user = User::findOrFail($userId);
            $roleName = $request->role;

            // Check if trying to remove superadmin role (only superadmin can do this)
            if ($roleName === 'superadmin' && !Auth::user()->hasRole('superadmin')) {
                return $this->error('Access denied. Only superadmin can remove superadmin role.', null, 403);
            }

            $user->removeRole($roleName);
            $user->load('roles', 'permissions');

            return $this->success(new \App\Http\Resources\UserResource($user), 'Role removed successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to remove role', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/users/{userId}/give-permission",
     *     summary="Give permission to user",
     *     tags={"Permission Management"},
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
     *             required={"permission"},
     *             @OA\Property(property="permission", type="string", description="Permission name", example="view users")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission given successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission given successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function givePermission(Request $request, $userId)
    {
        try {
            // Check if user has permission to manage permissions
            if (!Auth::user()->can('manage permissions')) {
                return $this->error('Access denied. You do not have permission to give permissions.', null, 403);
            }

            $validator = Validator::make($request->all(), [
                'permission' => 'required|string|exists:permissions,name'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            $user = User::findOrFail($userId);
            $permissionName = $request->permission;

            $user->givePermissionTo($permissionName);
            $user->load('roles', 'permissions');

            return $this->success(new \App\Http\Resources\UserResource($user), 'Permission given successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to give permission', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/users/{userId}/revoke-permission",
     *     summary="Revoke permission from user",
     *     tags={"Permission Management"},
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
     *             required={"permission"},
     *             @OA\Property(property="permission", type="string", description="Permission name", example="view users")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission revoked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission revoked successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function revokePermission(Request $request, $userId)
    {
        try {
            // Check if user has permission to manage permissions
            if (!Auth::user()->can('manage permissions')) {
                return $this->error('Access denied. You do not have permission to revoke permissions.', null, 403);
            }

            $validator = Validator::make($request->all(), [
                'permission' => 'required|string|exists:permissions,name'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            $user = User::findOrFail($userId);
            $permissionName = $request->permission;

            $user->revokePermissionTo($permissionName);
            $user->load('roles', 'permissions');

            return $this->success(new \App\Http\Resources\UserResource($user), 'Permission revoked successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to revoke permission', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/users/{userId}/permissions",
     *     summary="Get user permissions",
     *     tags={"Permission Management"},
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
     *         description="User permissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User permissions retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="admin"),
     *                     @OA\Property(property="permissions", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="view users")
     *                     ))
     *                 )),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="view users")
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function getUserPermissions($userId)
    {
        try {
            // Check if user has permission to view user permissions
            if (!Auth::user()->can('view users')) {
                return $this->error('Access denied. You do not have permission to view user permissions.', null, 403);
            }

            $user = User::with(['roles.permissions', 'permissions'])->findOrFail($userId);

            return $this->success([
                'user' => new \App\Http\Resources\UserResource($user),
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'permissions' => $role->permissions->map(function ($permission) {
                            return [
                                'id' => $permission->id,
                                'name' => $permission->name,
                            ];
                        })
                    ];
                }),
                'permissions' => $user->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ];
                })
            ], 'User permissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user permissions', $e->getMessage(), 500);
        }
    }
}
