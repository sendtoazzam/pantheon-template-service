<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Role Management",
 *     description="API Endpoints for Role Management with Advanced Features"
 * )
 */
class RoleController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/roles",
     *     summary="Get all roles with permissions",
     *     tags={"Role Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for role name",
     *         @OA\Schema(type="string", example="admin")
     *     ),
     *     @OA\Parameter(
     *         name="with_permissions",
     *         in="query",
     *         description="Include permissions in response",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="with_users",
     *         in="query",
     *         description="Include user count in response",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Roles retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Roles retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="roles", type="array", @OA\Items(ref="#/components/schemas/Role")),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=4),
     *                     @OA\Property(property="last_page", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Role::query();

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where('name', 'LIKE', "%{$search}%");
            }

            // Include permissions
            if ($request->boolean('with_permissions', true)) {
                $query->with('permissions');
            }

            // Include user count
            if ($request->boolean('with_users', false)) {
                $query->withCount('users');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $roles = $query->paginate($perPage);

            return $this->success([
                'roles' => RoleResource::collection($roles->items()),
                'pagination' => [
                    'current_page' => $roles->currentPage(),
                    'per_page' => $roles->perPage(),
                    'total' => $roles->total(),
                    'last_page' => $roles->lastPage(),
                    'from' => $roles->firstItem(),
                    'to' => $roles->lastItem()
                ]
            ], 'Roles retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve roles', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/roles/{id}",
     *     summary="Get specific role by ID",
     *     tags={"Role Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Role")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $role = Role::with(['permissions', 'users'])->findOrFail($id);

            return $this->success([
                'role' => new RoleResource($role)
            ], 'Role retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Role not found', $e->getMessage(), 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/roles",
     *     summary="Create a new role",
     *     tags={"Role Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","display_name"},
     *             @OA\Property(property="name", type="string", example="content_manager"),
     *             @OA\Property(property="display_name", type="string", example="Content Manager"),
     *             @OA\Property(property="description", type="string", example="Manages content and media"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={"view content", "create content", "edit content"}),
     *             @OA\Property(property="guard_name", type="string", example="web")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Role created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role created successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Role")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:roles,name',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'permissions' => 'nullable|array',
                'permissions.*' => 'string|exists:permissions,name',
                'guard_name' => 'sometimes|string|max:255'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'guard_name' => $request->guard_name ?? 'web'
            ]);

            // Assign permissions if provided
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            $role->load('permissions');

            DB::commit();

            return $this->success([
                'role' => new RoleResource($role)
            ], 'Role created successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create role', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/roles/{id}",
     *     summary="Update role",
     *     tags={"Role Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="display_name", type="string", example="Content Manager"),
     *             @OA\Property(property="description", type="string", example="Manages content and media"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={"view content", "create content", "edit content"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Role")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $role = Role::findOrFail($id);

            // Prevent modification of superadmin role
            if ($role->name === 'superadmin') {
                return $this->error('Cannot modify superadmin role', null, 403);
            }

            $validator = Validator::make($request->all(), [
                'display_name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:500',
                'permissions' => 'nullable|array',
                'permissions.*' => 'string|exists:permissions,name'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            DB::beginTransaction();

            $role->update($request->only(['display_name', 'description']));

            // Update permissions if provided
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            $role->load('permissions');

            DB::commit();

            return $this->success([
                'role' => new RoleResource($role)
            ], 'Role updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update role', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/roles/{id}",
     *     summary="Delete role",
     *     tags={"Role Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);

            // Prevent deletion of core roles
            if (in_array($role->name, ['superadmin', 'admin', 'user', 'vendor'])) {
                return $this->error('Cannot delete core system role', null, 403);
            }

            // Check if role has users
            if ($role->users()->count() > 0) {
                return $this->error('Cannot delete role that has assigned users', null, 403);
            }

            $role->delete();

            return $this->success([], 'Role deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete role', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/roles/{id}/assign-permissions",
     *     summary="Assign permissions to role",
     *     tags={"Role Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permissions"},
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={"view users", "create users"}),
     *             @OA\Property(property="sync", type="boolean", description="Whether to sync (replace) or add permissions", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions assigned successfully")
     *         )
     *     )
     * )
     */
    public function assignPermissions(Request $request, $id)
    {
        try {
            $role = Role::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'permissions' => 'required|array|min:1',
                'permissions.*' => 'string|exists:permissions,name',
                'sync' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            if ($request->boolean('sync', false)) {
                $role->syncPermissions($request->permissions);
            } else {
                $role->givePermissionTo($request->permissions);
            }

            return $this->success([], 'Permissions assigned successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to assign permissions', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/roles/{id}/revoke-permissions",
     *     summary="Revoke permissions from role",
     *     tags={"Role Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permissions"},
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={"delete users"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions revoked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions revoked successfully")
     *         )
     *     )
     * )
     */
    public function revokePermissions(Request $request, $id)
    {
        try {
            $role = Role::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'permissions' => 'required|array|min:1',
                'permissions.*' => 'string|exists:permissions,name'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            $role->revokePermissionTo($request->permissions);

            return $this->success([], 'Permissions revoked successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to revoke permissions', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/roles/statistics",
     *     summary="Get role statistics",
     *     tags={"Role Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Role statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role statistics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_roles", type="integer", example=4),
     *                 @OA\Property(property="role_distribution", type="object",
     *                     @OA\Property(property="superadmin", type="integer", example=1),
     *                     @OA\Property(property="admin", type="integer", example=2),
     *                     @OA\Property(property="vendor", type="integer", example=5),
     *                     @OA\Property(property="user", type="integer", example=50)
     *                 ),
     *                 @OA\Property(property="permissions_per_role", type="object",
     *                     @OA\Property(property="superadmin", type="integer", example=25),
     *                     @OA\Property(property="admin", type="integer", example=15),
     *                     @OA\Property(property="vendor", type="integer", example=8),
     *                     @OA\Property(property="user", type="integer", example=3)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function statistics()
    {
        try {
            $statistics = [
                'total_roles' => Role::count(),
                'role_distribution' => Role::withCount('users')
                    ->get()
                    ->pluck('users_count', 'name')
                    ->toArray(),
                'permissions_per_role' => Role::withCount('permissions')
                    ->get()
                    ->pluck('permissions_count', 'name')
                    ->toArray()
            ];

            return $this->success($statistics, 'Role statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve role statistics', $e->getMessage(), 500);
        }
    }
}
