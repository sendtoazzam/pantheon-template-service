<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Services\PantheonLoggerService;

class HealthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Basic health check
     */
    public function health(Request $request)
    {
        PantheonLoggerService::apiRequest('GET', '/api/health');
        
        $response = [
            'status' => 'success',
            'message' => 'Pantheon Template Service is running!',
            'timestamp' => now(),
            'version' => '1.0.0',
            'features' => [
                'Laravel API',
                'React.js Frontend',
                'TailwindCSS',
                'Spatie Packages',
                'SweetAlert2',
                'Database Logging',
                'Swagger Documentation'
            ]
        ];

        PantheonLoggerService::apiResponse('GET', '/api/health', $response, 200);
        PantheonLoggerService::success('Health check endpoint accessed successfully');
        
        return $this->successResponse($response);
    }

    /**
     * Detailed health check
     */
    public function detailedHealth(Request $request)
    {
        PantheonLoggerService::apiRequest('GET', '/api/health/detailed');
        
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'memory' => $this->checkMemory(),
            'disk_space' => $this->checkDiskSpace(),
            'environment' => $this->checkEnvironment(),
            'services' => $this->checkServices()
        ];

        $overallStatus = 'healthy';
        $hasIssues = false;

        foreach ($checks as $check) {
            if ($check['status'] !== 'healthy') {
                $overallStatus = 'degraded';
                $hasIssues = true;
            }
        }

        if ($hasIssues) {
            $overallStatus = 'unhealthy';
        }

        $response = [
            'status' => $overallStatus,
            'message' => $overallStatus === 'healthy' ? 'All systems operational' : 'Some issues detected',
            'timestamp' => now(),
            'version' => '1.0.0',
            'checks' => $checks,
            'system_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_time' => now()->toISOString(),
                'timezone' => config('app.timezone'),
                'environment' => app()->environment()
            ]
        ];

        PantheonLoggerService::apiResponse('GET', '/api/health/detailed', $response, 200);
        PantheonLoggerService::info('Detailed health check completed', ['status' => $overallStatus]);
        
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
            
            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'details' => [
                    'connection' => config('database.default'),
                    'query_time_ms' => round($queryTime, 2)
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
            $testKey = 'health_check_' . time();
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
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'Health check test content';
            
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
