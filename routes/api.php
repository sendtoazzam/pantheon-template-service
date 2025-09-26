<?php

use App\Services\PantheonLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    return $request->user();
});
