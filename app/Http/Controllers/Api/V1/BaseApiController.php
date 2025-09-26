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
 *
 * @OA\Schema(
 *     schema="Merchant",
 *     title="Merchant",
 *     description="Merchant model for vendors",
 *     @OA\Property(property="id", type="integer", format="int64", description="Merchant ID", example=1),
 *     @OA\Property(property="user_id", type="integer", description="User ID who owns this merchant account", example=1),
 *     @OA\Property(property="business_name", type="string", description="Business name", example="Acme Corporation"),
 *     @OA\Property(property="business_type", type="string", description="Type of business", example="restaurant"),
 *     @OA\Property(property="description", type="string", description="Business description", example="Fine dining restaurant"),
 *     @OA\Property(property="website", type="string", nullable=true, description="Business website", example="https://acme.com"),
 *     @OA\Property(property="phone", type="string", description="Business phone", example="+1234567890"),
 *     @OA\Property(property="email", type="string", format="email", description="Business email", example="contact@acme.com"),
 *     @OA\Property(property="address", type="string", description="Business address", example="123 Main St, City, State 12345"),
 *     @OA\Property(property="city", type="string", description="City", example="New York"),
 *     @OA\Property(property="state", type="string", description="State/Province", example="NY"),
 *     @OA\Property(property="country", type="string", description="Country", example="United States"),
 *     @OA\Property(property="postal_code", type="string", description="Postal code", example="10001"),
 *     @OA\Property(property="latitude", type="number", format="float", description="Latitude", example=40.7128),
 *     @OA\Property(property="longitude", type="number", format="float", description="Longitude", example=-74.0060),
 *     @OA\Property(property="status", type="string", description="Merchant status", enum={"active", "inactive", "suspended", "pending"}, example="active"),
 *     @OA\Property(property="is_verified", type="boolean", description="Whether merchant is verified", example=true),
 *     @OA\Property(property="verification_date", type="string", format="date-time", nullable=true, description="Verification date", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp", example="2025-09-26T07:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="MerchantSetting",
 *     title="Merchant Settings",
 *     description="Merchant settings and configuration",
 *     @OA\Property(property="id", type="integer", format="int64", description="Setting ID", example=1),
 *     @OA\Property(property="merchant_id", type="integer", description="Merchant ID", example=1),
 *     @OA\Property(property="api_key", type="string", description="API key for merchant", example="merchant_api_key_123"),
 *     @OA\Property(property="api_secret", type="string", description="API secret for merchant", example="merchant_api_secret_456"),
 *     @OA\Property(property="webhook_url", type="string", nullable=true, description="Webhook URL for notifications", example="https://merchant.com/webhook"),
 *     @OA\Property(property="callback_url", type="string", nullable=true, description="Callback URL for payments", example="https://merchant.com/callback"),
 *     @OA\Property(property="return_url", type="string", nullable=true, description="Return URL after payment", example="https://merchant.com/return"),
 *     @OA\Property(property="currency", type="string", description="Default currency", example="USD"),
 *     @OA\Property(property="timezone", type="string", description="Merchant timezone", example="America/New_York"),
 *     @OA\Property(property="language", type="string", description="Default language", example="en"),
 *     @OA\Property(property="settings", type="object", description="Additional settings JSON", example={"theme": "dark", "notifications": true}),
 *     @OA\Property(property="is_active", type="boolean", description="Whether settings are active", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp", example="2025-09-26T07:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="Booking",
 *     title="Booking",
 *     description="Booking model for appointments and reservations",
 *     @OA\Property(property="id", type="integer", format="int64", description="Booking ID", example=1),
 *     @OA\Property(property="user_id", type="integer", description="User who made the booking", example=1),
 *     @OA\Property(property="merchant_id", type="integer", description="Merchant providing the service", example=1),
 *     @OA\Property(property="service_name", type="string", description="Name of the service", example="Haircut"),
 *     @OA\Property(property="service_description", type="string", nullable=true, description="Service description", example="Professional haircut and styling"),
 *     @OA\Property(property="booking_date", type="string", format="date", description="Date of booking", example="2025-09-26"),
 *     @OA\Property(property="booking_time", type="string", format="time", description="Time of booking", example="14:30:00"),
 *     @OA\Property(property="duration_minutes", type="integer", description="Duration in minutes", example=60),
 *     @OA\Property(property="price", type="number", format="float", description="Price of the service", example=50.00),
 *     @OA\Property(property="currency", type="string", description="Currency code", example="USD"),
 *     @OA\Property(property="status", type="string", description="Booking status", enum={"pending", "confirmed", "cancelled", "completed", "no_show"}, example="confirmed"),
 *     @OA\Property(property="notes", type="string", nullable=true, description="Additional notes", example="Please arrive 10 minutes early"),
 *     @OA\Property(property="special_requests", type="string", nullable=true, description="Special requests", example="Wheelchair accessible"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp", example="2025-09-26T07:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="UserProfile",
 *     title="User Profile",
 *     description="Extended user profile information",
 *     @OA\Property(property="id", type="integer", format="int64", description="Profile ID", example=1),
 *     @OA\Property(property="user_id", type="integer", description="User ID", example=1),
 *     @OA\Property(property="bio", type="string", nullable=true, description="User biography", example="Software developer with 5 years experience"),
 *     @OA\Property(property="date_of_birth", type="string", format="date", nullable=true, description="Date of birth", example="1990-01-01"),
 *     @OA\Property(property="gender", type="string", nullable=true, description="Gender", enum={"male", "female", "other", "prefer_not_to_say"}, example="male"),
 *     @OA\Property(property="nationality", type="string", nullable=true, description="Nationality", example="American"),
 *     @OA\Property(property="address", type="string", nullable=true, description="Address", example="123 Main St, City, State 12345"),
 *     @OA\Property(property="city", type="string", nullable=true, description="City", example="New York"),
 *     @OA\Property(property="state", type="string", nullable=true, description="State/Province", example="NY"),
 *     @OA\Property(property="country", type="string", nullable=true, description="Country", example="United States"),
 *     @OA\Property(property="postal_code", type="string", nullable=true, description="Postal code", example="10001"),
 *     @OA\Property(property="phone_secondary", type="string", nullable=true, description="Secondary phone", example="+1234567891"),
 *     @OA\Property(property="emergency_contact_name", type="string", nullable=true, description="Emergency contact name", example="Jane Doe"),
 *     @OA\Property(property="emergency_contact_phone", type="string", nullable=true, description="Emergency contact phone", example="+1234567892"),
 *     @OA\Property(property="emergency_contact_relationship", type="string", nullable=true, description="Emergency contact relationship", example="Spouse"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp", example="2025-09-26T07:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="UserSession",
 *     title="User Session",
 *     description="User session information",
 *     @OA\Property(property="id", type="integer", format="int64", description="Session ID", example=1),
 *     @OA\Property(property="user_id", type="integer", description="User ID", example=1),
 *     @OA\Property(property="session_id", type="string", description="Session identifier", example="session_123456"),
 *     @OA\Property(property="ip_address", type="string", description="IP address", example="192.168.1.1"),
 *     @OA\Property(property="user_agent", type="string", description="User agent string", example="Mozilla/5.0..."),
 *     @OA\Property(property="device_type", type="string", description="Device type", enum={"desktop", "mobile", "tablet"}, example="desktop"),
 *     @OA\Property(property="browser", type="string", description="Browser", example="Chrome"),
 *     @OA\Property(property="operating_system", type="string", description="Operating system", example="Windows 10"),
 *     @OA\Property(property="country", type="string", description="Country", example="United States"),
 *     @OA\Property(property="city", type="string", description="City", example="New York"),
 *     @OA\Property(property="status", type="string", description="Session status", enum={"active", "expired", "terminated", "suspended"}, example="active"),
 *     @OA\Property(property="login_at", type="string", format="date-time", description="Login timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="last_activity_at", type="string", format="date-time", description="Last activity timestamp", example="2025-09-26T08:30:00Z"),
 *     @OA\Property(property="logout_at", type="string", format="date-time", nullable=true, description="Logout timestamp", example="2025-09-26T09:30:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp", example="2025-09-26T07:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="UserActivity",
 *     title="User Activity",
 *     description="User activity log",
 *     @OA\Property(property="id", type="integer", format="int64", description="Activity ID", example=1),
 *     @OA\Property(property="user_id", type="integer", description="User ID", example=1),
 *     @OA\Property(property="activity_type", type="string", description="Type of activity", example="login"),
 *     @OA\Property(property="activity_category", type="string", description="Activity category", example="authentication"),
 *     @OA\Property(property="description", type="string", description="Activity description", example="User logged in successfully"),
 *     @OA\Property(property="resource_type", type="string", nullable=true, description="Resource type", example="User"),
 *     @OA\Property(property="resource_id", type="integer", nullable=true, description="Resource ID", example=1),
 *     @OA\Property(property="ip_address", type="string", description="IP address", example="192.168.1.1"),
 *     @OA\Property(property="user_agent", type="string", description="User agent string", example="Mozilla/5.0..."),
 *     @OA\Property(property="status", type="string", description="Activity status", enum={"success", "failed", "warning"}, example="success"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp", example="2025-09-26T07:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="Notification",
 *     title="Notification",
 *     description="User notification",
 *     @OA\Property(property="id", type="integer", format="int64", description="Notification ID", example=1),
 *     @OA\Property(property="user_id", type="integer", description="User ID", example=1),
 *     @OA\Property(property="type", type="string", description="Notification type", example="info"),
 *     @OA\Property(property="category", type="string", description="Notification category", example="system"),
 *     @OA\Property(property="title", type="string", description="Notification title", example="Welcome to Pantheon!"),
 *     @OA\Property(property="message", type="string", description="Notification message", example="Thank you for joining our platform"),
 *     @OA\Property(property="data", type="object", description="Additional data", example={"action_url": "/dashboard"}),
 *     @OA\Property(property="read_at", type="string", format="date-time", nullable=true, description="Read timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="sent_at", type="string", format="date-time", nullable=true, description="Sent timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="priority", type="string", description="Priority level", enum={"low", "normal", "high", "urgent"}, example="normal"),
 *     @OA\Property(property="status", type="string", description="Notification status", enum={"pending", "sent", "delivered", "failed", "cancelled"}, example="delivered"),
 *     @OA\Property(property="is_urgent", type="boolean", description="Whether notification is urgent", example=false),
 *     @OA\Property(property="is_actionable", type="boolean", description="Whether notification has actions", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp", example="2025-09-26T07:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="UserPreference",
 *     title="User Preference",
 *     description="User preference setting",
 *     @OA\Property(property="id", type="integer", format="int64", description="Preference ID", example=1),
 *     @OA\Property(property="user_id", type="integer", description="User ID", example=1),
 *     @OA\Property(property="category", type="string", description="Preference category", example="notifications"),
 *     @OA\Property(property="key", type="string", description="Preference key", example="email_notifications"),
 *     @OA\Property(property="value", type="string", description="Preference value", example="true"),
 *     @OA\Property(property="data_type", type="string", description="Data type", enum={"boolean", "string", "integer", "float", "array", "object"}, example="boolean"),
 *     @OA\Property(property="is_public", type="boolean", description="Whether preference is public", example=false),
 *     @OA\Property(property="description", type="string", nullable=true, description="Preference description", example="Receive email notifications"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp", example="2025-09-26T07:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     title="Validation Error Response",
 *     description="Response for validation errors",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Validation failed"),
 *     @OA\Property(property="errors", type="object", description="Validation error details")
 * )
 *
 * @OA\Schema(
 *     schema="UnauthorizedResponse",
 *     title="Unauthorized Response",
 *     description="Response for unauthorized access",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Unauthenticated")
 * )
 *
 * @OA\Schema(
 *     schema="ForbiddenResponse",
 *     title="Forbidden Response",
 *     description="Response for forbidden access",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Access denied")
 * )
 *
 * @OA\Schema(
 *     schema="NotFoundResponse",
 *     title="Not Found Response",
 *     description="Response for resource not found",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Resource not found")
 * )
 *
 * @OA\Schema(
 *     schema="LockedResponse",
 *     title="Locked Response",
 *     description="Response for locked account",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Account is temporarily locked"),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="locked_until", type="string", format="date-time", example="2025-09-26T10:30:00Z")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="RateLimitResponse",
 *     title="Rate Limit Response",
 *     description="Response for rate limit exceeded",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Too many requests"),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="retry_after", type="integer", example=60)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Role",
 *     title="Role",
 *     description="Role model",
 *     @OA\Property(property="id", type="integer", format="int64", description="Role ID", example=1),
 *     @OA\Property(property="name", type="string", description="Role name", example="admin"),
 *     @OA\Property(property="display_name", type="string", description="Role display name", example="Administrator"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Role description", example="Full system administrator"),
 *     @OA\Property(property="guard_name", type="string", description="Guard name", example="web"),
 *     @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/Permission"), description="Role permissions"),
 *     @OA\Property(property="users_count", type="integer", nullable=true, description="Number of users with this role", example=5),
 *     @OA\Property(property="permissions_count", type="integer", nullable=true, description="Number of permissions for this role", example=15),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp", example="2025-09-26T07:30:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="Permission",
 *     title="Permission",
 *     description="Permission model",
 *     @OA\Property(property="id", type="integer", format="int64", description="Permission ID", example=1),
 *     @OA\Property(property="name", type="string", description="Permission name", example="view users"),
 *     @OA\Property(property="display_name", type="string", description="Permission display name", example="View Users"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Permission description", example="Can view user information"),
 *     @OA\Property(property="category", type="string", nullable=true, description="Permission category", example="user_management"),
 *     @OA\Property(property="guard_name", type="string", description="Guard name", example="web"),
 *     @OA\Property(property="roles", type="array", @OA\Items(ref="#/components/schemas/Role"), description="Roles that have this permission"),
 *     @OA\Property(property="roles_count", type="integer", nullable=true, description="Number of roles with this permission", example=3),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp", example="2025-09-26T07:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp", example="2025-09-26T07:30:00Z")
 * )
 */
class BaseApiController extends Controller
{
    use ApiResponseTrait;
}
