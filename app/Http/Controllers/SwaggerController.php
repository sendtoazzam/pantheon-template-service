<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Pantheon Template Service API",
 *     version="1.0.0",
 *     description="A comprehensive API for the Pantheon Template Service with user management, role-based access control, and multi-tenant support.",
 *     @OA\Contact(
 *         email="support@pantheon-template-service.com",
 *         name="API Support",
 *         url="https://pantheon-template-service.com/support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     ),
 *     @OA\Server(
 *         url="http://127.0.0.1:8000",
 *         description="Local Development Server"
 *     ),
 *     @OA\Server(
 *         url="https://api.pantheon-template-service.com",
 *         description="Production Server"
 *     )
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum API Token Authentication"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="csrf",
 *     type="apiKey",
 *     in="header",
 *     name="X-CSRF-TOKEN",
 *     description="CSRF Token for web routes"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Users",
 *     description="User management operations"
 * )
 * 
 * @OA\Tag(
 *     name="Roles",
 *     description="Role and permission management"
 * )
 * 
 * @OA\Tag(
 *     name="Merchants",
 *     description="Vendor/Merchant management operations"
 * )
 * 
 * @OA\Tag(
 *     name="Bookings",
 *     description="Booking management operations"
 * )
 * 
 * @OA\Tag(
 *     name="Health",
 *     description="System health and status endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="System",
 *     description="System status and metrics"
 * )
 * 
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Success"),
 *     @OA\Property(property="data", type="object"),
 *     @OA\Property(property="timestamp", type="string", format="date-time", example="2025-09-26T06:22:22.112825Z")
 * )
 * 
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="An error occurred"),
 *     @OA\Property(
 *         property="error",
 *         type="object",
 *         @OA\Property(property="message", type="string", example="Validation failed"),
 *         @OA\Property(property="details", type="object")
 *     ),
 *     @OA\Property(property="timestamp", type="string", format="date-time", example="2025-09-26T06:22:22.112825Z")
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="roles", type="array", @OA\Items(ref="#/components/schemas/Role"))
 * )
 * 
 * @OA\Schema(
 *     schema="Role",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="admin"),
 *     @OA\Property(property="guard_name", type="string", example="web"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Merchant",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="business_name", type="string", example="Acme Corp"),
 *     @OA\Property(property="contact_email", type="string", format="email", example="contact@acme.com"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="address", type="string", example="123 Main St, City, State"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "pending"}, example="active"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Booking",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="merchant_id", type="integer", example=1),
 *     @OA\Property(property="service_name", type="string", example="Consultation"),
 *     @OA\Property(property="booking_date", type="string", format="date-time"),
 *     @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed"}, example="pending"),
 *     @OA\Property(property="notes", type="string", example="Special requirements"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="HealthCheck",
 *     type="object",
 *     @OA\Property(property="status", type="string", enum={"healthy", "degraded", "unhealthy"}, example="healthy"),
 *     @OA\Property(property="message", type="string", example="All systems operational"),
 *     @OA\Property(property="timestamp", type="string", format="date-time"),
 *     @OA\Property(property="version", type="string", example="1.0.0"),
 *     @OA\Property(property="api_version", type="string", example="v1"),
 *     @OA\Property(property="checks", type="object"),
 *     @OA\Property(property="system_info", type="object")
 * )
 */
class SwaggerController
{
    // This controller serves as a placeholder for global Swagger annotations
    // The actual API endpoints are documented in their respective controllers
}
