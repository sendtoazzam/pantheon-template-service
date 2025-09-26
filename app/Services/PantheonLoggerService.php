<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PantheonLoggerService
{
    /**
     * Get the log channel name based on type
     */
    private static function getChannelName(string $type): string
    {
        $prefix = config('app_logging.log_prefix')();
        return "{$prefix}-{$type}";
    }

    /**
     * Log success messages
     */
    public static function success(string $message, array $context = []): void
    {
        Log::channel(self::getChannelName('success'))->info($message, $context);
    }

    /**
     * Log error messages
     */
    public static function error(string $message, array $context = []): void
    {
        Log::channel(self::getChannelName('error'))->error($message, $context);
    }

    /**
     * Log warning messages
     */
    public static function warning(string $message, array $context = []): void
    {
        Log::channel(self::getChannelName('warning'))->warning($message, $context);
    }

    /**
     * Log API requests and responses
     */
    public static function api(string $message, array $context = []): void
    {
        Log::channel(self::getChannelName('api'))->info($message, $context);
    }

    /**
     * Log general application messages
     */
    public static function info(string $message, array $context = []): void
    {
        Log::channel(self::getChannelName('main'))->info($message, $context);
    }

    /**
     * Log debug messages
     */
    public static function debug(string $message, array $context = []): void
    {
        Log::channel(self::getChannelName('main'))->debug($message, $context);
    }

    /**
     * Log activity (for Spatie Activity Log integration)
     */
    public static function activity(string $message, array $context = []): void
    {
        Log::channel(self::getChannelName('activity'))->info($message, $context);
    }

    /**
     * Log with custom channel
     */
    public static function channel(string $channel, string $level, string $message, array $context = []): void
    {
        Log::channel($channel)->{$level}($message, $context);
    }

    /**
     * Log API request
     */
    public static function apiRequest(string $method, string $url, array $data = [], int $responseCode = 200): void
    {
        self::api("API Request: {$method} {$url}", [
            'method' => $method,
            'url' => $url,
            'data' => $data,
            'response_code' => $responseCode,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log API response
     */
    public static function apiResponse(string $method, string $url, mixed $response, int $responseCode = 200): void
    {
        self::api("API Response: {$method} {$url}", [
            'method' => $method,
            'url' => $url,
            'response' => $response,
            'response_code' => $responseCode,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log user action
     */
    public static function userAction(int $userId, string $action, array $context = []): void
    {
        self::activity("User Action: {$action}", array_merge($context, [
            'user_id' => $userId,
            'action' => $action,
            'timestamp' => now()->toISOString()
        ]));
    }

    /**
     * Log system event
     */
    public static function systemEvent(string $event, array $context = []): void
    {
        self::info("System Event: {$event}", array_merge($context, [
            'event' => $event,
            'timestamp' => now()->toISOString()
        ]));
    }
}
