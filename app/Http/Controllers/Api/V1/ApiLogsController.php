<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\ApiCallLogResource;
use App\Models\ApiCallLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="API Logs Management",
 *     description="Superadmin-only API endpoints for managing API call logs"
 * )
 */
class ApiLogsController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/api-logs",
     *     summary="Get API call logs (Superadmin only)",
     *     tags={"API Logs Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (max 100)",
     *         required=false,
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Parameter(
     *         name="method",
     *         in="query",
     *         description="Filter by HTTP method",
     *         required=false,
     *         @OA\Schema(type="string", enum={"GET", "POST", "PUT", "DELETE", "PATCH"})
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"success", "error", "warning"})
     *     ),
     *     @OA\Parameter(
     *         name="status_code",
     *         in="query",
     *         description="Filter by HTTP status code",
     *         required=false,
     *         @OA\Schema(type="integer", example=200)
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filter by user ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="ip_address",
     *         in="query",
     *         description="Filter by IP address",
     *         required=false,
     *         @OA\Schema(type="string", example="192.168.1.1")
     *     ),
     *     @OA\Parameter(
     *         name="endpoint",
     *         in="query",
     *         description="Filter by endpoint (partial match)",
     *         required=false,
     *         @OA\Schema(type="string", example="api/v1/auth")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter from date (Y-m-d format)",
     *         required=false,
     *         @OA\Schema(type="string", example="2025-09-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter to date (Y-m-d format)",
     *         required=false,
     *         @OA\Schema(type="string", example="2025-09-30")
     *     ),
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Filter by last N days",
     *         required=false,
     *         @OA\Schema(type="integer", example=7)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="API logs retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="API logs retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="logs", type="array", @OA\Items(ref="#/components/schemas/ApiCallLog")),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=20),
     *                     @OA\Property(property="total", type="integer", example=150),
     *                     @OA\Property(property="last_page", type="integer", example=8)
     *                 ),
     *                 @OA\Property(property="statistics", type="object",
     *                     @OA\Property(property="total_calls", type="integer", example=150),
     *                     @OA\Property(property="successful_calls", type="integer", example=140),
     *                     @OA\Property(property="failed_calls", type="integer", example=10),
     *                     @OA\Property(property="average_response_time", type="number", example=125.5),
     *                     @OA\Property(property="total_data_transferred", type="string", example="2.5 MB")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - Superadmin only",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = ApiCallLog::with('user');

            // Apply filters
            if ($request->has('method')) {
                $query->method($request->method);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('status_code')) {
                $query->statusCode($request->status_code);
            }

            if ($request->has('user_id')) {
                $query->forUser($request->user_id);
            }

            if ($request->has('ip_address')) {
                $query->fromIp($request->ip_address);
            }

            if ($request->has('endpoint')) {
                $query->endpoint($request->endpoint);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->dateRange(
                    Carbon::parse($request->date_from)->startOfDay(),
                    Carbon::parse($request->date_to)->endOfDay()
                );
            } elseif ($request->has('days')) {
                $query->recent($request->days);
            }

            // Get statistics before pagination
            $statistics = $this->getLogStatistics($query->clone());

            // Paginate results
            $perPage = min($request->get('per_page', 20), 100);
            $logs = $query->orderBy('called_at', 'desc')->paginate($perPage);

            return $this->success([
                'logs' => ApiCallLogResource::collection($logs),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                    'from' => $logs->firstItem(),
                    'to' => $logs->lastItem(),
                ],
                'statistics' => $statistics,
            ], 'API logs retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve API logs', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/api-logs/statistics",
     *     summary="Get API call statistics (Superadmin only)",
     *     tags={"API Logs Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Number of days to analyze (default: 30)",
     *         required=false,
     *         @OA\Schema(type="integer", example=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="API statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="API statistics retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="overview", type="object"),
     *                 @OA\Property(property="daily_stats", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="top_endpoints", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="top_users", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="status_codes", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function statistics(Request $request)
    {
        try {
            $days = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($days);

            $query = ApiCallLog::where('called_at', '>=', $startDate);

            // Overview statistics
            $overview = [
                'total_calls' => $query->count(),
                'successful_calls' => $query->clone()->successful()->count(),
                'failed_calls' => $query->clone()->failed()->count(),
                'warning_calls' => $query->clone()->warning()->count(),
                'unique_users' => $query->clone()->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
                'unique_ips' => $query->clone()->distinct('ip_address')->count('ip_address'),
                'average_response_time' => round($query->clone()->avg('execution_time_ms'), 2),
                'total_data_transferred' => $this->formatBytes($query->clone()->sum('response_size_bytes')),
                'peak_memory_usage' => $this->formatBytes($query->clone()->max('peak_memory_bytes')),
            ];

            // Daily statistics
            $dailyStats = $query->clone()
                ->select(
                    DB::raw('DATE(called_at) as date'),
                    DB::raw('COUNT(*) as total_calls'),
                    DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_calls'),
                    DB::raw('SUM(CASE WHEN status = "error" THEN 1 ELSE 0 END) as failed_calls'),
                    DB::raw('AVG(execution_time_ms) as avg_response_time'),
                    DB::raw('SUM(response_size_bytes) as total_data_transferred')
                )
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            // Top endpoints
            $topEndpoints = $query->clone()
                ->select('endpoint', DB::raw('COUNT(*) as call_count'), DB::raw('AVG(execution_time_ms) as avg_response_time'))
                ->groupBy('endpoint')
                ->orderBy('call_count', 'desc')
                ->limit(10)
                ->get();

            // Top users
            $topUsers = $query->clone()
                ->select('user_id', DB::raw('COUNT(*) as call_count'))
                ->whereNotNull('user_id')
                ->with('user:id,name,email')
                ->groupBy('user_id')
                ->orderBy('call_count', 'desc')
                ->limit(10)
                ->get();

            // Status code distribution
            $statusCodes = $query->clone()
                ->select('response_status', DB::raw('COUNT(*) as count'))
                ->groupBy('response_status')
                ->orderBy('count', 'desc')
                ->get();

            return $this->success([
                'overview' => $overview,
                'daily_stats' => $dailyStats,
                'top_endpoints' => $topEndpoints,
                'top_users' => $topUsers,
                'status_codes' => $statusCodes,
            ], 'API statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve API statistics', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/api-logs/cleanup",
     *     summary="Clean up API logs (Superadmin only)",
     *     tags={"API Logs Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"days"},
     *             @OA\Property(property="days", type="integer", description="Number of days to keep (logs older than this will be deleted)", example=14),
     *             @OA\Property(property="dry_run", type="boolean", description="If true, only show what would be deleted without actually deleting", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="API logs cleaned up successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="API logs cleaned up successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="deleted_count", type="integer", example=150),
     *                 @OA\Property(property="remaining_count", type="integer", example=50),
     *                 @OA\Property(property="space_freed", type="string", example="2.5 MB")
     *             )
     *         )
     *     )
     * )
     */
    public function cleanup(Request $request)
    {
        try {
            $request->validate([
                'days' => 'required|integer|min:1|max:365',
                'dry_run' => 'boolean',
            ]);

            $days = $request->days;
            $dryRun = $request->get('dry_run', false);
            $cutoffDate = Carbon::now()->subDays($days);

            $logsToDelete = ApiCallLog::where('called_at', '<', $cutoffDate);
            $countToDelete = $logsToDelete->count();
            $remainingCount = ApiCallLog::where('called_at', '>=', $cutoffDate)->count();

            if ($countToDelete === 0) {
                return $this->success([
                    'deleted_count' => 0,
                    'remaining_count' => $remainingCount,
                    'space_freed' => '0 MB',
                ], 'No logs to delete');
            }

            if ($dryRun) {
                return $this->success([
                    'deleted_count' => $countToDelete,
                    'remaining_count' => $remainingCount,
                    'space_freed' => '0 MB (dry run)',
                    'dry_run' => true,
                ], 'Dry run completed - no logs were actually deleted');
            }

            // Get size before deletion
            $sizeBefore = $this->getLogsSize();
            
            // Delete logs
            $deletedCount = $logsToDelete->delete();
            
            $sizeAfter = $this->getLogsSize();
            $spaceFreed = $sizeBefore - $sizeAfter;

            return $this->success([
                'deleted_count' => $deletedCount,
                'remaining_count' => $remainingCount,
                'space_freed' => $this->formatBytes($spaceFreed * 1024 * 1024),
            ], 'API logs cleaned up successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to clean up API logs', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/api-logs/{id}",
     *     summary="Get specific API call log details (Superadmin only)",
     *     tags={"API Logs Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="API log ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="API log details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="API log details retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/ApiCallLog")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="API log not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $log = ApiCallLog::with('user')->findOrFail($id);
            return $this->success(new ApiCallLogResource($log), 'API log details retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('API log not found', $e->getMessage(), 404);
        }
    }

    /**
     * Get log statistics for the given query
     */
    private function getLogStatistics($query)
    {
        $clone = $query->clone();
        
        return [
            'total_calls' => $clone->count(),
            'successful_calls' => $clone->clone()->successful()->count(),
            'failed_calls' => $clone->clone()->failed()->count(),
            'warning_calls' => $clone->clone()->warning()->count(),
            'average_response_time' => round($clone->clone()->avg('execution_time_ms'), 2),
            'total_data_transferred' => $this->formatBytes($clone->clone()->sum('response_size_bytes')),
        ];
    }

    /**
     * Get total logs size in MB
     */
    private function getLogsSize(): float
    {
        $totalSize = ApiCallLog::sum('response_size_bytes');
        return round($totalSize / 1024 / 1024, 2);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
