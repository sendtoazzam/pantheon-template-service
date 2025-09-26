<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Permission Management",
 *     description="API Endpoints for Permission Management with Advanced Features"
 * )
 */
class PermissionController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/permissions",
     *     summary="Get all permissions",
     *     tags={"Permission Management"},
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
     *         description="Search term for permission name",
     *         @OA\Schema(type="string", example="user")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by permission category",
     *         @OA\Schema(type="string", example="user_management")
     *     ),
     *     @OA\Parameter(
     *         name="with_roles",
     *         in="query",
     *         description="Include roles that have this permission",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/Permission")),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=25),
     *                     @OA\Property(property="last_page", type="integer", example=2)
     *                 ),
     *                 @OA\Property(property="categories", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Permission::query();

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where('name', 'LIKE', "%{$search}%");
            }

            // Category filtering
            if ($request->has('category') && !empty($request->category)) {
                $query->where('name', 'LIKE', $request->category . '%');
            }

            // Include roles
            if ($request->boolean('with_roles', false)) {
                $query->with('roles');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $permissions = $query->paginate($perPage);

            // Get available categories
            $categories = Permission::selectRaw('SUBSTRING_INDEX(name, " ", 1) as category')
                ->distinct()
                ->pluck('category')
                ->filter()
                ->values()
                ->toArray();

            return $this->success([
                'permissions' => PermissionResource::collection($permissions->items()),
                'pagination' => [
                    'current_page' => $permissions->currentPage(),
                    'per_page' => $permissions->perPage(),
                    'total' => $permissions->total(),
                    'last_page' => $permissions->lastPage(),
                    'from' => $permissions->firstItem(),
                    'to' => $permissions->lastItem()
                ],
                'categories' => $categories
            ], 'Permissions retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve permissions', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/permissions/{id}",
     *     summary="Get specific permission by ID",
     *     tags={"Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Permission")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $permission = Permission::with('roles')->findOrFail($id);

            return $this->success([
                'permission' => new PermissionResource($permission)
            ], 'Permission retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Permission not found', $e->getMessage(), 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/permissions",
     *     summary="Create a new permission",
     *     tags={"Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","display_name"},
     *             @OA\Property(property="name", type="string", example="manage content"),
     *             @OA\Property(property="display_name", type="string", example="Manage Content"),
     *             @OA\Property(property="description", type="string", example="Can create, edit, and delete content"),
     *             @OA\Property(property="category", type="string", example="content"),
     *             @OA\Property(property="guard_name", type="string", example="web")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permission created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission created successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Permission")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:permissions,name',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'category' => 'nullable|string|max:100',
                'guard_name' => 'sometimes|string|max:255'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            $permission = Permission::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'category' => $request->category,
                'guard_name' => $request->guard_name ?? 'web'
            ]);

            return $this->success([
                'permission' => new PermissionResource($permission)
            ], 'Permission created successfully', 201);

        } catch (\Exception $e) {
            return $this->error('Failed to create permission', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/permissions/{id}",
     *     summary="Update permission",
     *     tags={"Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="display_name", type="string", example="Manage Content"),
     *             @OA\Property(property="description", type="string", example="Can create, edit, and delete content"),
     *             @OA\Property(property="category", type="string", example="content")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Permission")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $permission = Permission::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'display_name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:500',
                'category' => 'nullable|string|max:100'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            $permission->update($request->only(['display_name', 'description', 'category']));

            return $this->success([
                'permission' => new PermissionResource($permission)
            ], 'Permission updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update permission', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/permissions/{id}",
     *     summary="Delete permission",
     *     tags={"Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $permission = Permission::findOrFail($id);

            // Check if permission is assigned to any roles
            if ($permission->roles()->count() > 0) {
                return $this->error('Cannot delete permission that is assigned to roles', null, 403);
            }

            $permission->delete();

            return $this->success([], 'Permission deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete permission', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/permissions/user/{userId}",
     *     summary="Get user's permissions",
     *     tags={"Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User permissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User permissions retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/Permission")),
     *                 @OA\Property(property="roles", type="array", @OA\Items(ref="#/components/schemas/Role"))
     *             )
     *         )
     *     )
     * )
     */
    public function getUserPermissions($userId)
    {
        try {
            $user = User::with(['permissions', 'roles.permissions'])->findOrFail($userId);

            // Get all permissions (direct + through roles)
            $allPermissions = $user->getAllPermissions();

            return $this->success([
                'permissions' => PermissionResource::collection($allPermissions),
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name ?? $role->name
                    ];
                })
            ], 'User permissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('User not found', $e->getMessage(), 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/permissions/user/{userId}/assign",
     *     summary="Assign permissions to user",
     *     tags={"Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
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
    public function assignToUser(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);

            $validator = Validator::make($request->all(), [
                'permissions' => 'required|array|min:1',
                'permissions.*' => 'string|exists:permissions,name',
                'sync' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            if ($request->boolean('sync', false)) {
                $user->syncPermissions($request->permissions);
            } else {
                $user->givePermissionTo($request->permissions);
            }

            return $this->success([], 'Permissions assigned successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to assign permissions', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/permissions/user/{userId}/revoke",
     *     summary="Revoke permissions from user",
     *     tags={"Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
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
    public function revokeFromUser(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);

            $validator = Validator::make($request->all(), [
                'permissions' => 'required|array|min:1',
                'permissions.*' => 'string|exists:permissions,name'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            $user->revokePermissionTo($request->permissions);

            return $this->success([], 'Permissions revoked successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to revoke permissions', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/permissions/statistics",
     *     summary="Get permission statistics",
     *     tags={"Permission Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Permission statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission statistics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_permissions", type="integer", example=25),
     *                 @OA\Property(property="category_distribution", type="object",
     *                     @OA\Property(property="user_management", type="integer", example=8),
     *                     @OA\Property(property="content_management", type="integer", example=6),
     *                     @OA\Property(property="system_administration", type="integer", example=11)
     *                 ),
     *                 @OA\Property(property="most_used_permissions", type="array", @OA\Items(
     *                     @OA\Property(property="name", type="string", example="view users"),
     *                     @OA\Property(property="usage_count", type="integer", example=15)
     *                 ))
     *             )
     *         )
     *     )
     * )
     */
    public function statistics()
    {
        try {
            $statistics = [
                'total_permissions' => Permission::count(),
                'category_distribution' => Permission::selectRaw('category, COUNT(*) as count')
                    ->whereNotNull('category')
                    ->groupBy('category')
                    ->pluck('count', 'category')
                    ->toArray(),
                'most_used_permissions' => Permission::withCount('roles')
                    ->orderBy('roles_count', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($permission) {
                        return [
                            'name' => $permission->name,
                            'usage_count' => $permission->roles_count
                        ];
                    })
            ];

            return $this->success($statistics, 'Permission statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve permission statistics', $e->getMessage(), 500);
        }
    }
}