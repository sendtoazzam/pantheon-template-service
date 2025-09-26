<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Services\PantheonLoggerService;

trait ApiResponseTrait
{
    /**
     * Success response
     */
    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ];

        PantheonLoggerService::apiResponse(request()->method(), request()->path(), $response, $statusCode);
        
        return response()->json($response, $statusCode);
    }

    /**
     * Error response
     */
    protected function errorResponse(string $message = 'Error', $errors = null, int $statusCode = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => now()->toISOString()
        ];

        PantheonLoggerService::apiResponse(request()->method(), request()->path(), $response, $statusCode);
        
        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, $errors, 422);
    }

    /**
     * Not found response
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, null, 404);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, null, 401);
    }

    /**
     * Forbidden response
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, null, 403);
    }

    /**
     * Server error response
     */
    protected function serverErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, null, 500);
    }

    /**
     * Paginated response
     */
    protected function paginatedResponse($data, string $message = 'Data retrieved successfully'): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'has_more_pages' => $data->hasMorePages()
            ],
            'timestamp' => now()->toISOString()
        ];

        PantheonLoggerService::apiResponse(request()->method(), request()->path(), $response, 200);
        
        return response()->json($response);
    }

    /**
     * Created response
     */
    protected function createdResponse($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Updated response
     */
    protected function updatedResponse($data = null, string $message = 'Resource updated successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 200);
    }

    /**
     * Deleted response
     */
    protected function deletedResponse(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return $this->successResponse(null, $message, 200);
    }

    /**
     * No content response
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    // Alias methods for backward compatibility
    protected function success($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return $this->successResponse($data, $message, $statusCode);
    }

    protected function error(string $message = 'Error', $errors = null, int $statusCode = 400): JsonResponse
    {
        return $this->errorResponse($message, $errors, $statusCode);
    }

    protected function validationError($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->validationErrorResponse($errors, $message);
    }

    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->notFoundResponse($message);
    }

    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->unauthorizedResponse($message);
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->forbiddenResponse($message);
    }

    protected function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->serverErrorResponse($message);
    }

    protected function paginated($data, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return $this->paginatedResponse($data, $message);
    }

    protected function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->createdResponse($data, $message);
    }

    protected function updated($data = null, string $message = 'Resource updated successfully'): JsonResponse
    {
        return $this->updatedResponse($data, $message);
    }

    protected function deleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return $this->deletedResponse($message);
    }

    protected function noContent(): JsonResponse
    {
        return $this->noContentResponse();
    }
}
