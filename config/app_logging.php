<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Logging Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings for the application logging
    | system. It allows for dynamic log naming based on the application name.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Application Name for Logging
    |--------------------------------------------------------------------------
    |
    | This value is used to generate log file names. It will be converted to
    | a URL-friendly format (lowercase, hyphens instead of spaces).
    |
    */

    'app_name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Log File Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix used for all log files. This will be generated from the
    | application name and converted to a URL-friendly format.
    |
    */

    'log_prefix' => function () {
        $appName = config('app_logging.app_name');
        return strtolower(str_replace([' ', '_'], '-', $appName));
    },

    /*
    |--------------------------------------------------------------------------
    | Log Channels Configuration
    |--------------------------------------------------------------------------
    |
    | Dynamic log channel configuration based on the application name.
    |
    */

    'channels' => [
        'main' => [
            'driver' => 'daily',
            'path' => function () {
                $prefix = config('app_logging.log_prefix')();
                return storage_path("logs/{$prefix}.log");
            },
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 30),
            'replace_placeholders' => true,
        ],

        'success' => [
            'driver' => 'daily',
            'path' => function () {
                $prefix = config('app_logging.log_prefix')();
                return storage_path("logs/{$prefix}-success.log");
            },
            'level' => 'info',
            'days' => env('LOG_DAILY_DAYS', 30),
            'replace_placeholders' => true,
        ],

        'error' => [
            'driver' => 'daily',
            'path' => function () {
                $prefix = config('app_logging.log_prefix')();
                return storage_path("logs/{$prefix}-error.log");
            },
            'level' => 'error',
            'days' => env('LOG_DAILY_DAYS', 30),
            'replace_placeholders' => true,
        ],

        'warning' => [
            'driver' => 'daily',
            'path' => function () {
                $prefix = config('app_logging.log_prefix')();
                return storage_path("logs/{$prefix}-warning.log");
            },
            'level' => 'warning',
            'days' => env('LOG_DAILY_DAYS', 30),
            'replace_placeholders' => true,
        ],

        'api' => [
            'driver' => 'daily',
            'path' => function () {
                $prefix = config('app_logging.log_prefix')();
                return storage_path("logs/{$prefix}-api.log");
            },
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 30),
            'replace_placeholders' => true,
        ],

        'activity' => [
            'driver' => 'daily',
            'path' => function () {
                $prefix = config('app_logging.log_prefix')();
                return storage_path("logs/{$prefix}-activity.log");
            },
            'level' => 'info',
            'days' => env('LOG_DAILY_DAYS', 30),
            'replace_placeholders' => true,
        ],
    ],
];
