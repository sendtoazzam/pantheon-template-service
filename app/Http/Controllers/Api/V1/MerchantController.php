<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\MerchantResource;
use App\Models\Merchant;
use App\Models\MerchantSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Merchants",
 *     description="API Endpoints for Merchant Management"
 * )
 */
class MerchantController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/merchants",
     *     summary="Get all merchants",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive", "suspended", "pending"})
     *     ),
     *     @OA\Parameter(
     *         name="business_type",
     *         in="query",
     *         description="Filter by business type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of merchants per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Merchants retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchants retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Merchant"))
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
            $query = Merchant::with(['user', 'settings']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('business_type')) {
                $query->where('business_type', $request->business_type);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('business_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('country', 'like', "%{$search}%");
                });
            }

            // Check if user is admin/superadmin for full access, otherwise only their own
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin'])) {
                $query->where('user_id', $user->id);
            }

            $merchants = $query->paginate($request->get('per_page', 15));

            return $this->paginated($merchants, 'Merchants retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve merchants', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/merchants/profile",
     *     summary="Get current user's merchant profile",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Merchant profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant profile retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Merchant")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Merchant profile not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function profile()
    {
        try {
            $user = Auth::user();
            $merchant = Merchant::with(['user', 'settings'])
                ->where('user_id', $user->id)
                ->first();

            if (!$merchant) {
                return $this->notFound('Merchant profile not found');
            }

            return $this->success(new MerchantResource($merchant), 'Merchant profile retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve merchant profile', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/merchants/profile",
     *     summary="Update current user's merchant profile",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="business_name", type="string", example="Acme Corporation"),
     *             @OA\Property(property="business_type", type="string", example="restaurant"),
     *             @OA\Property(property="description", type="string", example="Fine dining restaurant"),
     *             @OA\Property(property="website", type="string", example="https://acme.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="email", type="string", format="email", example="contact@acme.com"),
     *             @OA\Property(property="address", type="string", example="123 Main St, City, State 12345"),
     *             @OA\Property(property="city", type="string", example="New York"),
     *             @OA\Property(property="state", type="string", example="NY"),
     *             @OA\Property(property="country", type="string", example="United States"),
     *             @OA\Property(property="postal_code", type="string", example="10001"),
     *             @OA\Property(property="latitude", type="number", format="float", example=40.7128),
     *             @OA\Property(property="longitude", type="number", format="float", example=-74.0060)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Merchant profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant profile updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Merchant")
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
            $user = Auth::user();
            $merchant = Merchant::where('user_id', $user->id)->first();

            if (!$merchant) {
                return $this->notFound('Merchant profile not found');
            }

            $validator = Validator::make($request->all(), [
                'business_name' => 'required|string|max:255',
                'business_type' => 'required|string|max:100',
                'description' => 'nullable|string|max:1000',
                'website' => 'nullable|url|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'required|email|max:255',
                'address' => 'required|string|max:500',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'country' => 'required|string|max:100',
                'postal_code' => 'required|string|max:20',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $merchant->update($request->only([
                'business_name', 'business_type', 'description', 'website',
                'phone', 'email', 'address', 'city', 'state', 'country',
                'postal_code', 'latitude', 'longitude'
            ]));

            $merchant->load(['user', 'settings']);

            return $this->success(new MerchantResource($merchant), 'Merchant profile updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update merchant profile', $e->getMessage(), 500);
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
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/MerchantSetting")
     *         )
     *     )
     * )
     */
    public function settings()
    {
        try {
            $user = Auth::user();
            $merchant = Merchant::where('user_id', $user->id)->first();

            if (!$merchant) {
                return $this->notFound('Merchant profile not found');
            }

            $settings = MerchantSetting::where('merchant_id', $merchant->id)->first();

            if (!$settings) {
                // Create default settings
                $settings = MerchantSetting::create([
                    'merchant_id' => $merchant->id,
                    'api_key' => 'merchant_' . uniqid(),
                    'api_secret' => 'secret_' . bin2hex(random_bytes(16)),
                    'currency' => 'USD',
                    'timezone' => 'UTC',
                    'language' => 'en',
                    'settings' => [],
                    'is_active' => true,
                ]);
            }

            return $this->success($settings, 'Merchant settings retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve merchant settings', $e->getMessage(), 500);
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
     *             @OA\Property(property="webhook_url", type="string", example="https://merchant.com/webhook"),
     *             @OA\Property(property="callback_url", type="string", example="https://merchant.com/callback"),
     *             @OA\Property(property="return_url", type="string", example="https://merchant.com/return"),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="timezone", type="string", example="America/New_York"),
     *             @OA\Property(property="language", type="string", example="en"),
     *             @OA\Property(property="settings", type="object", example={"theme": "dark", "notifications": true})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Merchant settings updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant settings updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/MerchantSetting")
     *         )
     *     )
     * )
     */
    public function updateSettings(Request $request)
    {
        try {
            $user = Auth::user();
            $merchant = Merchant::where('user_id', $user->id)->first();

            if (!$merchant) {
                return $this->notFound('Merchant profile not found');
            }

            $validator = Validator::make($request->all(), [
                'webhook_url' => 'nullable|url|max:255',
                'callback_url' => 'nullable|url|max:255',
                'return_url' => 'nullable|url|max:255',
                'currency' => 'required|string|size:3',
                'timezone' => 'required|string|max:50',
                'language' => 'required|string|size:2',
                'settings' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $settings = MerchantSetting::where('merchant_id', $merchant->id)->first();

            if (!$settings) {
                $settings = MerchantSetting::create([
                    'merchant_id' => $merchant->id,
                    'api_key' => 'merchant_' . uniqid(),
                    'api_secret' => 'secret_' . bin2hex(random_bytes(16)),
                    'is_active' => true,
                ]);
            }

            $settings->update($request->only([
                'webhook_url', 'callback_url', 'return_url',
                'currency', 'timezone', 'language', 'settings'
            ]));

            return $this->success($settings, 'Merchant settings updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update merchant settings', $e->getMessage(), 500);
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
     *         description="Merchant ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Merchant retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Merchant")
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
            $merchant = Merchant::with(['user', 'settings'])->find($id);

            if (!$merchant) {
                return $this->notFound('Merchant not found');
            }

            // Check if user can view this merchant
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin']) && $merchant->user_id !== $user->id) {
                return $this->forbidden('Access denied');
            }

            return $this->success(new MerchantResource($merchant), 'Merchant retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve merchant', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/merchants",
     *     summary="Create new merchant",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"business_name","business_type","phone","email","address","city","state","country","postal_code"},
     *             @OA\Property(property="business_name", type="string", example="Acme Corporation"),
     *             @OA\Property(property="business_type", type="string", example="restaurant"),
     *             @OA\Property(property="description", type="string", example="Fine dining restaurant"),
     *             @OA\Property(property="website", type="string", example="https://acme.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="email", type="string", format="email", example="contact@acme.com"),
     *             @OA\Property(property="address", type="string", example="123 Main St, City, State 12345"),
     *             @OA\Property(property="city", type="string", example="New York"),
     *             @OA\Property(property="state", type="string", example="NY"),
     *             @OA\Property(property="country", type="string", example="United States"),
     *             @OA\Property(property="postal_code", type="string", example="10001"),
     *             @OA\Property(property="latitude", type="number", format="float", example=40.7128),
     *             @OA\Property(property="longitude", type="number", format="float", example=-74.0060)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Merchant created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant created successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Merchant")
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
            $user = Auth::user();

            // Check if user already has a merchant profile
            $existingMerchant = Merchant::where('user_id', $user->id)->first();
            if ($existingMerchant) {
                return $this->error('User already has a merchant profile', null, 409);
            }

            $validator = Validator::make($request->all(), [
                'business_name' => 'required|string|max:255',
                'business_type' => 'required|string|max:100',
                'description' => 'nullable|string|max:1000',
                'website' => 'nullable|url|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'required|email|max:255',
                'address' => 'required|string|max:500',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'country' => 'required|string|max:100',
                'postal_code' => 'required|string|max:20',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            DB::beginTransaction();

            $merchant = Merchant::create([
                'user_id' => $user->id,
                'business_name' => $request->business_name,
                'business_type' => $request->business_type,
                'description' => $request->description,
                'website' => $request->website,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'postal_code' => $request->postal_code,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => 'pending',
                'is_verified' => false,
            ]);

            // Create default settings
            MerchantSetting::create([
                'merchant_id' => $merchant->id,
                'api_key' => 'merchant_' . uniqid(),
                'api_secret' => 'secret_' . bin2hex(random_bytes(16)),
                'currency' => 'USD',
                'timezone' => 'UTC',
                'language' => 'en',
                'settings' => [],
                'is_active' => true,
            ]);

            // Update user to be a vendor
            $user->update(['is_vendor' => true]);
            $user->assignRole('vendor');

            DB::commit();

            $merchant->load(['user', 'settings']);

            return $this->created(new MerchantResource($merchant), 'Merchant created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create merchant', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/merchants/{id}",
     *     summary="Update merchant",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Merchant ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="business_name", type="string", example="Updated Business Name"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended", "pending"}),
     *             @OA\Property(property="is_verified", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Merchant updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Merchant")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $merchant = Merchant::find($id);

            if (!$merchant) {
                return $this->notFound('Merchant not found');
            }

            // Check permissions
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin']) && $merchant->user_id !== $user->id) {
                return $this->forbidden('Access denied');
            }

            $validator = Validator::make($request->all(), [
                'business_name' => 'sometimes|string|max:255',
                'business_type' => 'sometimes|string|max:100',
                'description' => 'nullable|string|max:1000',
                'website' => 'nullable|url|max:255',
                'phone' => 'sometimes|string|max:20',
                'email' => 'sometimes|email|max:255',
                'address' => 'sometimes|string|max:500',
                'city' => 'sometimes|string|max:100',
                'state' => 'sometimes|string|max:100',
                'country' => 'sometimes|string|max:100',
                'postal_code' => 'sometimes|string|max:20',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'status' => 'sometimes|in:active,inactive,suspended,pending',
                'is_verified' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $merchant->update($request->only([
                'business_name', 'business_type', 'description', 'website',
                'phone', 'email', 'address', 'city', 'state', 'country',
                'postal_code', 'latitude', 'longitude', 'status', 'is_verified'
            ]));

            if ($request->has('is_verified') && $request->is_verified) {
                $merchant->update(['verification_date' => now()]);
            }

            $merchant->load(['user', 'settings']);

            return $this->success(new MerchantResource($merchant), 'Merchant updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update merchant', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/merchants/{id}",
     *     summary="Delete merchant",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Merchant ID",
     *         required=true,
     *         @OA\Schema(type="integer")
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
            $merchant = Merchant::find($id);

            if (!$merchant) {
                return $this->notFound('Merchant not found');
            }

            // Check permissions - only superadmin can delete merchants
            $user = Auth::user();
            if (!$user->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Only superadmin can delete merchants.');
            }

            DB::beginTransaction();

            // Delete related settings
            MerchantSetting::where('merchant_id', $merchant->id)->delete();

            // Update user to remove vendor status
            $merchant->user->update(['is_vendor' => false]);
            $merchant->user->removeRole('vendor');

            // Delete merchant
            $merchant->delete();

            DB::commit();

            return $this->success([], 'Merchant deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to delete merchant', $e->getMessage(), 500);
        }
    }
}