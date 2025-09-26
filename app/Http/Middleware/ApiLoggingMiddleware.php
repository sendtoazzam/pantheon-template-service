<?php

namespace App\Http\Middleware;

use App\Services\PantheonLoggerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log the incoming request
        PantheonLoggerService::apiRequest(
            $request->method(),
            $request->fullUrl(),
            $request->all(),
            0 // Will be updated after response
        );

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

        // Log the response
        PantheonLoggerService::apiResponse(
            $request->method(),
            $request->fullUrl(),
            [
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $duration,
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true)
            ],
            $response->getStatusCode()
        );

        // Log based on response status
        if ($response->getStatusCode() >= 500) {
            PantheonLoggerService::error("API Error: {$request->method()} {$request->fullUrl()}", [
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $duration
            ]);
        } elseif ($response->getStatusCode() >= 400) {
            PantheonLoggerService::warning("API Warning: {$request->method()} {$request->fullUrl()}", [
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $duration
            ]);
        } else {
            PantheonLoggerService::success("API Success: {$request->method()} {$request->fullUrl()}", [
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $duration
            ]);
        }

        return $response;
    }
}
