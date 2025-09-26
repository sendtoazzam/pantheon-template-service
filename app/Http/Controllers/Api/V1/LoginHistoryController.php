<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\UserLoginHistoryResource;
use App\Services\LoginHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Login History",
 *     description="API Endpoints for User Login History"
 * )
 */
class LoginHistoryController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/auth/login-history",
     *     summary="Get user login history",
     *     tags={"Login History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Number of days to look back (default: 30)",
     *         required=false,
     *         @OA\Schema(type="integer", example=30)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of records to return (default: 50)",
     *         required=false,
     *         @OA\Schema(type="integer", example=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login history retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login history retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="login_history", type="array", @OA\Items(ref="#/components/schemas/UserLoginHistory")),
     *                 @OA\Property(property="statistics", type="object",
     *                     @OA\Property(property="total_logins", type="integer", example=25),
     *                     @OA\Property(property="successful_logins", type="integer", example=23),
     *                     @OA\Property(property="failed_logins", type="integer", example=2),
     *                     @OA\Property(property="unique_ips", type="integer", example=3),
     *                     @OA\Property(property="unique_devices", type="integer", example=2),
     *                     @OA\Property(property="average_session_duration", type="number", example=45.5),
     *                     @OA\Property(property="last_login", type="string", format="date-time", example="2025-09-26T07:30:00Z")
     *                 )
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
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $days = $request->query('days', 30);
            $limit = $request->query('limit', 50);

            $loginHistory = LoginHistoryService::getUserLoginHistory($user, $days, $limit);
            $statistics = LoginHistoryService::getLoginStatistics($user, $days);

            return $this->success([
                'login_history' => UserLoginHistoryResource::collection($loginHistory),
                'statistics' => $statistics,
            ], 'Login history retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve login history', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/login-statistics",
     *     summary="Get user login statistics",
     *     tags={"Login History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Number of days to look back (default: 30)",
     *         required=false,
     *         @OA\Schema(type="integer", example=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login statistics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_logins", type="integer", example=25),
     *                 @OA\Property(property="successful_logins", type="integer", example=23),
     *                 @OA\Property(property="failed_logins", type="integer", example=2),
     *                 @OA\Property(property="unique_ips", type="integer", example=3),
     *                 @OA\Property(property="unique_devices", type="integer", example=2),
     *                 @OA\Property(property="average_session_duration", type="number", example=45.5),
     *                 @OA\Property(property="last_login", type="string", format="date-time", example="2025-09-26T07:30:00Z")
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
    public function statistics(Request $request)
    {
        try {
            $user = Auth::user();
            $days = $request->query('days', 30);

            $statistics = LoginHistoryService::getLoginStatistics($user, $days);

            return $this->success($statistics, 'Login statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve login statistics', $e->getMessage(), 500);
        }
    }
}
