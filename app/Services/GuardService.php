<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

class GuardService
{
    /**
     * Get the appropriate guard for a user
     */
    public static function getGuardForUser(User $user): string
    {
        return $user->getGuardForUser();
    }

    /**
     * Get the appropriate API guard for a user
     */
    public static function getApiGuardForUser(User $user): string
    {
        return $user->getApiGuardForUser();
    }

    /**
     * Check if user can authenticate with specific guard
     */
    public static function canAuthenticateWithGuard(User $user, string $guard): bool
    {
        $guardConfig = config("guards.guards.{$guard}");
        
        if (!$guardConfig) {
            return false;
        }

        // Check if user is active
        if (!$user->isActive()) {
            return false;
        }

        // Check if user is locked
        if ($user->isLocked()) {
            return false;
        }

        // Check guard-specific conditions
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
                return $user->is_active; // All active users can use web/api guards
            
            default:
                return false;
        }
    }

    /**
     * Get available guards for a user
     */
    public static function getAvailableGuardsForUser(User $user): array
    {
        $guards = [];
        $allGuards = array_keys(config('guards.guards', []));

        foreach ($allGuards as $guard) {
            if (self::canAuthenticateWithGuard($user, $guard)) {
                $guards[] = $guard;
            }
        }

        return $guards;
    }

    /**
     * Check rate limiting for guard
     */
    public static function checkRateLimit(string $guard, string $identifier): bool
    {
        $rateLimit = config("guards.security.{$guard}.rate_limit", []);
        
        if (empty($rateLimit)) {
            return true; // No rate limiting configured
        }

        $key = "guard_rate_limit:{$guard}:{$identifier}";
        $maxAttempts = $rateLimit['max_attempts'] ?? 60;
        $decayMinutes = $rateLimit['decay_minutes'] ?? 1;

        return RateLimiter::attempt($key, $maxAttempts, function () {
            return true;
        }, $decayMinutes * 60);
    }

    /**
     * Record failed login attempt
     */
    public static function recordFailedLogin(User $user, string $guard): void
    {
        $user->increment('login_attempts');
        
        $maxAttempts = config("guards.security.{$guard}.max_login_attempts", 5);
        $lockoutDuration = config("guards.security.{$guard}.lockout_duration", 15);

        if ($user->login_attempts >= $maxAttempts) {
            $user->update([
                'locked_until' => now()->addMinutes($lockoutDuration)
            ]);
        }
    }

    /**
     * Reset login attempts
     */
    public static function resetLoginAttempts(User $user): void
    {
        $user->update([
            'login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Check if 2FA is required for guard
     */
    public static function requiresTwoFactor(string $guard): bool
    {
        return config("guards.security.{$guard}.require_2fa", false);
    }

    /**
     * Get guard security settings
     */
    public static function getGuardSecurity(string $guard): array
    {
        return config("guards.security.{$guard}", []);
    }

    /**
     * Check IP whitelist for admin guard
     */
    public static function checkIpWhitelist(string $guard, string $ip): bool
    {
        $whitelist = config("guards.security.{$guard}.ip_whitelist", []);
        
        if (empty($whitelist)) {
            return true; // No whitelist configured
        }

        return in_array($ip, $whitelist);
    }

    /**
     * Get session lifetime for guard
     */
    public static function getSessionLifetime(string $guard): int
    {
        return config("guards.security.{$guard}.session_lifetime", 120);
    }

    /**
     * Get token lifetime for API guard
     */
    public static function getTokenLifetime(string $guard): int
    {
        return config("guards.security.{$guard}.token_lifetime", 525600);
    }

    /**
     * Check if user has exceeded max tokens
     */
    public static function checkMaxTokens(User $user, string $guard): bool
    {
        $maxTokens = config("guards.security.{$guard}.max_tokens_per_user", 10);
        
        if ($maxTokens === 0) {
            return true; // No limit
        }

        $currentTokens = $user->tokens()->count();
        return $currentTokens < $maxTokens;
    }

    /**
     * Get guard middleware
     */
    public static function getGuardMiddleware(string $guard): array
    {
        return config("guards.middleware.{$guard}", []);
    }

    /**
     * Log guard activity
     */
    public static function logGuardActivity(User $user, string $guard, string $action, array $metadata = []): void
    {
        // This would integrate with your existing audit trail system
        \App\Models\AuditTrail::create([
            'user_id' => $user->id,
            'action' => "guard_{$action}",
            'resource_type' => 'Guard',
            'resource_id' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => "User {$user->name} performed {$action} with {$guard} guard",
            'status' => 'success',
            'metadata' => array_merge($metadata, [
                'guard' => $guard,
                'action' => $action,
            ]),
            'performed_at' => now(),
        ]);
    }

    /**
     * Switch user guard
     */
    public static function switchUserGuard(User $user, string $newGuard): bool
    {
        if (!self::canAuthenticateWithGuard($user, $newGuard)) {
            return false;
        }

        // Log the guard switch
        self::logGuardActivity($user, $newGuard, 'switch', [
            'previous_guard' => Auth::getDefaultDriver(),
        ]);

        return true;
    }

    /**
     * Get guard statistics
     */
    public static function getGuardStatistics(): array
    {
        $guards = array_keys(config('guards.guards', []));
        $stats = [];

        foreach ($guards as $guard) {
            $stats[$guard] = [
                'active_users' => self::getActiveUsersForGuard($guard),
                'locked_users' => self::getLockedUsersForGuard($guard),
                'recent_logins' => self::getRecentLoginsForGuard($guard),
                'security_settings' => self::getGuardSecurity($guard),
            ];
        }

        return $stats;
    }

    /**
     * Get active users for guard
     */
    private static function getActiveUsersForGuard(string $guard): int
    {
        $query = User::active();

        switch ($guard) {
            case 'admin':
            case 'api_admin':
                $query->admins();
                break;
            case 'vendor':
            case 'api_vendor':
                $query->vendors();
                break;
        }

        return $query->count();
    }

    /**
     * Get locked users for guard
     */
    private static function getLockedUsersForGuard(string $guard): int
    {
        $query = User::locked();

        switch ($guard) {
            case 'admin':
            case 'api_admin':
                $query->admins();
                break;
            case 'vendor':
            case 'api_vendor':
                $query->vendors();
                break;
        }

        return $query->count();
    }

    /**
     * Get recent logins for guard
     */
    private static function getRecentLoginsForGuard(string $guard): int
    {
        return \App\Models\UserLoginHistory::where('called_at', '>=', now()->subHours(24))
            ->whereHas('user', function ($query) use ($guard) {
                switch ($guard) {
                    case 'admin':
                    case 'api_admin':
                        $query->admins();
                        break;
                    case 'vendor':
                    case 'api_vendor':
                        $query->vendors();
                        break;
                }
            })
            ->count();
    }
}
