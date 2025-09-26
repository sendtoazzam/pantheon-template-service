<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\GuardService;
use App\Services\LoginHistoryService;
use App\Services\SecureTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

/**
 * @OA\Tag(
 *     name="Enhanced Authentication",
 *     description="Enhanced API Endpoints for Multi-Guard Authentication"
 * )
 */
class EnhancedAuthController extends BaseApiController
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth/login/{guard}",
     *     summary="Login with specific guard",
     *     tags={"Enhanced Authentication"},
     *     @OA\Parameter(
     *         name="guard",
     *         in="path",
     *         description="Authentication guard (web, api, superadmin, api_superadmin, admin, api_admin, vendor, api_vendor)",
     *         required=true,
     *         @OA\Schema(type="string", enum={"web", "api", "superadmin", "api_superadmin", "admin", "api_admin", "vendor", "api_vendor"})
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login","password"},
     *             @OA\Property(property="login", type="string", description="Email or username", example="superadmin@pantheon.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),
     *             @OA\Property(property="remember_me", type="boolean", description="Remember login", example=false),
     *             @OA\Property(property="two_factor_code", type="string", description="2FA code if required", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User logged in successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"),
     *                 @OA\Property(property="guard", type="string", example="api_admin"),
     *                 @OA\Property(property="available_guards", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="security_info", type="object",
     *                     @OA\Property(property="requires_2fa", type="boolean", example=false),
     *                     @OA\Property(property="session_lifetime", type="integer", example=60),
     *                     @OA\Property(property="rate_limit", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenResponse")
     *     ),
     *     @OA\Response(
     *         response=423,
     *         description="Account locked",
     *         @OA\JsonContent(ref="#/components/schemas/LockedResponse")
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Rate limit exceeded",
     *         @OA\JsonContent(ref="#/components/schemas/RateLimitResponse")
     *     )
     * )
     */
    public function loginWithGuard(Request $request, string $guard)
    {
        try {
            // Validate guard
            $availableGuards = array_keys(config('guards.guards', []));
            if (!in_array($guard, $availableGuards)) {
                return $this->error('Invalid guard specified', [
                    'guard' => $guard,
                    'available_guards' => $availableGuards,
                ], 400);
            }

            // Rate limiting
            $rateLimitKey = "login_attempts:{$guard}:" . $request->ip();
            if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
                $seconds = RateLimiter::availableIn($rateLimitKey);
                return $this->error('Too many login attempts. Please try again later.', [
                    'retry_after' => $seconds,
                ], 429);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'login' => 'required|string',
                'password' => 'required|string',
                'remember_me' => 'boolean',
                'two_factor_code' => 'nullable|string|size:6',
            ]);

            if ($validator->fails()) {
                RateLimiter::hit($rateLimitKey, 300); // 5 minutes
                return $this->error('Validation failed', $validator->errors(), 422);
            }

            $login = $request->input('login');
            $password = $request->input('password');

            // Determine if login is email or username
            $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            // Find user
            $user = User::where($field, $login)->first();

            if (!$user) {
                RateLimiter::hit($rateLimitKey, 300);
                return $this->error('Invalid credentials', null, 401);
            }

            // Check if user can use this guard
            if (!GuardService::canAuthenticateWithGuard($user, $guard)) {
                RateLimiter::hit($rateLimitKey, 300);
                return $this->error('Access denied. You do not have permission to use this guard.', [
                    'guard' => $guard,
                    'available_guards' => GuardService::getAvailableGuardsForUser($user),
                ], 403);
            }

            // Check if user is active
            if (!$user->isActive()) {
                RateLimiter::hit($rateLimitKey, 300);
                return $this->error('Account is inactive', null, 403);
            }

            // Check if user is locked
            if ($user->isLocked()) {
                return $this->error('Account is temporarily locked due to multiple failed login attempts.', [
                    'locked_until' => $user->locked_until,
                ], 423);
            }

            // Attempt authentication using standard web guard first
            $authenticated = false;
            
            // Use the standard web guard for authentication since all users are in the same table
            if (Auth::guard('web')->attempt([$field => $login, 'password' => $password])) {
                $authenticated = true;
                $user = Auth::guard('web')->user();
                
                // Verify the user meets the guard requirements
                if (!$this->userMeetsGuardRequirements($user, $guard)) {
                    Auth::guard('web')->logout();
                    $authenticated = false;
                }
            }

            if (!$authenticated) {
                // Only record failed login if user exists
                if ($user) {
                    GuardService::recordFailedLogin($user, $guard);
                }
                RateLimiter::hit($rateLimitKey, 300);
                return $this->error('Invalid credentials', null, 401);
            }

            // Reset login attempts on successful login
            GuardService::resetLoginAttempts($user);

            // Clear rate limiting
            RateLimiter::clear($rateLimitKey);
            
            // Update last login timestamp
            $user->update(['last_login_at' => now()]);
            
            // Load user's roles and permissions
            $user->load('roles', 'permissions');

            // Track successful login
            LoginHistoryService::trackLogin($user, $request, true);

            // Generate token for API guards
            $token = null;
            if (str_starts_with($guard, 'api_')) {
                $token = SecureTokenService::generateSecureToken(64);
                $user->tokens()->create([
                    'name' => "{$guard}-token",
                    'token' => hash('sha256', $token),
                    'abilities' => ['*'],
                ]);
            }

            // Get security information
            $securityInfo = GuardService::getGuardSecurity($guard);

            return $this->success([
                'user' => new UserResource($user),
                'token' => $token,
                'guard' => $guard,
                'available_guards' => GuardService::getAvailableGuardsForUser($user),
                'security_info' => [
                    'requires_2fa' => GuardService::requiresTwoFactor($guard),
                    'session_lifetime' => $securityInfo['session_lifetime'] ?? 120,
                    'rate_limit' => $securityInfo['rate_limit'] ?? null,
                    'max_tokens' => $securityInfo['max_tokens_per_user'] ?? null,
                ],
            ], 'Login successful');

        } catch (\Exception $e) {
            RateLimiter::hit($rateLimitKey, 300);
            return $this->error('Login failed', $e->getMessage(), 500);
        }
    }

    /**
     * Check if user meets the requirements for the specified guard
     */
    private function userMeetsGuardRequirements(User $user, string $guard): bool
    {
        switch ($guard) {
            case 'superadmin':
            case 'api_superadmin':
                return $user->hasRole('superadmin') && $user->is_admin && $user->is_active;
                
            case 'admin':
            case 'api_admin':
                return $user->hasRole(['admin', 'superadmin']) && $user->is_admin && $user->is_active;
                
            case 'vendor':
            case 'api_vendor':
                return $user->hasRole('vendor') && $user->is_vendor && $user->is_active;
                
            case 'web':
            case 'api':
            default:
                return $user->is_active;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/guards",
     *     summary="Get available guards for user",
     *     tags={"Enhanced Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Available guards retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Available guards retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_guard", type="string", example="api"),
     *                 @OA\Property(property="available_guards", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="guard_info", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function getAvailableGuards(Request $request)
    {
        try {
            $user = Auth::user();
            $currentGuard = Auth::getDefaultDriver();
            $availableGuards = GuardService::getAvailableGuardsForUser($user);
            
            $guardInfo = [];
            foreach ($availableGuards as $guard) {
                $guardInfo[$guard] = GuardService::getGuardSecurity($guard);
            }

            return $this->success([
                'current_guard' => $currentGuard,
                'available_guards' => $availableGuards,
                'guard_info' => $guardInfo,
            ], 'Available guards retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve guards', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/switch-guard",
     *     summary="Switch user guard",
     *     tags={"Enhanced Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"guard"},
     *             @OA\Property(property="guard", type="string", description="Target guard", example="api_admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Guard switched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Guard switched successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="previous_guard", type="string", example="api"),
     *                 @OA\Property(property="current_guard", type="string", example="api_admin"),
     *                 @OA\Property(property="user", type="object", ref="#/components/schemas/User")
     *             )
     *         )
     *     )
     * )
     */
    public function switchGuard(Request $request)
    {
        try {
            $request->validate([
                'guard' => 'required|string',
            ]);

            $user = Auth::user();
            $previousGuard = Auth::getDefaultDriver();
            $newGuard = $request->guard;

            if (!GuardService::switchUserGuard($user, $newGuard)) {
                return $this->error('Cannot switch to the specified guard', [
                    'guard' => $newGuard,
                    'available_guards' => GuardService::getAvailableGuardsForUser($user),
                ], 403);
            }

            return $this->success([
                'previous_guard' => $previousGuard,
                'current_guard' => $newGuard,
                'user' => new UserResource($user),
            ], 'Guard switched successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to switch guard', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/guard-statistics",
     *     summary="Get guard statistics (Admin only)",
     *     tags={"Enhanced Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Guard statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Guard statistics retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function getGuardStatistics(Request $request)
    {
        try {
            // Check if user is admin
            if (!Auth::user()->isAdmin()) {
                return $this->error('Access denied. Admin privileges required.', null, 403);
            }

            $statistics = GuardService::getGuardStatistics();

            return $this->success($statistics, 'Guard statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve guard statistics', $e->getMessage(), 500);
        }
    }
}
