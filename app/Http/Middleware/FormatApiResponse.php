<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FormatApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $response instanceof JsonResponse &&
            $request->is('api/*')
        ) {
            $original = $response->getData(true);
            
            // Skip if already formatted
            if (isset($original['success']) && (isset($original['data']) || isset($original['error']))) {
                return $response;
            }

            $isHealth = $request->is('api/v1/health') || $request->is('api/v2/health');
            $status = $response->getStatusCode();
            $isSuccess = $status >= 200 && $status < 300;

            $formatted = [
                'success' => $isSuccess,
                'data' => $isSuccess
                    ? (
                        $isHealth
                            ? array_merge($original, [
                                'error' => (object) [],
                                'details' => $original['info'] ?? (object) []
                            ])
                            : $original
                    )
                    : (object) [],
                'error' => $isSuccess
                    ? null
                    : [
                        'message' => $original['message'] ?? 'An error occurred' ?? $original['error'],
                        'details' => $original['errors'] ?? (object) []
                    ],
            ];
            
            return response()->json($formatted, $status);
        }

        return $response;
    }
}
