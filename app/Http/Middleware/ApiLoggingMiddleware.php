<?php

namespace App\Http\Middleware;

use App\Models\ApiCallLog;
use App\Services\LoggerService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $startMemory = memory_get_usage(true);
        
        // Log the incoming request to file (existing functionality)
        LoggerService::apiRequest(
            $request->method(),
            $request->fullUrl(),
            $request->all(),
            0 // Will be updated after response
        );

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

        // Log the response to file (existing functionality)
        LoggerService::apiResponse(
            $request->method(),
            $request->fullUrl(),
            [
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $duration,
                'memory_usage' => $endMemory,
                'peak_memory' => $peakMemory
            ],
            $response->getStatusCode()
        );

        // Store API call in database
        $this->storeApiCallLog($request, $response, $duration, $endMemory, $peakMemory);

        // Log based on response status to file
        if ($response->getStatusCode() >= 500) {
            LoggerService::error("API Error: {$request->method()} {$request->fullUrl()}", [
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $duration
            ]);
        } elseif ($response->getStatusCode() >= 400) {
            LoggerService::warning("API Warning: {$request->method()} {$request->fullUrl()}", [
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $duration
            ]);
        } else {
            LoggerService::success("API Success: {$request->method()} {$request->fullUrl()}", [
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $duration
            ]);
        }

        return $response;
    }

    /**
     * Store API call log in database
     */
    private function storeApiCallLog(Request $request, Response $response, float $duration, int $memoryUsage, int $peakMemory): void
    {
        try {
            // Get response content
            $responseContent = $response->getContent();
            $responseSize = strlen($responseContent);

            // Determine status
            $status = 'success';
            $errorMessage = null;
            
            if ($response->getStatusCode() >= 500) {
                $status = 'error';
                $errorMessage = 'Internal Server Error';
            } elseif ($response->getStatusCode() >= 400) {
                $status = 'warning';
                $errorMessage = 'Client Error';
            }

            // Get user if authenticated
            $userId = null;
            if (Auth::check()) {
                $userId = Auth::id();
            }

            // Prepare request data (exclude sensitive information)
            $requestData = $request->all();
            $this->sanitizeRequestData($requestData);

            // Prepare response data (limit size for large responses)
            $responseData = $responseContent;
            if (strlen($responseData) > 10000) { // Limit to 10KB
                $responseData = substr($responseData, 0, 10000) . '... [TRUNCATED]';
            }

            ApiCallLog::create([
                'user_id' => $userId,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'endpoint' => $request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_headers' => $this->sanitizeHeaders($request->headers->all()),
                'request_body' => $requestData,
                'request_params' => $request->query(),
                'response_status' => $response->getStatusCode(),
                'response_headers' => $this->sanitizeHeaders($response->headers->all()),
                'response_body' => $responseData,
                'response_size_bytes' => $responseSize,
                'execution_time_ms' => $duration,
                'memory_usage_bytes' => $memoryUsage,
                'peak_memory_bytes' => $peakMemory,
                'status' => $status,
                'error_message' => $errorMessage,
                'metadata' => [
                    'route_name' => $request->route()?->getName(),
                    'controller_action' => $request->route()?->getActionName(),
                    'middleware' => $request->route()?->gatherMiddleware(),
                ],
                'called_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log the error but don't break the request
            LoggerService::error('Failed to store API call log', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl(),
            ]);
        }
    }

    /**
     * Sanitize request data to remove sensitive information
     */
    private function sanitizeRequestData(array &$data): void
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_key', 'secret', 'ssn', 'credit_card'];
        
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $this->sanitizeRequestData($data[$key]);
            }
        }
    }

    /**
     * Sanitize headers to remove sensitive information
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];
        
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitiveHeaders)) {
                $headers[$key] = ['[REDACTED]'];
            }
        }
        
        return $headers;
    }
}
