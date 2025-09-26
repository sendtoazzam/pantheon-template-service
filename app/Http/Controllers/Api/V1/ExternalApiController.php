<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Merchant;
use App\Models\MerchantSetting;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="External API Integration",
 *     description="API Endpoints for External Warehouse Integration"
 * )
 */
class ExternalApiController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/external/products",
     *     summary="Get products from external warehouse",
     *     tags={"External API Integration"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="merchant_id",
     *         in="query",
     *         description="Merchant ID to get products for",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Product category filter",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for products",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="products", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="merchant_info", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function getProducts(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'merchant_id' => 'required|exists:merchants,id',
                'category' => 'nullable|string|max:100',
                'search' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $merchant = Merchant::with('settings')->find($request->merchant_id);
            if (!$merchant) {
                return $this->notFound('Merchant not found');
            }

            // Check if user has access to this merchant
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin']) && $merchant->user_id !== $user->id) {
                return $this->forbidden('Access denied');
            }

            $merchantSettings = $merchant->settings;
            if (!$merchantSettings) {
                return $this->error('Merchant settings not configured', null, 400);
            }

            // Call external warehouse API
            $response = $this->callExternalApi($merchantSettings, 'products', [
                'category' => $request->category,
                'search' => $request->search,
            ]);

            if (!$response['success']) {
                return $this->error('Failed to fetch products from warehouse', $response['error'], 500);
            }

            return $this->success([
                'products' => $response['data']['products'] ?? [],
                'total' => $response['data']['total'] ?? 0,
                'merchant_info' => [
                    'id' => $merchant->id,
                    'business_name' => $merchant->business_name,
                    'api_configured' => !empty($merchantSettings->api_key),
                ],
            ], 'Products retrieved successfully');

        } catch (\Exception $e) {
            Log::error('External API Products Error: ' . $e->getMessage());
            return $this->error('Failed to retrieve products', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/external/packages",
     *     summary="Get packages from external warehouse",
     *     tags={"External API Integration"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="merchant_id",
     *         in="query",
     *         description="Merchant ID to get packages for",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Package type filter",
     *         required=false,
     *         @OA\Schema(type="string", enum={"umrah", "hajj", "qurban"})
     *     ),
     *     @OA\Parameter(
     *         name="price_min",
     *         in="query",
     *         description="Minimum price filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="price_max",
     *         in="query",
     *         description="Maximum price filter",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Packages retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Packages retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="packages", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(property="types", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */
    public function getPackages(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'merchant_id' => 'required|exists:merchants,id',
                'type' => 'nullable|in:umrah,hajj,qurban',
                'price_min' => 'nullable|numeric|min:0',
                'price_max' => 'nullable|numeric|min:0|gte:price_min',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $merchant = Merchant::with('settings')->find($request->merchant_id);
            if (!$merchant) {
                return $this->notFound('Merchant not found');
            }

            // Check if user has access to this merchant
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin']) && $merchant->user_id !== $user->id) {
                return $this->forbidden('Access denied');
            }

            $merchantSettings = $merchant->settings;
            if (!$merchantSettings) {
                return $this->error('Merchant settings not configured', null, 400);
            }

            // Call external warehouse API
            $response = $this->callExternalApi($merchantSettings, 'packages', [
                'type' => $request->type,
                'price_min' => $request->price_min,
                'price_max' => $request->price_max,
            ]);

            if (!$response['success']) {
                return $this->error('Failed to fetch packages from warehouse', $response['error'], 500);
            }

            return $this->success([
                'packages' => $response['data']['packages'] ?? [],
                'total' => $response['data']['total'] ?? 0,
                'types' => $response['data']['types'] ?? ['umrah', 'hajj', 'qurban'],
            ], 'Packages retrieved successfully');

        } catch (\Exception $e) {
            Log::error('External API Packages Error: ' . $e->getMessage());
            return $this->error('Failed to retrieve packages', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/external/insurance",
     *     summary="Get insurance options from external warehouse",
     *     tags={"External API Integration"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="merchant_id",
     *         in="query",
     *         description="Merchant ID to get insurance for",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="package_id",
     *         in="query",
     *         description="Package ID to get insurance for",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Insurance options retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Insurance options retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="insurance_options", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=10)
     *             )
     *         )
     *     )
     * )
     */
    public function getInsurance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'merchant_id' => 'required|exists:merchants,id',
                'package_id' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $merchant = Merchant::with('settings')->find($request->merchant_id);
            if (!$merchant) {
                return $this->notFound('Merchant not found');
            }

            // Check if user has access to this merchant
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin']) && $merchant->user_id !== $user->id) {
                return $this->forbidden('Access denied');
            }

            $merchantSettings = $merchant->settings;
            if (!$merchantSettings) {
                return $this->error('Merchant settings not configured', null, 400);
            }

            // Call external warehouse API
            $response = $this->callExternalApi($merchantSettings, 'insurance', [
                'package_id' => $request->package_id,
            ]);

            if (!$response['success']) {
                return $this->error('Failed to fetch insurance from warehouse', $response['error'], 500);
            }

            return $this->success([
                'insurance_options' => $response['data']['insurance_options'] ?? [],
                'total' => $response['data']['total'] ?? 0,
            ], 'Insurance options retrieved successfully');

        } catch (\Exception $e) {
            Log::error('External API Insurance Error: ' . $e->getMessage());
            return $this->error('Failed to retrieve insurance options', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/external/resources",
     *     summary="Get resources from external warehouse",
     *     tags={"External API Integration"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="merchant_id",
     *         in="query",
     *         description="Merchant ID to get resources for",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="resource_type",
     *         in="query",
     *         description="Resource type filter",
     *         required=false,
     *         @OA\Schema(type="string", enum={"images", "documents", "videos", "guides"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resources retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Resources retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="resources", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=100)
     *             )
     *         )
     *     )
     * )
     */
    public function getResources(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'merchant_id' => 'required|exists:merchants,id',
                'resource_type' => 'nullable|in:images,documents,videos,guides',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $merchant = Merchant::with('settings')->find($request->merchant_id);
            if (!$merchant) {
                return $this->notFound('Merchant not found');
            }

            // Check if user has access to this merchant
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin']) && $merchant->user_id !== $user->id) {
                return $this->forbidden('Access denied');
            }

            $merchantSettings = $merchant->settings;
            if (!$merchantSettings) {
                return $this->error('Merchant settings not configured', null, 400);
            }

            // Call external warehouse API
            $response = $this->callExternalApi($merchantSettings, 'resources', [
                'resource_type' => $request->resource_type,
            ]);

            if (!$response['success']) {
                return $this->error('Failed to fetch resources from warehouse', $response['error'], 500);
            }

            return $this->success([
                'resources' => $response['data']['resources'] ?? [],
                'total' => $response['data']['total'] ?? 0,
            ], 'Resources retrieved successfully');

        } catch (\Exception $e) {
            Log::error('External API Resources Error: ' . $e->getMessage());
            return $this->error('Failed to retrieve resources', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/external/marketing",
     *     summary="Get marketing materials from external warehouse",
     *     tags={"External API Integration"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="merchant_id",
     *         in="query",
     *         description="Merchant ID to get marketing for",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="campaign_type",
     *         in="query",
     *         description="Campaign type filter",
     *         required=false,
     *         @OA\Schema(type="string", enum={"email", "social", "banner", "brochure"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Marketing materials retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Marketing materials retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="marketing_materials", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=20)
     *             )
     *         )
     *     )
     * )
     */
    public function getMarketing(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'merchant_id' => 'required|exists:merchants,id',
                'campaign_type' => 'nullable|in:email,social,banner,brochure',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $merchant = Merchant::with('settings')->find($request->merchant_id);
            if (!$merchant) {
                return $this->notFound('Merchant not found');
            }

            // Check if user has access to this merchant
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin']) && $merchant->user_id !== $user->id) {
                return $this->forbidden('Access denied');
            }

            $merchantSettings = $merchant->settings;
            if (!$merchantSettings) {
                return $this->error('Merchant settings not configured', null, 400);
            }

            // Call external warehouse API
            $response = $this->callExternalApi($merchantSettings, 'marketing', [
                'campaign_type' => $request->campaign_type,
            ]);

            if (!$response['success']) {
                return $this->error('Failed to fetch marketing materials from warehouse', $response['error'], 500);
            }

            return $this->success([
                'marketing_materials' => $response['data']['marketing_materials'] ?? [],
                'total' => $response['data']['total'] ?? 0,
            ], 'Marketing materials retrieved successfully');

        } catch (\Exception $e) {
            Log::error('External API Marketing Error: ' . $e->getMessage());
            return $this->error('Failed to retrieve marketing materials', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/external/promotions",
     *     summary="Get promotions from external warehouse",
     *     tags={"External API Integration"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="merchant_id",
     *         in="query",
     *         description="Merchant ID to get promotions for",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="active_only",
     *         in="query",
     *         description="Show only active promotions",
     *         required=false,
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Promotions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Promotions retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="promotions", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=15)
     *             )
     *         )
     *     )
     * )
     */
    public function getPromotions(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'merchant_id' => 'required|exists:merchants,id',
                'active_only' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $merchant = Merchant::with('settings')->find($request->merchant_id);
            if (!$merchant) {
                return $this->notFound('Merchant not found');
            }

            // Check if user has access to this merchant
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin']) && $merchant->user_id !== $user->id) {
                return $this->forbidden('Access denied');
            }

            $merchantSettings = $merchant->settings;
            if (!$merchantSettings) {
                return $this->error('Merchant settings not configured', null, 400);
            }

            // Call external warehouse API
            $response = $this->callExternalApi($merchantSettings, 'promotions', [
                'active_only' => $request->boolean('active_only', true),
            ]);

            if (!$response['success']) {
                return $this->error('Failed to fetch promotions from warehouse', $response['error'], 500);
            }

            return $this->success([
                'promotions' => $response['data']['promotions'] ?? [],
                'total' => $response['data']['total'] ?? 0,
            ], 'Promotions retrieved successfully');

        } catch (\Exception $e) {
            Log::error('External API Promotions Error: ' . $e->getMessage());
            return $this->error('Failed to retrieve promotions', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/external/booking/redirect",
     *     summary="Redirect user to merchant booking page",
     *     tags={"External API Integration"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"merchant_id","package_id","user_id"},
     *             @OA\Property(property="merchant_id", type="integer", example=1),
     *             @OA\Property(property="package_id", type="string", example="PKG_UMRAH_001"),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="insurance_id", type="string", example="INS_001"),
     *             @OA\Property(property="special_requests", type="string", example="Wheelchair accessible"),
     *             @OA\Property(property="redirect_url", type="string", example="https://merchant.com/booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Redirect URL generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Redirect URL generated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="redirect_url", type="string", example="https://merchant.com/booking?token=abc123"),
     *                 @OA\Property(property="booking_token", type="string", example="abc123"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2025-09-26T10:30:00Z")
     *             )
     *         )
     *     )
     * )
     */
    public function redirectToBooking(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'merchant_id' => 'required|exists:merchants,id',
                'package_id' => 'required|string|max:100',
                'user_id' => 'required|exists:users,id',
                'insurance_id' => 'nullable|string|max:100',
                'special_requests' => 'nullable|string|max:1000',
                'redirect_url' => 'nullable|url|max:500',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $merchant = Merchant::with('settings')->find($request->merchant_id);
            if (!$merchant) {
                return $this->notFound('Merchant not found');
            }

            $merchantSettings = $merchant->settings;
            if (!$merchantSettings) {
                return $this->error('Merchant settings not configured', null, 400);
            }

            // Generate booking token
            $bookingToken = 'booking_' . uniqid() . '_' . time();
            
            // Store booking session data
            Cache::put("booking_session_{$bookingToken}", [
                'merchant_id' => $request->merchant_id,
                'package_id' => $request->package_id,
                'user_id' => $request->user_id,
                'insurance_id' => $request->insurance_id,
                'special_requests' => $request->special_requests,
                'created_at' => now(),
            ], 3600); // 1 hour expiry

            // Generate redirect URL
            $redirectUrl = $request->redirect_url ?? $merchantSettings->return_url ?? 'https://merchant.com/booking';
            $redirectUrl .= '?' . http_build_query([
                'token' => $bookingToken,
                'merchant_id' => $merchant->id,
                'package_id' => $request->package_id,
                'callback_url' => url('/api/v1/webhooks/booking/callback'),
            ]);

            return $this->success([
                'redirect_url' => $redirectUrl,
                'booking_token' => $bookingToken,
                'expires_at' => now()->addHour()->toISOString(),
            ], 'Redirect URL generated successfully');

        } catch (\Exception $e) {
            Log::error('External API Booking Redirect Error: ' . $e->getMessage());
            return $this->error('Failed to generate redirect URL', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/external/aggregated",
     *     summary="Get aggregated data from multiple external APIs",
     *     tags={"External API Integration"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="merchant_id",
     *         in="query",
     *         description="Merchant ID to get data for",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="endpoints",
     *         in="query",
     *         description="Comma-separated list of endpoints to fetch (products,packages,insurance,resources,marketing,promotions)",
     *         required=true,
     *         @OA\Schema(type="string", example="products,packages,insurance")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term across all endpoints",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Aggregated data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Aggregated data retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="items", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=20),
     *                     @OA\Property(property="total", type="integer", example=100),
     *                     @OA\Property(property="last_page", type="integer", example=5),
     *                     @OA\Property(property="has_more", type="boolean", example=true)
     *                 ),
     *                 @OA\Property(property="endpoints", type="object",
     *                     @OA\Property(property="products", type="object"),
     *                     @OA\Property(property="packages", type="object"),
     *                     @OA\Property(property="insurance", type="object")
     *                 ),
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="total_items", type="integer", example=100),
     *                     @OA\Property(property="successful_endpoints", type="integer", example=3),
     *                     @OA\Property(property="failed_endpoints", type="integer", example=0)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAggregatedData(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'merchant_id' => 'required|exists:merchants,id',
                'endpoints' => 'required|string',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $merchant = Merchant::with('settings')->find($request->merchant_id);
            if (!$merchant) {
                return $this->notFound('Merchant not found');
            }

            // Check if user has access to this merchant
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin']) && $merchant->user_id !== $user->id) {
                return $this->forbidden('Access denied');
            }

            $merchantSettings = $merchant->settings;
            if (!$merchantSettings) {
                return $this->error('Merchant settings not configured', null, 400);
            }

            $endpoints = array_map('trim', explode(',', $request->endpoints));
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);
            $search = $request->get('search');

            // Call multiple APIs in parallel
            $responses = $this->callMultipleApis($merchantSettings, $endpoints, [
                'search' => $search,
                'page' => $page,
                'per_page' => $perPage,
            ]);

            // Aggregate and paginate results
            $aggregatedData = $this->aggregateAndPaginate($responses, $page, $perPage);

            return $this->success($aggregatedData, 'Aggregated data retrieved successfully');

        } catch (\Exception $e) {
            Log::error('External API Aggregated Data Error: ' . $e->getMessage());
            return $this->error('Failed to retrieve aggregated data', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/external/dashboard",
     *     summary="Get dashboard data from all external APIs",
     *     tags={"External API Integration"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="merchant_id",
     *         in="query",
     *         description="Merchant ID to get dashboard data for",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dashboard data retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="overview", type="object",
     *                     @OA\Property(property="total_products", type="integer", example=150),
     *                     @OA\Property(property="total_packages", type="integer", example=25),
     *                     @OA\Property(property="active_promotions", type="integer", example=5),
     *                     @OA\Property(property="available_insurance", type="integer", example=10)
     *                 ),
     *                 @OA\Property(property="recent_items", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="featured_packages", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="active_promotions", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="api_status", type="object",
     *                     @OA\Property(property="products_api", type="string", example="online"),
     *                     @OA\Property(property="packages_api", type="string", example="online"),
     *                     @OA\Property(property="insurance_api", type="string", example="offline")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getDashboardData(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'merchant_id' => 'required|exists:merchants,id',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $merchant = Merchant::with('settings')->find($request->merchant_id);
            if (!$merchant) {
                return $this->notFound('Merchant not found');
            }

            // Check if user has access to this merchant
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin']) && $merchant->user_id !== $user->id) {
                return $this->forbidden('Access denied');
            }

            $merchantSettings = $merchant->settings;
            if (!$merchantSettings) {
                return $this->error('Merchant settings not configured', null, 400);
            }

            // Call all APIs for dashboard data
            $allEndpoints = ['products', 'packages', 'insurance', 'resources', 'marketing', 'promotions'];
            $responses = $this->callMultipleApis($merchantSettings, $allEndpoints, [
                'dashboard' => true,
                'limit' => 10, // Limit for dashboard
            ]);

            // Build dashboard data
            $dashboardData = $this->buildDashboardData($responses, $merchant);

            return $this->success($dashboardData, 'Dashboard data retrieved successfully');

        } catch (\Exception $e) {
            Log::error('External API Dashboard Error: ' . $e->getMessage());
            return $this->error('Failed to retrieve dashboard data', $e->getMessage(), 500);
        }
    }

    /**
     * Call multiple external APIs in parallel
     */
    private function callMultipleApis(MerchantSetting $merchantSettings, array $endpoints, array $params = []): array
    {
        $responses = [];
        $promises = [];

        foreach ($endpoints as $endpoint) {
            $promises[$endpoint] = $this->callExternalApiAsync($merchantSettings, $endpoint, $params);
        }

        // Wait for all requests to complete
        foreach ($promises as $endpoint => $promise) {
            try {
                $responses[$endpoint] = $promise->wait();
            } catch (\Exception $e) {
                $responses[$endpoint] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'data' => [],
                ];
            }
        }

        return $responses;
    }

    /**
     * Call external warehouse API asynchronously
     */
    private function callExternalApiAsync(MerchantSetting $merchantSettings, string $endpoint, array $params = [])
    {
        $baseUrl = config('services.warehouse.base_url', 'https://warehouse-api.example.com');
        $apiKey = $merchantSettings->api_key;
        $apiSecret = $merchantSettings->api_secret;

        if (!$apiKey || !$apiSecret) {
            return new \GuzzleHttp\Promise\RejectedPromise(
                new \Exception('Merchant API credentials not configured')
            );
        }

        $client = new \GuzzleHttp\Client();
        
        return $client->getAsync("{$baseUrl}/api/v1/{$endpoint}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'X-API-Secret' => $apiSecret,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'query' => $params,
            'timeout' => 30,
        ])->then(
            function ($response) {
                return [
                    'success' => true,
                    'data' => json_decode($response->getBody(), true),
                ];
            },
            function ($exception) {
                return [
                    'success' => false,
                    'error' => $exception->getMessage(),
                    'data' => [],
                ];
            }
        );
    }

    /**
     * Aggregate and paginate responses from multiple APIs
     */
    private function aggregateAndPaginate(array $responses, int $page, int $perPage): array
    {
        $allItems = [];
        $endpointData = [];
        $successfulEndpoints = 0;
        $failedEndpoints = 0;

        foreach ($responses as $endpoint => $response) {
            if ($response['success']) {
                $successfulEndpoints++;
                $items = $response['data'][$endpoint] ?? $response['data']['items'] ?? $response['data'];
                
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $item['_source'] = $endpoint;
                        $allItems[] = $item;
                    }
                }
                
                $endpointData[$endpoint] = [
                    'success' => true,
                    'count' => count($items ?? []),
                    'data' => $items ?? [],
                ];
            } else {
                $failedEndpoints++;
                $endpointData[$endpoint] = [
                    'success' => false,
                    'error' => $response['error'],
                    'count' => 0,
                    'data' => [],
                ];
            }
        }

        // Paginate aggregated items
        $total = count($allItems);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = array_slice($allItems, $offset, $perPage);
        $lastPage = ceil($total / $perPage);

        return [
            'items' => $paginatedItems,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'has_more' => $page < $lastPage,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ],
            'endpoints' => $endpointData,
            'summary' => [
                'total_items' => $total,
                'successful_endpoints' => $successfulEndpoints,
                'failed_endpoints' => $failedEndpoints,
                'total_endpoints' => count($responses),
            ],
        ];
    }

    /**
     * Build dashboard data from API responses
     */
    private function buildDashboardData(array $responses, Merchant $merchant): array
    {
        $overview = [
            'total_products' => 0,
            'total_packages' => 0,
            'active_promotions' => 0,
            'available_insurance' => 0,
        ];

        $recentItems = [];
        $featuredPackages = [];
        $activePromotions = [];
        $apiStatus = [];

        foreach ($responses as $endpoint => $response) {
            $apiStatus[$endpoint . '_api'] = $response['success'] ? 'online' : 'offline';
            
            if ($response['success']) {
                $data = $response['data'];
                
                switch ($endpoint) {
                    case 'products':
                        $overview['total_products'] = $data['total'] ?? count($data['products'] ?? []);
                        $recentItems = array_merge($recentItems, array_slice($data['products'] ?? [], 0, 5));
                        break;
                        
                    case 'packages':
                        $overview['total_packages'] = $data['total'] ?? count($data['packages'] ?? []);
                        $featuredPackages = array_slice($data['packages'] ?? [], 0, 3);
                        break;
                        
                    case 'insurance':
                        $overview['available_insurance'] = $data['total'] ?? count($data['insurance_options'] ?? []);
                        break;
                        
                    case 'promotions':
                        $overview['active_promotions'] = $data['total'] ?? count($data['promotions'] ?? []);
                        $activePromotions = array_slice($data['promotions'] ?? [], 0, 3);
                        break;
                }
            }
        }

        return [
            'overview' => $overview,
            'recent_items' => $recentItems,
            'featured_packages' => $featuredPackages,
            'active_promotions' => $activePromotions,
            'api_status' => $apiStatus,
            'merchant_info' => [
                'id' => $merchant->id,
                'business_name' => $merchant->business_name,
                'status' => $merchant->status,
            ],
        ];
    }

    /**
     * Call external warehouse API
     */
    private function callExternalApi(MerchantSetting $merchantSettings, string $endpoint, array $params = []): array
    {
        try {
            $baseUrl = config('services.warehouse.base_url', 'https://warehouse-api.example.com');
            $apiKey = $merchantSettings->api_key;
            $apiSecret = $merchantSettings->api_secret;

            if (!$apiKey || !$apiSecret) {
                return [
                    'success' => false,
                    'error' => 'Merchant API credentials not configured',
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'X-API-Secret' => $apiSecret,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(30)->get("{$baseUrl}/api/v1/{$endpoint}", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->body() ?: 'External API request failed',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
