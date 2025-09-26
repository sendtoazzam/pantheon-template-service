<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;
use App\Services\PantheonLoggerService;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check routes (no authentication required)
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'health']);
    Route::get('/detailed', [HealthController::class, 'detailedHealth']);
});

// V1 API routes
Route::prefix('v1')->group(function () {
    
    // Health check routes (no authentication required)
    Route::prefix('health')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\HealthController::class, 'health']);
        Route::get('/detailed', [App\Http\Controllers\Api\V1\HealthController::class, 'detailedHealth']);
    });
    
    // System status routes (no authentication required)
    Route::prefix('system')->group(function () {
        Route::get('/status', [App\Http\Controllers\Api\V1\SystemController::class, 'systemStatus']);
        Route::get('/quick-status', [App\Http\Controllers\Api\V1\SystemController::class, 'quickStatus']);
        Route::get('/metrics', [App\Http\Controllers\Api\V1\SystemController::class, 'metrics']);
    });
    
    // Authentication routes (no auth required)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [App\Http\Controllers\Api\V1\AuthController::class, 'register']);
        Route::post('/login', [App\Http\Controllers\Api\V1\AuthController::class, 'login']);
        
        // Enhanced authentication routes
        Route::post('/login/{guard}', [App\Http\Controllers\Api\V1\EnhancedAuthController::class, 'loginWithGuard']);
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
            Route::get('/me', [App\Http\Controllers\Api\V1\AuthController::class, 'me']);
            Route::post('/refresh', [App\Http\Controllers\Api\V1\AuthController::class, 'refresh']);
            Route::get('/login-history', [App\Http\Controllers\Api\V1\LoginHistoryController::class, 'index']);
            Route::get('/login-statistics', [App\Http\Controllers\Api\V1\LoginHistoryController::class, 'statistics']);
            
            // Enhanced guard management
            Route::get('/guards', [App\Http\Controllers\Api\V1\EnhancedAuthController::class, 'getAvailableGuards']);
            Route::post('/switch-guard', [App\Http\Controllers\Api\V1\EnhancedAuthController::class, 'switchGuard']);
            Route::get('/guard-statistics', [App\Http\Controllers\Api\V1\EnhancedAuthController::class, 'getGuardStatistics']);
        });
    });

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        
        // User routes
        Route::prefix('users')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\UserController::class, 'index']);
            Route::get('/profile', [App\Http\Controllers\Api\V1\UserController::class, 'profile']);
            Route::put('/profile', [App\Http\Controllers\Api\V1\UserController::class, 'updateProfile']);
            Route::get('/{id}', [App\Http\Controllers\Api\V1\UserController::class, 'show']);
            Route::post('/', [App\Http\Controllers\Api\V1\UserController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\Api\V1\UserController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\Api\V1\UserController::class, 'destroy']);
        });

        // Merchant routes
        Route::prefix('merchants')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\MerchantController::class, 'index']);
            Route::get('/profile', [App\Http\Controllers\Api\V1\MerchantController::class, 'profile']);
            Route::put('/profile', [App\Http\Controllers\Api\V1\MerchantController::class, 'updateProfile']);
            Route::get('/settings', [App\Http\Controllers\Api\V1\MerchantController::class, 'settings']);
            Route::put('/settings', [App\Http\Controllers\Api\V1\MerchantController::class, 'updateSettings']);
            Route::get('/{id}', [App\Http\Controllers\Api\V1\MerchantController::class, 'show']);
            Route::post('/', [App\Http\Controllers\Api\V1\MerchantController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\Api\V1\MerchantController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\Api\V1\MerchantController::class, 'destroy']);
        });

        // Booking routes
        Route::prefix('bookings')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\BookingController::class, 'index']);
            Route::get('/my', [App\Http\Controllers\Api\V1\BookingController::class, 'myBookings']);
            Route::get('/merchant', [App\Http\Controllers\Api\V1\BookingController::class, 'merchantBookings']);
            Route::get('/{id}', [App\Http\Controllers\Api\V1\BookingController::class, 'show']);
            Route::post('/', [App\Http\Controllers\Api\V1\BookingController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\Api\V1\BookingController::class, 'update']);
            Route::put('/{id}/status', [App\Http\Controllers\Api\V1\BookingController::class, 'updateStatus']);
            Route::delete('/{id}', [App\Http\Controllers\Api\V1\BookingController::class, 'destroy']);
        });

        // Notification routes
        Route::prefix('notifications')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\NotificationController::class, 'index']);
            Route::get('/unread-count', [App\Http\Controllers\Api\V1\NotificationController::class, 'unreadCount']);
            Route::get('/{id}', [App\Http\Controllers\Api\V1\NotificationController::class, 'show']);
            Route::post('/', [App\Http\Controllers\Api\V1\NotificationController::class, 'store']);
            Route::put('/{id}/read', [App\Http\Controllers\Api\V1\NotificationController::class, 'markAsRead']);
            Route::put('/mark-all-read', [App\Http\Controllers\Api\V1\NotificationController::class, 'markAllAsRead']);
            Route::delete('/{id}', [App\Http\Controllers\Api\V1\NotificationController::class, 'destroy']);
            Route::delete('/clear-all', [App\Http\Controllers\Api\V1\NotificationController::class, 'clearAll']);
        });

        // User Profile routes
        Route::prefix('user-profiles')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\UserProfileController::class, 'index']);
            Route::get('/my', [App\Http\Controllers\Api\V1\UserProfileController::class, 'myProfile']);
            Route::put('/my', [App\Http\Controllers\Api\V1\UserProfileController::class, 'updateMyProfile']);
            Route::get('/{id}', [App\Http\Controllers\Api\V1\UserProfileController::class, 'show']);
            Route::post('/', [App\Http\Controllers\Api\V1\UserProfileController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\Api\V1\UserProfileController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\Api\V1\UserProfileController::class, 'destroy']);
        });

        // User Preference routes
        Route::prefix('user-preferences')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\UserPreferenceController::class, 'index']);
            Route::get('/{key}', [App\Http\Controllers\Api\V1\UserPreferenceController::class, 'show']);
            Route::post('/', [App\Http\Controllers\Api\V1\UserPreferenceController::class, 'store']);
            Route::put('/{key}', [App\Http\Controllers\Api\V1\UserPreferenceController::class, 'update']);
            Route::delete('/{key}', [App\Http\Controllers\Api\V1\UserPreferenceController::class, 'destroy']);
            Route::post('/bulk-update', [App\Http\Controllers\Api\V1\UserPreferenceController::class, 'bulkUpdate']);
        });

        // User Activity routes
        Route::prefix('user-activities')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\UserActivityController::class, 'index']);
            Route::get('/my', [App\Http\Controllers\Api\V1\UserActivityController::class, 'myActivities']);
            Route::get('/statistics', [App\Http\Controllers\Api\V1\UserActivityController::class, 'statistics']);
            Route::get('/{id}', [App\Http\Controllers\Api\V1\UserActivityController::class, 'show']);
            Route::post('/', [App\Http\Controllers\Api\V1\UserActivityController::class, 'store']);
        });

        // Profile routes (permission-based access)
        Route::prefix('profile')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\ProfileController::class, 'show'])->middleware('permission_access:view own profile');
            Route::put('/', [App\Http\Controllers\Api\V1\ProfileController::class, 'update'])->middleware('permission_access:edit own profile');
            Route::get('/permissions', [App\Http\Controllers\Api\V1\ProfileController::class, 'getPermissions']);
        });

        // Admin routes
        Route::prefix('admin')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\Api\V1\AdminController::class, 'dashboard']);
            Route::get('/logs', [App\Http\Controllers\Api\V1\AdminController::class, 'logs']);
            Route::get('/log-stats', [App\Http\Controllers\Api\V1\AdminController::class, 'logStats']);
            Route::post('/cleanup-logs', [App\Http\Controllers\Api\V1\AdminController::class, 'cleanupLogs']);
            Route::get('/roles', [App\Http\Controllers\Api\V1\AdminController::class, 'roles']);
            Route::get('/permissions', [App\Http\Controllers\Api\V1\AdminController::class, 'permissions']);
            Route::post('/assign-role', [App\Http\Controllers\Api\V1\AdminController::class, 'assignRole']);
            Route::post('/remove-role', [App\Http\Controllers\Api\V1\AdminController::class, 'removeRole']);
            Route::post('/give-permission', [App\Http\Controllers\Api\V1\AdminController::class, 'givePermission']);
            Route::post('/revoke-permission', [App\Http\Controllers\Api\V1\AdminController::class, 'revokePermission']);
            Route::get('/user-activity/{userId}', [App\Http\Controllers\Api\V1\AdminController::class, 'userActivity']);
            
            // Enhanced Permission Management
            Route::middleware('role:admin,superadmin')->group(function () {
                Route::get('/permissions', [App\Http\Controllers\Api\V1\PermissionController::class, 'index']);
                Route::get('/roles', [App\Http\Controllers\Api\V1\PermissionController::class, 'getRoles']);
                Route::post('/users/{userId}/assign-role', [App\Http\Controllers\Api\V1\PermissionController::class, 'assignRole']);
                Route::post('/users/{userId}/remove-role', [App\Http\Controllers\Api\V1\PermissionController::class, 'removeRole']);
                Route::post('/users/{userId}/give-permission', [App\Http\Controllers\Api\V1\PermissionController::class, 'givePermission']);
                Route::post('/users/{userId}/revoke-permission', [App\Http\Controllers\Api\V1\PermissionController::class, 'revokePermission']);
                Route::get('/users/{userId}/permissions', [App\Http\Controllers\Api\V1\PermissionController::class, 'getUserPermissions']);
            });
            
            // User Management (Superadmin only)
            Route::middleware('role:superadmin')->group(function () {
                Route::get('/users', [App\Http\Controllers\Api\V1\UserManagementController::class, 'index']);
                Route::post('/users', [App\Http\Controllers\Api\V1\UserManagementController::class, 'store']);
                Route::get('/users/{id}', [App\Http\Controllers\Api\V1\UserManagementController::class, 'show']);
                Route::put('/users/{id}', [App\Http\Controllers\Api\V1\UserManagementController::class, 'update']);
                Route::delete('/users/{id}', [App\Http\Controllers\Api\V1\UserManagementController::class, 'destroy']);
            });
            
            // API Logs Management (Superadmin only)
            Route::middleware('role:superadmin')->group(function () {
                Route::get('/api-logs', [App\Http\Controllers\Api\V1\ApiLogsController::class, 'index']);
                Route::get('/api-logs/statistics', [App\Http\Controllers\Api\V1\ApiLogsController::class, 'statistics']);
                Route::delete('/api-logs/cleanup', [App\Http\Controllers\Api\V1\ApiLogsController::class, 'cleanup']);
                Route::get('/api-logs/{id}', [App\Http\Controllers\Api\V1\ApiLogsController::class, 'show']);
            });
        });
    });
});

// Legacy routes for backward compatibility (moved to end to avoid conflicts)
Route::get('/health', function () {
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
            'SweetAlert2'
        ]
    ];

    PantheonLoggerService::apiResponse('GET', '/api/health', $response, 200);
    PantheonLoggerService::success('Health check endpoint accessed successfully');
    
    return response()->json($response);
});

Route::get('/logs/test', function () {
    PantheonLoggerService::success('This is a success message test');
    PantheonLoggerService::error('This is an error message test');
    PantheonLoggerService::warning('This is a warning message test');
    PantheonLoggerService::info('This is an info message test');
    PantheonLoggerService::debug('This is a debug message test');
    PantheonLoggerService::api('This is an API log message test');
    PantheonLoggerService::activity('This is an activity log message test');
    PantheonLoggerService::userAction(1, 'test_logging', ['test' => true]);
    PantheonLoggerService::systemEvent('logging_test_completed');

    $prefix = config('app_logging.log_prefix')();
    $date = now()->format('Y-m-d');
    
    return response()->json([
        'message' => 'Log test completed. Check the log files in storage/logs/',
        'app_name' => config('app_logging.app_name'),
        'log_prefix' => $prefix,
        'log_files' => [
            "{$prefix}-{$date}.log",
            "{$prefix}-success-{$date}.log",
            "{$prefix}-error-{$date}.log",
            "{$prefix}-warning-{$date}.log",
            "{$prefix}-api-{$date}.log",
            "{$prefix}-activity-{$date}.log"
        ]
    ]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    PantheonLoggerService::userAction($request->user()->id, 'user_profile_accessed');
    return response()->json([
        'success' => true,
        'data' => $request->user(),
        'error' => null
    ]);
});