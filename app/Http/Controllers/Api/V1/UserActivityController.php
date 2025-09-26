<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\UserActivityResource;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="User Activities",
 *     description="API Endpoints for User Activity Tracking"
 * )
 */
class UserActivityController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/user-activities",
     *     summary="Get user activities",
     *     tags={"User Activities"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="activity_type",
     *         in="query",
     *         description="Filter by activity type",
     *         required=false,
     *         @OA\Schema(type="string", example="login")
     *     ),
     *     @OA\Parameter(
     *         name="activity_category",
     *         in="query",
     *         description="Filter by activity category",
     *         required=false,
     *         @OA\Schema(type="string", enum={"authentication", "profile", "booking", "payment", "system"})
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"success", "failed", "warning"})
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter activities from date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter activities to date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of activities per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User activities retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User activities retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/UserActivity"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Check if user can view all activities (admin/superadmin) or only their own
            $query = UserActivity::query();
            
            if (!$user->hasRole(['admin', 'superadmin'])) {
                $query->where('user_id', $user->id);
            } elseif ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Apply filters
            if ($request->has('activity_type')) {
                $query->where('activity_type', $request->activity_type);
            }

            if ($request->has('activity_category')) {
                $query->where('activity_category', $request->activity_category);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('activity_type', 'like', "%{$search}%")
                      ->orWhere('resource_type', 'like', "%{$search}%");
                });
            }

            $activities = $query->orderBy('created_at', 'desc')
                              ->paginate($request->get('per_page', 15));

            return $this->paginated($activities, 'User activities retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user activities', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user-activities/my",
     *     summary="Get current user's activities",
     *     tags={"User Activities"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User activities retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User activities retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/UserActivity"))
     *         )
     *     )
     * )
     */
    public function myActivities(Request $request)
    {
        try {
            $user = Auth::user();
            
            $activities = UserActivity::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return $this->paginated($activities, 'User activities retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user activities', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user-activities/{id}",
     *     summary="Get user activity by ID",
     *     tags={"User Activities"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Activity ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User activity retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User activity retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserActivity")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            
            $activity = UserActivity::find($id);

            if (!$activity) {
                return $this->notFound('Activity not found');
            }

            // Check permissions
            if (!$user->hasRole(['admin', 'superadmin']) && $activity->user_id !== $user->id) {
                return $this->forbidden('Access denied');
            }

            return $this->success(new UserActivityResource($activity), 'User activity retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user activity', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user-activities",
     *     summary="Create user activity",
     *     tags={"User Activities"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"activity_type","activity_category","description"},
     *             @OA\Property(property="activity_type", type="string", example="profile_update"),
     *             @OA\Property(property="activity_category", type="string", enum={"authentication", "profile", "booking", "payment", "system"}, example="profile"),
     *             @OA\Property(property="description", type="string", example="User updated their profile information"),
     *             @OA\Property(property="resource_type", type="string", example="User"),
     *             @OA\Property(property="resource_id", type="integer", example=1),
     *             @OA\Property(property="ip_address", type="string", example="192.168.1.1"),
     *             @OA\Property(property="user_agent", type="string", example="Mozilla/5.0..."),
     *             @OA\Property(property="status", type="string", enum={"success", "failed", "warning"}, example="success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User activity created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User activity created successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserActivity")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'activity_type' => 'required|string|max:100',
                'activity_category' => 'required|in:authentication,profile,booking,payment,system',
                'description' => 'required|string|max:500',
                'resource_type' => 'nullable|string|max:100',
                'resource_id' => 'nullable|integer',
                'ip_address' => 'nullable|ip',
                'user_agent' => 'nullable|string|max:500',
                'status' => 'required|in:success,failed,warning',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $activity = UserActivity::create([
                'user_id' => $user->id,
                'activity_type' => $request->activity_type,
                'activity_category' => $request->activity_category,
                'description' => $request->description,
                'resource_type' => $request->resource_type,
                'resource_id' => $request->resource_id,
                'ip_address' => $request->ip_address ?? $request->ip(),
                'user_agent' => $request->user_agent ?? $request->userAgent(),
                'status' => $request->status,
            ]);

            return $this->created(new UserActivityResource($activity), 'User activity created successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to create user activity', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user-activities/statistics",
     *     summary="Get user activity statistics",
     *     tags={"User Activities"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="User ID (admin/superadmin only)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Number of days to look back",
     *         required=false,
     *         @OA\Schema(type="integer", default=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Activity statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Activity statistics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_activities", type="integer", example=150),
     *                 @OA\Property(property="successful_activities", type="integer", example=140),
     *                 @OA\Property(property="failed_activities", type="integer", example=10),
     *                 @OA\Property(property="activities_by_category", type="object"),
     *                 @OA\Property(property="activities_by_type", type="object"),
     *                 @OA\Property(property="daily_activities", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function statistics(Request $request)
    {
        try {
            $user = Auth::user();
            $targetUserId = $user->id;
            
            // Check if admin/superadmin is requesting stats for another user
            if ($request->has('user_id') && $user->hasRole(['admin', 'superadmin'])) {
                $targetUserId = $request->user_id;
            }

            $days = $request->get('days', 30);
            $startDate = now()->subDays($days);

            $query = UserActivity::where('user_id', $targetUserId)
                ->where('created_at', '>=', $startDate);

            $totalActivities = $query->count();
            $successfulActivities = $query->where('status', 'success')->count();
            $failedActivities = $query->where('status', 'failed')->count();

            // Activities by category
            $activitiesByCategory = $query->selectRaw('activity_category, COUNT(*) as count')
                ->groupBy('activity_category')
                ->pluck('count', 'activity_category')
                ->toArray();

            // Activities by type
            $activitiesByType = $query->selectRaw('activity_type, COUNT(*) as count')
                ->groupBy('activity_type')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'activity_type')
                ->toArray();

            // Daily activities
            $dailyActivities = $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function($item) {
                    return [
                        'date' => $item->date,
                        'count' => $item->count,
                    ];
                });

            $statistics = [
                'total_activities' => $totalActivities,
                'successful_activities' => $successfulActivities,
                'failed_activities' => $failedActivities,
                'warning_activities' => $query->where('status', 'warning')->count(),
                'activities_by_category' => $activitiesByCategory,
                'activities_by_type' => $activitiesByType,
                'daily_activities' => $dailyActivities,
                'period_days' => $days,
                'success_rate' => $totalActivities > 0 ? round(($successfulActivities / $totalActivities) * 100, 2) : 0,
            ];

            return $this->success($statistics, 'Activity statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve activity statistics', $e->getMessage(), 500);
        }
    }
}
