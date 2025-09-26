<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\UserResource;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * @OA\Tag(
 *     name="User Management",
 *     description="API Endpoints for User Management with Advanced Features"
 * )
 */
class UserController extends BaseApiController
{
    protected $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users",
     *     summary="Get all users with advanced filtering and search",
     *     tags={"User Management"},
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
     *         description="Search term for name, email, username",
     *         @OA\Schema(type="string", example="john")
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filter by role",
     *         @OA\Schema(type="string", example="admin")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"active", "inactive", "suspended"})
     *     ),
     *     @OA\Parameter(
     *         name="is_admin",
     *         in="query",
     *         description="Filter by admin status",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="is_vendor",
     *         in="query",
     *         description="Filter by vendor status",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field",
     *         @OA\Schema(type="string", enum={"name", "email", "created_at", "last_login_at"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_direction",
     *         in="query",
     *         description="Sort direction",
     *         @OA\Schema(type="string", enum={"asc", "desc"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Users retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="users", type="array", @OA\Items(ref="#/components/schemas/UserWithRoles")),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=100),
     *                     @OA\Property(property="last_page", type="integer", example=7)
     *                 ),
     *                 @OA\Property(property="filters", type="object",
     *                     @OA\Property(property="applied", type="object"),
     *                     @OA\Property(property="available_roles", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="available_statuses", type="array", @OA\Items(type="string"))
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = User::with(['roles', 'permissions']);

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('username', 'LIKE', "%{$search}%");
                });
            }

            // Role filtering
            if ($request->has('role') && !empty($request->role)) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            // Status filtering
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Admin/Vendor filtering
            if ($request->has('is_admin')) {
                $query->where('is_admin', $request->boolean('is_admin'));
            }

            if ($request->has('is_vendor')) {
                $query->where('is_vendor', $request->boolean('is_vendor'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            // Get available filters
            $availableRoles = Role::pluck('name')->toArray();
            $availableStatuses = ['active', 'inactive', 'suspended'];

            return $this->success([
                'users' => UserResource::collection($users->items()),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem()
                ],
                'filters' => [
                    'applied' => $request->only(['search', 'role', 'status', 'is_admin', 'is_vendor', 'sort_by', 'sort_direction']),
                    'available_roles' => $availableRoles,
                    'available_statuses' => $availableStatuses
                ]
            ], 'Users retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve users', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/profile",
     *     summary="Get current user's profile",
     *     tags={"User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profile retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserWithRoles")
     *         )
     *     )
     * )
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();
            $user->load(['roles', 'permissions']);

            return $this->success([
                'user' => new UserResource($user)
            ], 'User profile retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user profile', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/users/profile",
     *     summary="Update current user's profile",
     *     tags={"User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserWithRoles")
     *         )
     *     )
     * )
     */
    public function updateProfile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'username' => 'sometimes|required|string|max:255|unique:users,username,' . $request->user()->id,
                'phone' => 'sometimes|nullable|string|max:255',
                'avatar' => 'sometimes|nullable|url|max:500'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            $user = $request->user();
            $user->update($request->only(['name', 'username', 'phone', 'avatar']));
            $user->load(['roles', 'permissions']);

            return $this->success([
                'user' => new UserResource($user)
            ], 'Profile updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update profile', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/{id}",
     *     summary="Get specific user by ID",
     *     tags={"User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserWithRoles")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $user = User::with(['roles', 'permissions'])->findOrFail($id);

            return $this->success([
                'user' => new UserResource($user)
            ], 'User retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('User not found', $e->getMessage(), 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users",
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
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="role", type="string", example="user"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active"),
     *             @OA\Property(property="is_admin", type="boolean", example=false),
     *             @OA\Property(property="is_vendor", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User created successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserWithRoles")
     *         )
     *     )
     * )
     */
    public function store(StoreUserRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'status' => $request->status ?? 'active',
                'is_admin' => $request->is_admin ?? false,
                'is_vendor' => $request->is_vendor ?? false,
                'email_verified_at' => now()
            ]);

            // Assign role
            if ($request->has('role')) {
                $user->assignRole($request->role);
            }

            // Update password history
            $this->passwordResetService->updatePasswordHistory($user, $request->password);

            $user->load(['roles', 'permissions']);

            DB::commit();

            return $this->success([
                'user' => new UserResource($user)
            ], 'User created successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create user', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/users/{id}",
     *     summary="Update user (Superadmin only)",
     *     tags={"User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active"),
     *             @OA\Property(property="is_admin", type="boolean", example=false),
     *             @OA\Property(property="is_vendor", type="boolean", example=false),
     *             @OA\Property(property="role", type="string", example="user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserWithRoles")
     *         )
     *     )
     * )
     */
    public function update(UpdateUserRequest $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            DB::beginTransaction();

            $user->update($request->only([
                'name', 'username', 'email', 'phone', 'status', 'is_admin', 'is_vendor'
            ]));

            // Update role if provided
            if ($request->has('role')) {
                $user->syncRoles([$request->role]);
            }

            $user->load(['roles', 'permissions']);

            DB::commit();

            return $this->success([
                'user' => new UserResource($user)
            ], 'User updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update user', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/users/{id}",
     *     summary="Delete user (Superadmin only)",
     *     tags={"User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deletion of superadmin users
            if ($user->hasRole('superadmin')) {
                return $this->error('Cannot delete superadmin user', null, 403);
            }

            $user->delete();

            return $this->success([], 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete user', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/bulk-update",
     *     summary="Bulk update users (Superadmin only)",
     *     tags={"User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_ids","updates"},
     *             @OA\Property(property="user_ids", type="array", @OA\Items(type="integer"), example={1,2,3}),
     *             @OA\Property(property="updates", type="object",
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="role", type="string", example="user")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Users updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="updated_count", type="integer", example=3)
     *             )
     *         )
     *     )
     * )
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'integer|exists:users,id',
                'updates' => 'required|array',
                'updates.status' => 'sometimes|string|in:active,inactive,suspended',
                'updates.role' => 'sometimes|string|exists:roles,name'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            DB::beginTransaction();

            $userIds = $request->user_ids;
            $updates = $request->updates;

            // Remove role from updates if present (handled separately)
            $role = $updates['role'] ?? null;
            unset($updates['role']);

            // Update users
            $updatedCount = User::whereIn('id', $userIds)->update($updates);

            // Update roles if provided
            if ($role) {
                $users = User::whereIn('id', $userIds)->get();
                foreach ($users as $user) {
                    if (!$user->hasRole('superadmin')) {
                        $user->syncRoles([$role]);
                    }
                }
            }

            DB::commit();

            return $this->success([
                'updated_count' => $updatedCount
            ], 'Users updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update users', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/statistics",
     *     summary="Get user statistics",
     *     tags={"User Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User statistics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_users", type="integer", example=150),
     *                 @OA\Property(property="active_users", type="integer", example=120),
     *                 @OA\Property(property="inactive_users", type="integer", example=20),
     *                 @OA\Property(property="suspended_users", type="integer", example=10),
     *                 @OA\Property(property="admin_users", type="integer", example=5),
     *                 @OA\Property(property="vendor_users", type="integer", example=25),
     *                 @OA\Property(property="role_distribution", type="object",
     *                     @OA\Property(property="superadmin", type="integer", example=1),
     *                     @OA\Property(property="admin", type="integer", example=4),
     *                     @OA\Property(property="vendor", type="integer", example=25),
     *                     @OA\Property(property="user", type="integer", example=120)
     *                 ),
     *                 @OA\Property(property="recent_registrations", type="integer", example=15)
     *             )
     *         )
     *     )
     * )
     */
    public function statistics()
    {
        try {
            $statistics = [
                'total_users' => User::count(),
                'active_users' => User::where('status', 'active')->count(),
                'inactive_users' => User::where('status', 'inactive')->count(),
                'suspended_users' => User::where('status', 'suspended')->count(),
                'admin_users' => User::where('is_admin', true)->count(),
                'vendor_users' => User::where('is_vendor', true)->count(),
                'role_distribution' => User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->selectRaw('roles.name, COUNT(*) as count')
                    ->groupBy('roles.name')
                    ->pluck('count', 'name')
                    ->toArray(),
                'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count()
            ];

            return $this->success($statistics, 'User statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user statistics', $e->getMessage(), 500);
        }
    }
}