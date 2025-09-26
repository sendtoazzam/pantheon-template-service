<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use App\Services\PantheonLoggerService;

/**
 * @OA\Schema(
 *     schema="SystemStatusResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Success"),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="status", type="string", example="healthy"),
 *         @OA\Property(property="message", type="string", example="All V1 systems operational"),
 *         @OA\Property(property="timestamp", type="string", format="date-time"),
 *         @OA\Property(property="version", type="string", example="1.0.0"),
 *         @OA\Property(property="api_version", type="string", example="v1"),
 *         @OA\Property(property="checks", type="object"),
 *         @OA\Property(property="system_info", type="object"),
 *         @OA\Property(property="recommendations", type="array", @OA\Items(type="string"))
 *     ),
 *     @OA\Property(property="timestamp", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="QuickStatusResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Success"),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="status", type="string", example="healthy"),
 *         @OA\Property(property="uptime", type="string", example="13:37  up 23:29, 2 users, load averages: 3.75 3.44 4.08"),
 *         @OA\Property(property="memory_usage", type="number", format="float", example=1.56),
 *         @OA\Property(property="disk_usage", type="number", format="float", example=63.43),
 *         @OA\Property(property="database_status", type="string", example="connected"),
 *         @OA\Property(property="cache_status", type="string", example="working"),
 *         @OA\Property(property="timestamp", type="string", format="date-time")
 *     ),
 *     @OA\Property(property="timestamp", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="MetricsResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Success"),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="memory", type="object",
 *             @OA\Property(property="usage_mb", type="number", format="float", example=45.67),
 *             @OA\Property(property="peak_mb", type="number", format="float", example=67.89),
 *             @OA\Property(property="limit_mb", type="number", format="float", example=512.0)
 *         ),
 *         @OA\Property(property="disk", type="object",
 *             @OA\Property(property="free_gb", type="number", format="float", example=45.67),
 *             @OA\Property(property="total_gb", type="number", format="float", example=500.0),
 *             @OA\Property(property="used_percentage", type="number", format="float", example=63.43)
 *         ),
 *         @OA\Property(property="database", type="object",
 *             @OA\Property(property="connection_time_ms", type="number", format="float", example=2.45),
 *             @OA\Property(property="tables_count", type="integer", example=15)
 *         ),
 *         @OA\Property(property="cache", type="object",
 *             @OA\Property(property="hit_rate", type="number", format="float", example=95.5)
 *         ),
 *         @OA\Property(property="timestamp", type="string", format="date-time")
 *     ),
 *     @OA\Property(property="timestamp", type="string", format="date-time")
 * )
 */
class SystemController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/system/status",
     *     summary="Get comprehensive system status",
     *     description="Returns detailed system status including all health checks, system information, and recommendations",
     *     operationId="getSystemStatus",
     *     tags={"System"},
     *     @OA\Response(
     *         response=200,
     *         description="System status retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SystemStatusResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve system status",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve system status"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="error", type="object")
     *         )
     *     )
     * )
     */
    public function systemStatus(Request $request)
    {
        PantheonLoggerService::apiRequest('GET', '/api/v1/system/status');
        
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'memory' => $this->checkMemory(),
            'disk_space' => $this->checkDiskSpace(),
            'environment' => $this->checkEnvironment(),
            'services' => $this->checkServices(),
            'models' => $this->checkModels(),
            'permissions' => $this->checkPermissions(),
            'migrations' => $this->checkMigrations(),
            'logs' => $this->checkLogs()
        ];

        $overallStatus = 'healthy';
        $hasUnhealthyIssues = false;

        foreach ($checks as $check) {
            if ($check['status'] === 'unhealthy') {
                $overallStatus = 'unhealthy';
                $hasUnhealthyIssues = true;
                break;
            } elseif ($check['status'] === 'degraded') {
                $overallStatus = 'degraded';
            }
        }

        $response = [
            'status' => $overallStatus,
            'message' => $this->getStatusMessage($overallStatus),
            'timestamp' => now(),
            'version' => '1.0.0',
            'api_version' => 'v1',
            'checks' => $checks,
            'system_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_time' => now()->toISOString(),
                'timezone' => config('app.timezone'),
                'environment' => app()->environment(),
                'database_connection' => config('database.default'),
                'cache_driver' => config('cache.default'),
                'storage_driver' => config('filesystems.default'),
                'queue_driver' => config('queue.default'),
                'session_driver' => config('session.driver')
            ],
            'recommendations' => $this->getRecommendations($checks)
        ];

        PantheonLoggerService::apiResponse('GET', '/api/v1/system/status', $response, 200);
        PantheonLoggerService::info('System status check completed', ['status' => $overallStatus]);
        
        return $this->successResponse($response);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/system/quick-status",
     *     summary="Get quick system status",
     *     description="Returns quick system status optimized for dashboard cards with essential metrics",
     *     operationId="getQuickStatus",
     *     tags={"System"},
     *     @OA\Response(
     *         response=200,
     *         description="Quick status retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/QuickStatusResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve quick status",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve quick status"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="error", type="object")
     *         )
     *     )
     * )
     */
    public function quickStatus(Request $request)
    {
        PantheonLoggerService::apiRequest('GET', '/api/v1/system/quick-status');
        
        $response = [
            'status' => 'healthy',
            'uptime' => $this->getUptime(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'database_status' => $this->getDatabaseStatus(),
            'cache_status' => $this->getCacheStatus(),
            'timestamp' => now()
        ];

        PantheonLoggerService::apiResponse('GET', '/api/v1/system/quick-status', $response, 200);
        
        return $this->successResponse($response);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/system/metrics",
     *     summary="Get system metrics",
     *     description="Returns detailed system metrics for charts and monitoring dashboards",
     *     operationId="getSystemMetrics",
     *     tags={"System"},
     *     @OA\Response(
     *         response=200,
     *         description="System metrics retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/MetricsResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve system metrics",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve system metrics"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="error", type="object")
     *         )
     *     )
     * )
     */
    public function metrics(Request $request)
    {
        PantheonLoggerService::apiRequest('GET', '/api/v1/system/metrics');
        
        $response = [
            'memory' => [
                'usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'limit_mb' => round($this->parseMemoryLimit(ini_get('memory_limit')) / 1024 / 1024, 2)
            ],
            'disk' => [
                'free_gb' => round(disk_free_space(storage_path()) / 1024 / 1024 / 1024, 2),
                'total_gb' => round(disk_total_space(storage_path()) / 1024 / 1024 / 1024, 2),
                'used_percentage' => round((disk_total_space(storage_path()) - disk_free_space(storage_path())) / disk_total_space(storage_path()) * 100, 2)
            ],
            'database' => [
                'connection_time_ms' => $this->getDatabaseResponseTime(),
                'tables_count' => $this->getTablesCount()
            ],
            'cache' => [
                'hit_rate' => $this->getCacheHitRate()
            ],
            'timestamp' => now()
        ];

        PantheonLoggerService::apiResponse('GET', '/api/v1/system/metrics', $response, 200);
        
        return $this->successResponse($response);
    }

    /**
     * Check database connection
     */
    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            $queryTime = microtime(true);
            DB::select('SELECT 1');
            $queryTime = (microtime(true) - $queryTime) * 1000;
            
            // Check if required tables exist
            $tables = ['users', 'migrations'];
            $existingTables = DB::select("SHOW TABLES");
            $tableNames = array_map(function($table) {
                return array_values((array)$table)[0];
            }, $existingTables);
            
            $missingTables = array_diff($tables, $tableNames);
            
            return [
                'status' => empty($missingTables) ? 'healthy' : 'degraded',
                'message' => empty($missingTables) ? 'Database connection successful' : 'Database connected but missing tables',
                'details' => [
                    'connection' => config('database.default'),
                    'query_time_ms' => round($queryTime, 2),
                    'missing_tables' => $missingTables,
                    'total_tables' => count($tableNames)
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'details' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Check cache system
     */
    private function checkCache()
    {
        try {
            $testKey = 'v1_health_check_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            if ($retrieved === $testValue) {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache system working',
                    'details' => [
                        'driver' => config('cache.default')
                    ]
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Cache system not working properly',
                    'details' => []
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache system failed',
                'details' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Check storage system
     */
    private function checkStorage()
    {
        try {
            $testFile = 'v1_health_check_' . time() . '.txt';
            $testContent = 'V1 Health check test content';
            
            Storage::put($testFile, $testContent);
            $retrieved = Storage::get($testFile);
            Storage::delete($testFile);
            
            if ($retrieved === $testContent) {
                return [
                    'status' => 'healthy',
                    'message' => 'Storage system working',
                    'details' => [
                        'driver' => config('filesystems.default')
                    ]
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Storage system not working properly',
                    'details' => []
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage system failed',
                'details' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Check memory usage
     */
    private function checkMemory()
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryPercent = ($memoryUsage / $memoryLimit) * 100;
        
        $status = 'healthy';
        if ($memoryPercent > 90) {
            $status = 'unhealthy';
        } elseif ($memoryPercent > 75) {
            $status = 'degraded';
        }
        
        return [
            'status' => $status,
            'message' => 'Memory usage normal',
            'details' => [
                'usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
                'percentage' => round($memoryPercent, 2)
            ]
        ];
    }

    /**
     * Check disk space
     */
    private function checkDiskSpace()
    {
        $diskFree = disk_free_space(storage_path());
        $diskTotal = disk_total_space(storage_path());
        $diskUsed = $diskTotal - $diskFree;
        $diskPercent = ($diskUsed / $diskTotal) * 100;
        
        $status = 'healthy';
        if ($diskPercent > 90) {
            $status = 'unhealthy';
        } elseif ($diskPercent > 75) {
            $status = 'degraded';
        }
        
        return [
            'status' => $status,
            'message' => 'Disk space available',
            'details' => [
                'free_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
                'total_gb' => round($diskTotal / 1024 / 1024 / 1024, 2),
                'used_percentage' => round($diskPercent, 2)
            ]
        ];
    }

    /**
     * Check environment configuration
     */
    private function checkEnvironment()
    {
        $requiredEnvVars = ['APP_KEY', 'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE'];
        $missingVars = [];
        
        foreach ($requiredEnvVars as $var) {
            if (empty(env($var))) {
                $missingVars[] = $var;
            }
        }
        
        $status = empty($missingVars) ? 'healthy' : 'unhealthy';
        
        return [
            'status' => $status,
            'message' => empty($missingVars) ? 'Environment configured properly' : 'Missing required environment variables',
            'details' => [
                'missing_vars' => $missingVars,
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug')
            ]
        ];
    }

    /**
     * Check external services
     */
    private function checkServices()
    {
        $services = [];
        
        // Check if we can connect to external services if configured
        // This is a placeholder for future service checks
        
        return [
            'status' => 'healthy',
            'message' => 'All services operational',
            'details' => $services
        ];
    }

    /**
     * Check if required models exist
     */
    private function checkModels()
    {
        $models = ['User']; // Only check for User model for now
        $existingModels = [];
        $missingModels = [];
        
        foreach ($models as $model) {
            $modelClass = "App\\Models\\{$model}";
            if (class_exists($modelClass)) {
                $existingModels[] = $model;
            } else {
                $missingModels[] = $model;
            }
        }
        
        $status = empty($missingModels) ? 'healthy' : 'degraded';
        
        return [
            'status' => $status,
            'message' => empty($missingModels) ? 'All required models exist' : 'Some models are missing',
            'details' => [
                'existing_models' => $existingModels,
                'missing_models' => $missingModels,
                'note' => 'Additional models (Merchant, Booking, Role, Permission) will be available after running migrations and seeders'
            ]
        ];
    }

    /**
     * Check permissions system
     */
    private function checkPermissions()
    {
        try {
            // Check if Spatie Permission package is working
            if (class_exists('\App\Models\Role') && class_exists('\App\Models\Permission')) {
                $roleCount = \App\Models\Role::count();
                $permissionCount = \App\Models\Permission::count();
                
                return [
                    'status' => 'healthy',
                    'message' => 'Permissions system working',
                    'details' => [
                        'roles_count' => $roleCount,
                        'permissions_count' => $permissionCount
                    ]
                ];
            } else {
                return [
                    'status' => 'degraded',
                    'message' => 'Permissions system not set up',
                    'details' => [
                        'note' => 'Role and Permission models not found. Run migrations and seeders to set up permissions.',
                        'status' => 'Spatie Permission package not configured yet'
                    ]
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'degraded',
                'message' => 'Permissions system not configured',
                'details' => [
                    'note' => 'Permissions system will be available after running migrations and seeders',
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Check migrations status
     */
    private function checkMigrations()
    {
        try {
            $migrations = DB::table('migrations')->count();
            
            return [
                'status' => 'healthy',
                'message' => 'Migrations table accessible',
                'details' => [
                    'migrations_count' => $migrations,
                    'note' => 'Run php artisan migrate to apply pending migrations'
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'degraded',
                'message' => 'Migrations table not accessible',
                'details' => [
                    'error' => $e->getMessage(),
                    'note' => 'Run php artisan migrate:install to create migrations table'
                ]
            ];
        }
    }

    /**
     * Check logs system
     */
    private function checkLogs()
    {
        try {
            $logPath = storage_path('logs');
            $logFiles = glob($logPath . '/*.log');
            
            return [
                'status' => 'healthy',
                'message' => 'Logs system working',
                'details' => [
                    'log_files_count' => count($logFiles),
                    'log_path' => $logPath,
                    'latest_log' => count($logFiles) > 0 ? basename(end($logFiles)) : null
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'degraded',
                'message' => 'Logs system issue',
                'details' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Get status message based on overall status
     */
    private function getStatusMessage($status)
    {
        switch ($status) {
            case 'healthy':
                return 'All V1 systems operational';
            case 'degraded':
                return 'Some V1 issues detected - system functional but needs attention';
            case 'unhealthy':
                return 'Critical V1 issues detected - immediate attention required';
            default:
                return 'Unknown system status';
        }
    }

    /**
     * Get recommendations based on checks
     */
    private function getRecommendations($checks)
    {
        $recommendations = [];
        
        foreach ($checks as $name => $check) {
            if ($check['status'] === 'degraded') {
                switch ($name) {
                    case 'models':
                        $recommendations[] = 'Run migrations and seeders to set up additional models';
                        break;
                    case 'permissions':
                        $recommendations[] = 'Set up Spatie Permission package for role-based access control';
                        break;
                    case 'migrations':
                        $recommendations[] = 'Install migrations table: php artisan migrate:install';
                        break;
                }
            } elseif ($check['status'] === 'unhealthy') {
                switch ($name) {
                    case 'database':
                        $recommendations[] = 'Check database connection and credentials';
                        break;
                    case 'cache':
                        $recommendations[] = 'Check cache configuration and driver';
                        break;
                    case 'storage':
                        $recommendations[] = 'Check storage configuration and permissions';
                        break;
                    case 'memory':
                        $recommendations[] = 'Increase PHP memory limit or optimize memory usage';
                        break;
                    case 'disk_space':
                        $recommendations[] = 'Free up disk space or increase storage capacity';
                        break;
                    case 'environment':
                        $recommendations[] = 'Set missing environment variables';
                        break;
                }
            }
        }
        
        return array_unique($recommendations);
    }

    /**
     * Get uptime information
     */
    private function getUptime()
    {
        try {
            $uptime = shell_exec('uptime');
            return trim($uptime) ?: 'Unable to determine uptime';
        } catch (\Exception $e) {
            return 'Unable to determine uptime';
        }
    }

    /**
     * Get memory usage percentage
     */
    private function getMemoryUsage()
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        return round(($memoryUsage / $memoryLimit) * 100, 2);
    }

    /**
     * Get disk usage percentage
     */
    private function getDiskUsage()
    {
        $diskFree = disk_free_space(storage_path());
        $diskTotal = disk_total_space(storage_path());
        return round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);
    }

    /**
     * Get database status
     */
    private function getDatabaseStatus()
    {
        try {
            DB::select('SELECT 1');
            return 'connected';
        } catch (\Exception $e) {
            return 'disconnected';
        }
    }

    /**
     * Get cache status
     */
    private function getCacheStatus()
    {
        try {
            Cache::put('test', 'test', 1);
            Cache::forget('test');
            return 'working';
        } catch (\Exception $e) {
            return 'not working';
        }
    }

    /**
     * Get database response time
     */
    private function getDatabaseResponseTime()
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            return round((microtime(true) - $start) * 1000, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get tables count
     */
    private function getTablesCount()
    {
        try {
            $tables = DB::select("SHOW TABLES");
            return count($tables);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get cache hit rate (placeholder)
     */
    private function getCacheHitRate()
    {
        // This would require implementing cache hit/miss tracking
        return 95.5; // Placeholder value
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit($limit)
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $limit = (int) $limit;
        
        switch ($last) {
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
        }
        
        return $limit;
    }
}
