<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="User Management",
 *     description="API Endpoints for Managing Users (Superadmin Only)"
 * )
 */
class UserManagementController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/users",
     *     summary="Get all users",
     *     tags={"User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filter by role",
     *         required=false,
     *         @OA\Schema(type="string", enum={"superadmin", "admin", "vendor", "user"})
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive", "suspended"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of users per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Users retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            // Check if user has permission to view users
            if (!Auth::user()->can('view users')) {
                return $this->error('Access denied. You do not have permission to view users.', null, 403);
            }

            $query = User::with('roles', 'permissions');

            // Filter by role
            if ($request->has('role')) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            return $this->paginated(UserResource::collection($users), 'Users retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve users', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/users",
     *     summary="Create a new user (Superadmin only)",
     *     tags={"User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","role"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),
     *             @OA\Property(property="role", type="string", enum={"superadmin", "admin", "vendor", "user"}, example="admin"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active"),
     *             @OA\Property(property="is_admin", type="boolean", example=false),
     *             @OA\Property(property="is_vendor", type="boolean", example=false),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User created successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            // Check if user is superadmin
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->error('Access denied. Only superadmin can create users.', null, 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'nullable|string|max:255',
                'password' => 'required|string|min:8',
                'role' => 'required|string|in:superadmin,admin,vendor,user',
                'status' => 'nullable|string|in:active,inactive,suspended',
                'is_admin' => 'boolean',
                'is_vendor' => 'boolean',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            $role = $request->role;
            $isAdmin = $request->boolean('is_admin', in_array($role, ['superadmin', 'admin']));
            $isVendor = $request->boolean('is_vendor', $role === 'vendor');

            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'status' => $request->status ?? 'active',
                'is_admin' => $isAdmin,
                'is_vendor' => $isVendor,
                'is_active' => $request->boolean('is_active', true),
                'email_verified_at' => now(),
            ]);

            // Assign role
            $user->assignRole($role);

            // Load user's roles and permissions
            $user->load('roles', 'permissions');

            return $this->created(new UserResource($user), 'User created successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to create user', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/users/{id}",
     *     summary="Get user by ID",
     *     tags={"User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User retrieved successfully"),
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
    public function show($id)
    {
        try {
            // Check if user has permission to view users
            if (!Auth::user()->can('view users')) {
                return $this->error('Access denied. You do not have permission to view users.', null, 403);
            }

            $user = User::with('roles', 'permissions')->findOrFail($id);

            return $this->success(new UserResource($user), 'User retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/users/{id}",
     *     summary="Update user",
     *     tags={"User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active"),
     *             @OA\Property(property="is_admin", type="boolean", example=false),
     *             @OA\Property(property="is_vendor", type="boolean", example=false),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User updated successfully"),
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
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            // Check if user has permission to edit users
            if (!Auth::user()->can('edit users')) {
                return $this->error('Access denied. You do not have permission to edit users.', null, 403);
            }

            $user = User::findOrFail($id);

            // Check if trying to update superadmin (only superadmin can do this)
            if ($user->hasRole('superadmin') && !Auth::user()->hasRole('superadmin')) {
                return $this->error('Access denied. Only superadmin can update superadmin users.', null, 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'username' => 'sometimes|string|max:255|unique:users,username,' . $id,
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
                'phone' => 'nullable|string|max:255',
                'password' => 'sometimes|string|min:8',
                'status' => 'sometimes|string|in:active,inactive,suspended',
                'is_admin' => 'boolean',
                'is_vendor' => 'boolean',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            $updateData = $request->only(['name', 'username', 'email', 'phone', 'status', 'is_admin', 'is_vendor', 'is_active']);

            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);
            $user->load('roles', 'permissions');

            return $this->success(new UserResource($user), 'User updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update user', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/users/{id}",
     *     summary="Delete user",
     *     tags={"User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
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
    public function destroy($id)
    {
        try {
            // Check if user has permission to delete users
            if (!Auth::user()->can('delete users')) {
                return $this->error('Access denied. You do not have permission to delete users.', null, 403);
            }

            $user = User::findOrFail($id);

            // Check if trying to delete superadmin (only superadmin can do this)
            if ($user->hasRole('superadmin') && !Auth::user()->hasRole('superadmin')) {
                return $this->error('Access denied. Only superadmin can delete superadmin users.', null, 403);
            }

            // Prevent self-deletion
            if ($user->id === Auth::id()) {
                return $this->error('You cannot delete your own account.', null, 403);
            }

            $user->delete();

            return $this->success([], 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete user', $e->getMessage(), 500);
        }
    }
}
