<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\MerchantResource;
use App\Models\Merchant;
use App\Models\MerchantSetting;
use App\Models\User;
use App\Services\SecureTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    /**
     * @OA\Post(
     *     path="/api/v1/merchants/create-vendor",
     *     summary="Create vendor with automatic user account (Superadmin only)",
     *     tags={"Merchants"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"business_name","business_type","admin_name","admin_email","admin_username","admin_phone"},
     *             @OA\Property(property="business_name", type="string", example="Acme Corporation"),
     *             @OA\Property(property="business_type", type="string", example="restaurant"),
     *             @OA\Property(property="business_description", type="string", example="Fine dining restaurant"),
     *             @OA\Property(property="business_category", type="string", example="food"),
     *             @OA\Property(property="business_size", type="string", example="medium"),
     *             @OA\Property(property="contact_email", type="string", format="email", example="contact@acme.com"),
     *             @OA\Property(property="contact_phone", type="string", example="+1234567890"),
     *             @OA\Property(property="website", type="string", example="https://acme.com"),
     *             @OA\Property(property="business_address", type="string", example="123 Main St"),
     *             @OA\Property(property="business_city", type="string", example="New York"),
     *             @OA\Property(property="business_state", type="string", example="NY"),
     *             @OA\Property(property="business_country", type="string", example="United States"),
     *             @OA\Property(property="business_postal_code", type="string", example="10001"),
     *             @OA\Property(property="latitude", type="number", format="float", example=40.7128),
     *             @OA\Property(property="longitude", type="number", format="float", example=-74.0060),
     *             @OA\Property(property="admin_name", type="string", example="John Doe"),
     *             @OA\Property(property="admin_email", type="string", format="email", example="admin@acme.com"),
     *             @OA\Property(property="admin_username", type="string", example="acme_admin"),
     *             @OA\Property(property="admin_phone", type="string", example="+1234567890"),
     *             @OA\Property(property="admin_password", type="string", format="password", example="SecurePassword123!"),
     *             @OA\Property(property="send_credentials", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vendor created successfully with admin user",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vendor created successfully with admin user"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="merchant", ref="#/components/schemas/Merchant"),
     *                 @OA\Property(property="admin_user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="admin_token", type="string", example="1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Superadmin access required",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function createVendor(Request $request)
    {
        try {
            // Check if user is superadmin
            $user = Auth::user();
            if (!$user->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Only superadmin can create vendors.');
            }

            $validator = Validator::make($request->all(), [
                // Business information
                'business_name' => 'required|string|max:255',
                'business_type' => 'required|string|max:100',
                'business_description' => 'nullable|string|max:1000',
                'business_category' => 'nullable|string|max:100',
                'business_size' => 'nullable|string|max:50',
                'contact_email' => 'required|email|max:255',
                'contact_phone' => 'required|string|max:20',
                'website' => 'nullable|url|max:255',
                'business_address' => 'required|string|max:500',
                'business_city' => 'required|string|max:100',
                'business_state' => 'required|string|max:100',
                'business_country' => 'required|string|max:100',
                'business_postal_code' => 'required|string|max:20',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                
                // Admin user information
                'admin_name' => 'required|string|max:255',
                'admin_email' => 'required|email|max:255|unique:users,email',
                'admin_username' => 'required|string|max:255|unique:users,username',
                'admin_phone' => 'required|string|max:20',
                'admin_password' => 'nullable|string|min:8|confirmed',
                'send_credentials' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            DB::beginTransaction();

            // Generate admin password if not provided
            $adminPassword = $request->admin_password ?? Str::random(12);
            
            // Create admin user for the vendor
            $adminUser = User::create([
                'name' => $request->admin_name,
                'username' => $request->admin_username,
                'email' => $request->admin_email,
                'phone' => $request->admin_phone,
                'password' => Hash::make($adminPassword),
                'status' => 'active',
                'is_admin' => false,
                'is_vendor' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Assign vendor role to admin user
            $adminUser->assignRole('vendor');

            // Generate business slug
            $businessSlug = Str::slug($request->business_name) . '-' . uniqid();

            // Create merchant
            $merchant = Merchant::create([
                'user_id' => $adminUser->id,
                'business_name' => $request->business_name,
                'business_slug' => $businessSlug,
                'business_description' => $request->business_description,
                'business_type' => $request->business_type,
                'business_category' => $request->business_category,
                'business_size' => $request->business_size,
                'contact_email' => $request->contact_email,
                'contact_phone' => $request->contact_phone,
                'website' => $request->website,
                'business_address' => $request->business_address,
                'business_city' => $request->business_city,
                'business_state' => $request->business_state,
                'business_country' => $request->business_country,
                'business_postal_code' => $request->business_postal_code,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => 'active',
                'verification_status' => 'verified',
                'verified_at' => now(),
                'verified_by' => $user->id,
                'registration_date' => now(),
                'is_subscription_active' => true,
                'subscription_started_at' => now(),
                'subscription_expires_at' => now()->addYear(),
            ]);

            // Create comprehensive merchant settings with dummy API warehouse data
            $merchantSettings = MerchantSetting::create([
                'merchant_id' => $merchant->id,
                
                // API Configuration
                'api_key' => 'merchant_' . Str::random(32),
                'api_secret' => 'secret_' . Str::random(64),
                'api_permissions' => [
                    'products' => ['read', 'write'],
                    'bookings' => ['read', 'write', 'update'],
                    'payments' => ['read', 'write'],
                    'analytics' => ['read'],
                    'notifications' => ['read', 'write'],
                ],
                'api_rate_limit' => 1000, // requests per hour
                'api_enabled' => true,
                'api_key_created_at' => now(),
                'api_key_expires_at' => now()->addYear(),
                
                // Payment Gateway Configuration
                'payment_gateway' => 'stripe',
                'payment_gateway_key' => 'pk_test_' . Str::random(24),
                'payment_gateway_secret' => 'sk_test_' . Str::random(48),
                'payment_gateway_webhook_secret' => 'whsec_' . Str::random(32),
                'payment_methods' => ['card', 'bank_transfer', 'digital_wallet'],
                'payment_enabled' => true,
                'payment_processing_fee' => 2.9, // percentage
                'minimum_payment_amount' => 1.00,
                'maximum_payment_amount' => 10000.00,
                
                // Notification Settings
                'notification_email' => $request->admin_email,
                'notification_phone' => $request->admin_phone,
                'email_notifications_enabled' => true,
                'sms_notifications_enabled' => true,
                'push_notifications_enabled' => true,
                'notification_preferences' => [
                    'new_booking' => true,
                    'payment_received' => true,
                    'booking_cancelled' => true,
                    'low_stock' => true,
                    'daily_summary' => true,
                ],
                
                // Business Hours
                'business_hours' => [
                    'monday' => ['is_open' => true, 'open' => '09:00', 'close' => '18:00'],
                    'tuesday' => ['is_open' => true, 'open' => '09:00', 'close' => '18:00'],
                    'wednesday' => ['is_open' => true, 'open' => '09:00', 'close' => '18:00'],
                    'thursday' => ['is_open' => true, 'open' => '09:00', 'close' => '18:00'],
                    'friday' => ['is_open' => true, 'open' => '09:00', 'close' => '18:00'],
                    'saturday' => ['is_open' => true, 'open' => '10:00', 'close' => '16:00'],
                    'sunday' => ['is_open' => false, 'open' => '00:00', 'close' => '00:00'],
                ],
                'is_24_hours' => false,
                'timezone' => 'America/New_York',
                
                // Order Management
                'auto_accept_orders' => false,
                'order_preparation_time' => 30, // minutes
                'max_orders_per_hour' => 50,
                
                // Delivery Settings
                'delivery_enabled' => true,
                'pickup_enabled' => true,
                'delivery_fee' => 5.99,
                'free_delivery_threshold' => 50.00,
                'delivery_radius' => 10, // miles
                'delivery_time_min' => 30, // minutes
                'delivery_time_max' => 60, // minutes
                'delivery_zones' => [
                    ['name' => 'Zone 1', 'radius' => 5, 'fee' => 3.99],
                    ['name' => 'Zone 2', 'radius' => 10, 'fee' => 5.99],
                    ['name' => 'Zone 3', 'radius' => 15, 'fee' => 8.99],
                ],
                'pickup_locations' => [
                    ['name' => 'Main Store', 'address' => $request->business_address, 'phone' => $request->contact_phone],
                ],
                
                // Inventory Management
                'inventory_tracking_enabled' => true,
                'low_stock_alerts' => true,
                'low_stock_threshold' => 10,
                'auto_out_of_stock' => false,
                'allow_backorders' => true,
                'product_categories' => ['food', 'beverages', 'desserts', 'appetizers'],
                
                // Order Settings
                'max_order_items' => 20,
                'minimum_order_amount' => 10.00,
                'maximum_order_amount' => 500.00,
                'require_customer_info' => true,
                'allow_guest_checkout' => true,
                'order_hold_time' => 15, // minutes
                'auto_cancel_unpaid_orders' => true,
                'auto_cancel_time' => 30, // minutes
                
                // Marketing & Promotions
                'promotions_enabled' => true,
                'promotion_settings' => [
                    'discount_types' => ['percentage', 'fixed_amount', 'buy_one_get_one'],
                    'max_discount_percentage' => 50,
                    'promotion_duration_days' => 30,
                ],
                'loyalty_program_enabled' => true,
                'loyalty_settings' => [
                    'points_per_dollar' => 1,
                    'points_for_signup' => 100,
                    'points_for_referral' => 50,
                    'redemption_rate' => 100, // points per dollar
                ],
                
                // Email Marketing
                'email_marketing_enabled' => true,
                'email_marketing_provider' => 'mailchimp',
                'email_marketing_settings' => [
                    'api_key' => 'mailchimp_' . Str::random(32),
                    'list_id' => 'list_' . Str::random(16),
                    'automation_enabled' => true,
                ],
                
                // Analytics
                'analytics_enabled' => true,
                'google_analytics_id' => 'GA-' . Str::random(10),
                'facebook_pixel_id' => 'pixel_' . Str::random(16),
                'custom_tracking_codes' => [
                    'google_tag_manager' => 'GTM-' . Str::random(8),
                    'hotjar' => 'hotjar_' . Str::random(12),
                ],
                'sales_reporting_enabled' => true,
                'customer_analytics_enabled' => true,
                
                // Security Settings
                'two_factor_auth_enabled' => false,
                'ip_whitelist_enabled' => false,
                'allowed_ip_addresses' => [],
                'session_timeout_enabled' => true,
                'session_timeout_minutes' => 480, // 8 hours
                'password_policy_enabled' => true,
                'password_policy_settings' => [
                    'min_length' => 8,
                    'require_uppercase' => true,
                    'require_lowercase' => true,
                    'require_numbers' => true,
                    'require_symbols' => true,
                ],
                
                // Third-party Integrations
                'third_party_integrations' => [
                    'pos_system' => 'square',
                    'accounting' => 'quickbooks',
                    'crm' => 'hubspot',
                    'inventory' => 'tradegecko',
                ],
                'pos_system' => 'square',
                'pos_settings' => [
                    'api_key' => 'square_' . Str::random(32),
                    'location_id' => 'loc_' . Str::random(16),
                    'webhook_secret' => 'whsec_' . Str::random(32),
                ],
                'accounting_system' => 'quickbooks',
                'accounting_settings' => [
                    'client_id' => 'qb_' . Str::random(32),
                    'client_secret' => 'qb_secret_' . Str::random(48),
                    'company_id' => 'company_' . Str::random(16),
                ],
                'crm_system' => 'hubspot',
                'crm_settings' => [
                    'api_key' => 'hubspot_' . Str::random(32),
                    'portal_id' => 'portal_' . Str::random(16),
                ],
                
                // Custom Fields
                'custom_fields' => [
                    'special_instructions' => 'text',
                    'dietary_restrictions' => 'select',
                    'delivery_notes' => 'textarea',
                ],
                
                // Theme Settings
                'theme_settings' => [
                    'primary_color' => '#3B82F6',
                    'secondary_color' => '#1E40AF',
                    'logo_url' => null,
                    'favicon_url' => null,
                ],
                
                // Feature Flags
                'feature_flags' => [
                    'advanced_analytics' => true,
                    'multi_location' => false,
                    'subscription_management' => true,
                    'api_webhooks' => true,
                ],
                
                // Experimental Features
                'experimental_features' => [
                    'ai_recommendations' => false,
                    'voice_ordering' => false,
                    'ar_menu' => false,
                ],
            ]);

            // Generate token for admin user
            $adminToken = SecureTokenService::generateSecureToken(64);
            $adminUser->tokens()->create([
                'name' => 'vendor-admin-token',
                'token' => hash('sha256', $adminToken),
                'abilities' => ['*'],
            ]);

            DB::commit();

            // Load relationships
            $merchant->load(['user', 'settings']);
            $adminUser->load(['roles', 'permissions']);

            $responseData = [
                'merchant' => new MerchantResource($merchant),
                'admin_user' => [
                    'id' => $adminUser->id,
                    'name' => $adminUser->name,
                    'username' => $adminUser->username,
                    'email' => $adminUser->email,
                    'phone' => $adminUser->phone,
                    'roles' => $adminUser->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'guard_name' => $role->guard_name,
                        ];
                    }),
                    'permissions' => $adminUser->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'guard_name' => $permission->guard_name,
                        ];
                    }),
                ],
                'admin_token' => $adminToken,
                'admin_password' => $adminPassword,
            ];

            return $this->created($responseData, 'Vendor created successfully with admin user');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create vendor', $e->getMessage(), 500);
        }
    }
}