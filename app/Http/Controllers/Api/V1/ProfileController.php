<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Profile Management",
 *     description="API Endpoints for User Profile Management with Permission-Based Access Control"
 * )
 */
class ProfileController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/profile",
     *     summary="Get user profile",
     *     tags={"Profile Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     )
     * )
     */
    public function show()
    {
        try {
            $user = Auth::user();
            $user->load('roles', 'permissions');

            return $this->success(new UserResource($user), 'Profile retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve profile', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/profile",
     *     summary="Update user profile",
     *     tags={"Profile Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg"),
     *             @OA\Property(property="current_password", type="string", format="password", example="currentpassword"),
     *             @OA\Property(property="new_password", type="string", format="password", example="newpassword"),
     *             @OA\Property(property="new_password_confirmation", type="string", format="password", example="newpassword")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
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
    public function update(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:255',
                'avatar' => 'nullable|string|max:500',
                'current_password' => 'required_with:new_password|string',
                'new_password' => 'sometimes|string|min:8|confirmed',
                'new_password_confirmation' => 'required_with:new_password|string'
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            // Check current password if changing password
            if ($request->has('new_password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return $this->error('Current password is incorrect', null, 422);
                }
            }

            $updateData = $request->only(['name', 'username', 'email', 'phone', 'avatar']);

            // Update password if provided
            if ($request->has('new_password')) {
                $updateData['password'] = Hash::make($request->new_password);
            }

            $user->update($updateData);
            $user->load('roles', 'permissions');

            // Log profile update
            \App\Models\AuditTrail::create([
                'user_id' => $user->id,
                'action' => 'profile_update',
                'resource_type' => 'User',
                'resource_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'old_values' => $request->only(['name', 'username', 'email', 'phone', 'avatar']),
                'new_values' => $updateData,
                'description' => "User {$user->name} updated their profile",
                'status' => 'success',
                'metadata' => [
                    'updated_fields' => array_keys($updateData),
                ],
                'performed_at' => now(),
            ]);

            return $this->success(new UserResource($user), 'Profile updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update profile', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/profile/permissions",
     *     summary="Get user permissions",
     *     tags={"Profile Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Permissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="roles", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="user"),
     *                     @OA\Property(property="permissions", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="view own profile")
     *                     ))
     *                 )),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="view own profile")
     *                 )),
     *                 @OA\Property(property="can_view_profile", type="boolean", example=true),
     *                 @OA\Property(property="can_edit_profile", type="boolean", example=true)
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
    public function getPermissions()
    {
        try {
            $user = Auth::user();
            $user->load('roles.permissions', 'permissions');

            $roles = $user->roles->map(function ($role) {
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
            });

            $permissions = $user->permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ];
            });

            return $this->success([
                'roles' => $roles,
                'permissions' => $permissions,
                'can_view_profile' => $user->can('view own profile'),
                'can_edit_profile' => $user->can('edit own profile'),
            ], 'Permissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve permissions', $e->getMessage(), 500);
        }
    }
}
