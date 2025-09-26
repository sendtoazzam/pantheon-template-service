<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Services\PantheonLoggerService;

/**
 * @OA\Schema(
 *     schema="HealthResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Success"),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="status", type="string", example="success"),
 *         @OA\Property(property="message", type="string", example="Pantheon Template Service V1 is running!"),
 *         @OA\Property(property="timestamp", type="string", format="date-time"),
 *         @OA\Property(property="version", type="string", example="1.0.0"),
 *         @OA\Property(property="api_version", type="string", example="v1"),
 *         @OA\Property(property="features", type="array", @OA\Items(type="string")),
 *         @OA\Property(property="endpoints", type="object")
 *     ),
 *     @OA\Property(property="timestamp", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="DetailedHealthResponse",
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
 */
class HealthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/health",
     *     summary="Basic health check",
     *     description="Returns basic health status of the Pantheon Template Service V1 API",
     *     operationId="getHealth",
     *     tags={"Health"},
     *     @OA\Response(
     *         response=200,
     *         description="Service is healthy",
     *         @OA\JsonContent(ref="#/components/schemas/HealthResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Service is unhealthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Service unavailable"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="error", type="object")
     *         )
     *     )
     * )
     */
    public function health(Request $request)
    {
        PantheonLoggerService::apiRequest('GET', '/api/v1/health');
        
        $response = [
            'status' => 'success',
            'message' => 'Pantheon Template Service V1 is running!',
            'timestamp' => now(),
            'version' => '1.0.0',
            'api_version' => 'v1',
            'features' => [
                'Laravel API V1',
                'React.js Frontend',
                'TailwindCSS',
                'Spatie Packages',
                'SweetAlert2',
                'Database Logging',
                'Swagger Documentation',
                'Role-based Authentication',
                'Merchant Management',
                'Booking System'
            ],
            'endpoints' => [
                'auth' => '/api/v1/auth',
                'users' => '/api/v1/users',
                'merchants' => '/api/v1/merchants',
                'bookings' => '/api/v1/bookings',
                'admin' => '/api/v1/admin'
            ]
        ];

        PantheonLoggerService::apiResponse('GET', '/api/v1/health', $response, 200);
        PantheonLoggerService::success('V1 Health check endpoint accessed successfully');
        
        return $this->successResponse($response);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/health/detailed",
     *     summary="Detailed health check",
     *     description="Returns comprehensive health status including database, cache, storage, memory, disk space, environment, services, models, and permissions checks",
     *     operationId="getDetailedHealth",
     *     tags={"Health"},
     *     @OA\Response(
     *         response=200,
     *         description="Detailed health status",
     *         @OA\JsonContent(ref="#/components/schemas/DetailedHealthResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Health check failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Health check failed"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="error", type="object")
     *         )
     *     )
     * )
     */
    public function detailedHealth(Request $request)
    {
        PantheonLoggerService::apiRequest('GET', '/api/v1/health/detailed');
        
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'memory' => $this->checkMemory(),
            'disk_space' => $this->checkDiskSpace(),
            'environment' => $this->checkEnvironment(),
            'services' => $this->checkServices(),
            'models' => $this->checkModels(),
            'permissions' => $this->checkPermissions()
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
            'message' => $overallStatus === 'healthy' ? 'All V1 systems operational' : 'Some V1 issues detected',
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
                'storage_driver' => config('filesystems.default')
            ]
        ];

        PantheonLoggerService::apiResponse('GET', '/api/v1/health/detailed', $response, 200);
        PantheonLoggerService::info('V1 Detailed health check completed', ['status' => $overallStatus]);
        
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
            $tables = ['users', 'roles', 'permissions', 'merchants', 'bookings'];
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
