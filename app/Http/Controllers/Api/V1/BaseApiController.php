<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Pantheon Template Service API",
 *      description="A comprehensive API for the Pantheon Template Service with user management, role-based access control, and multi-tenant support.",
 *      @OA\Contact(
 *          email="support@pantheon-template-service.com"
 *      ),
 *      @OA\License(
 *          name="MIT",
 *          url="https://opensource.org/licenses/MIT"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Pantheon Template Service API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Login with email and password to get the authentication token",
 *     name="Sanctum",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="Sanctum",
 *     securityScheme="bearerAuth",
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", format="int64", description="User ID", example=1),
 *     @OA\Property(property="name", type="string", description="User's full name", example="John Doe"),
 *     @OA\Property(property="username", type="string", description="User's username", example="johndoe"),
 *     @OA\Property(property="email", type="string", format="email", description="User's email address", example="john@example.com"),
 *     @OA\Property(property="phone", type="string", description="User's phone number", example="+1234567890"),
 *     @OA\Property(property="avatar", type="string", nullable=true, description="URL to user's avatar", example="http://example.com/avatars/johndoe.jpg"),
 *     @OA\Property(property="status", type="string", description="User's account status", enum={"active", "inactive", "suspended"}, example="active"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, description="Timestamp of email verification", example="2023-01-01T12:00:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp of user creation", example="2023-01-01T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp of last update", example="2023-01-01T12:00:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="UserWithRoles",
 *     title="User with Roles and Permissions",
 *     description="User model including clean roles and permissions (no pivot data)",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/User"),
 *         @OA\Schema(
 *             @OA\Property(property="roles", type="array", @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="superadmin"),
 *                 @OA\Property(property="guard_name", type="string", example="web")
 *             )),
 *             @OA\Property(property="permissions", type="array", @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="view users"),
 *                 @OA\Property(property="guard_name", type="string", example="web")
 *             ))
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="UserLoginHistory",
 *     title="User Login History",
 *     description="User login history record",
 *     @OA\Property(property="id", type="integer", format="int64", description="Login history ID", example=1),
 *     @OA\Property(property="login_method", type="string", description="Login method used", enum={"email", "username"}, example="email"),
 *     @OA\Property(property="ip_address", type="string", description="IP address of the login", example="192.168.1.1"),
 *     @OA\Property(property="device_type", type="string", description="Device type", enum={"desktop", "mobile", "tablet"}, example="desktop"),
 *     @OA\Property(property="browser", type="string", description="Browser used", example="Chrome 91.0.4472.124"),
 *     @OA\Property(property="os", type="string", description="Operating system", example="Windows 10"),
 *     @OA\Property(property="country", type="string", description="Country of login", example="United States"),
 *     @OA\Property(property="city", type="string", description="City of login", example="New York"),
 *     @OA\Property(property="is_successful", type="boolean", description="Whether login was successful", example=true),
 *     @OA\Property(property="failure_reason", type="string", nullable=true, description="Reason for failed login", example="invalid_credentials"),
 *     @OA\Property(property="login_at", type="string", format="date-time", description="Login timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="logout_at", type="string", format="date-time", nullable=true, description="Logout timestamp", example="2025-09-26T08:30:00Z"),
 *     @OA\Property(property="session_duration_minutes", type="integer", nullable=true, description="Session duration in minutes", example=60),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Record creation timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Record update timestamp", example="2025-09-26T07:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="ApiCallLog",
 *     title="API Call Log",
 *     description="API call log record",
 *     @OA\Property(property="id", type="integer", format="int64", description="Log ID", example=1),
 *     @OA\Property(property="user_id", type="integer", nullable=true, description="User ID who made the call", example=1),
 *     @OA\Property(property="method", type="string", description="HTTP method", enum={"GET", "POST", "PUT", "DELETE", "PATCH"}, example="POST"),
 *     @OA\Property(property="url", type="string", description="Full URL called", example="http://127.0.0.1:8000/api/v1/auth/login"),
 *     @OA\Property(property="endpoint", type="string", description="API endpoint path", example="api/v1/auth/login"),
 *     @OA\Property(property="ip_address", type="string", description="IP address of the caller", example="127.0.0.1"),
 *     @OA\Property(property="user_agent", type="string", description="User agent string", example="Mozilla/5.0..."),
 *     @OA\Property(property="request_headers", type="object", description="Request headers (sanitized)"),
 *     @OA\Property(property="request_body", type="object", description="Request body (sanitized)"),
 *     @OA\Property(property="request_params", type="object", description="Query parameters"),
 *     @OA\Property(property="response_status", type="integer", description="HTTP response status code", example=200),
 *     @OA\Property(property="response_headers", type="object", description="Response headers"),
 *     @OA\Property(property="response_body", type="string", description="Response body (truncated if large)"),
 *     @OA\Property(property="response_size_bytes", type="integer", description="Response size in bytes", example=1024),
 *     @OA\Property(property="response_size_human", type="string", description="Response size in human readable format", example="1.0 KB"),
 *     @OA\Property(property="execution_time_ms", type="number", description="Execution time in milliseconds", example=125.5),
 *     @OA\Property(property="memory_usage_bytes", type="integer", description="Memory usage in bytes", example=2097152),
 *     @OA\Property(property="memory_usage_human", type="string", description="Memory usage in human readable format", example="2.0 MB"),
 *     @OA\Property(property="peak_memory_bytes", type="integer", description="Peak memory usage in bytes", example=4194304),
 *     @OA\Property(property="status", type="string", description="Call status", enum={"success", "error", "warning"}, example="success"),
 *     @OA\Property(property="error_message", type="string", nullable=true, description="Error message if any"),
 *     @OA\Property(property="metadata", type="object", description="Additional metadata"),
 *     @OA\Property(property="called_at", type="string", format="date-time", description="When the API was called", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Record creation timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Record update timestamp", example="2025-09-26T07:30:00Z")
 * )
 */
class BaseApiController extends Controller
{
    use ApiResponseTrait;
}
