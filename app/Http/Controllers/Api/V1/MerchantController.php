<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Merchants",
 *     description="API Endpoints for Merchant Management"
 * )
 */
class MerchantController extends BaseApiController
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/merchants",
     *     summary="Get all merchants",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Merchants retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchants retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $merchants = User::role('vendor')->with('roles', 'permissions')->paginate(15);
            
            return $this->success($merchants, 'Merchants retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve merchants', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/merchants/profile",
     *     summary="Get authenticated merchant profile",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Merchant profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant profile retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole('vendor')) {
                return $this->error('Access denied. Vendor role required.', 403);
            }
            
            $merchant = $user->load('roles', 'permissions');
            
            return $this->success($merchant, 'Merchant profile retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve merchant profile', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/merchants/profile",
     *     summary="Update authenticated merchant profile",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Merchant Name"),
     *             @OA\Property(property="username", type="string", example="merchant"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="avatar", type="string", example="http://example.com/avatar.jpg"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Merchant profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant profile updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole('vendor')) {
                return $this->error('Access denied. Vendor role required.', 403);
            }

            $request->validate([
                'name' => 'sometimes|string|max:255',
                'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
                'phone' => 'sometimes|nullable|string|max:255',
                'avatar' => 'sometimes|nullable|string|max:255',
            ]);

            $user->update($request->only(['name', 'username', 'phone', 'avatar']));

            return $this->success($user->fresh(), 'Merchant profile updated successfully');
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update merchant profile', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/merchants/settings",
     *     summary="Get merchant settings",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Merchant settings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant settings retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="api_key", type="string", example="pk_test_123456789"),
     *                 @OA\Property(property="webhook_url", type="string", example="https://example.com/webhook"),
     *                 @OA\Property(property="settings", type="object", example={"theme": "dark", "notifications": true})
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
    public function settings(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole('vendor')) {
                return $this->error('Access denied. Vendor role required.', 403);
            }

            // Mock settings data - in a real app, this would come from a merchant_settings table
            $settings = [
                'api_key' => 'pk_test_' . str_random(32),
                'webhook_url' => 'https://example.com/webhook',
                'settings' => [
                    'theme' => 'light',
                    'notifications' => true,
                    'auto_approve_bookings' => false,
                ]
            ];

            return $this->success($settings, 'Merchant settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve merchant settings', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/merchants/settings",
     *     summary="Update merchant settings",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="webhook_url", type="string", example="https://example.com/webhook"),
     *             @OA\Property(property="settings", type="object", example={"theme": "dark", "notifications": true})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Merchant settings updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant settings updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function updateSettings(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole('vendor')) {
                return $this->error('Access denied. Vendor role required.', 403);
            }

            $request->validate([
                'webhook_url' => 'sometimes|nullable|url|max:255',
                'settings' => 'sometimes|array',
            ]);

            // In a real app, this would update the merchant_settings table
            // For now, we'll just return success

            return $this->success([], 'Merchant settings updated successfully');
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update merchant settings', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/merchants/{id}",
     *     summary="Get merchant by ID",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Merchant ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Merchant retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Merchant not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $merchant = User::role('vendor')->with('roles', 'permissions')->findOrFail($id);
            
            return $this->success($merchant, 'Merchant retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Merchant not found', 404);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve merchant', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/merchants",
     *     summary="Create a new merchant",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","username"},
     *             @OA\Property(property="name", type="string", example="Merchant Name"),
     *             @OA\Property(property="username", type="string", example="merchant"),
     *             @OA\Property(property="email", type="string", format="email", example="merchant@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Merchant created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant created successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
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
            $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'nullable|string|max:255',
                'password' => 'required|string|min:8',
            ]);

            $merchant = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'status' => 'active',
            ]);

            $merchant->assignRole('vendor');

            return $this->success($merchant, 'Merchant created successfully', 201);
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to create merchant', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/merchants/{id}",
     *     summary="Update merchant by ID",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Merchant ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Merchant Name"),
     *             @OA\Property(property="username", type="string", example="merchant"),
     *             @OA\Property(property="email", type="string", format="email", example="merchant@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Merchant updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Merchant not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'username' => 'sometimes|string|max:255|unique:users,username,' . $id,
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
                'phone' => 'sometimes|nullable|string|max:255',
                'status' => 'sometimes|in:active,inactive,suspended',
            ]);

            $merchant = User::role('vendor')->findOrFail($id);
            $merchant->update($request->only(['name', 'username', 'email', 'phone', 'status']));

            return $this->success($merchant->fresh(), 'Merchant updated successfully');
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Merchant not found', 404);
        } catch (\Exception $e) {
            return $this->error('Failed to update merchant', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/merchants/{id}",
     *     summary="Delete merchant by ID",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Merchant ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Merchant deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Merchant not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $merchant = User::role('vendor')->findOrFail($id);
            $merchant->delete();

            return $this->success([], 'Merchant deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Merchant not found', 404);
        } catch (\Exception $e) {
            return $this->error('Failed to delete merchant', 500, $e->getMessage());
        }
    }
}
