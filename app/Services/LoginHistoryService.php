<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLoginHistory;
use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginHistoryService
{
    /**
     * Track a login attempt
     */
    public static function trackLogin(User $user, Request $request, bool $isSuccessful = true, string $failureReason = null): UserLoginHistory
    {
        $userAgent = $request->userAgent();
        $deviceInfo = self::parseUserAgent($userAgent);
        
        $loginHistory = UserLoginHistory::create([
            'user_id' => $user->id,
            'login_method' => self::determineLoginMethod($request),
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'os' => $deviceInfo['os'],
            'country' => self::getCountryFromIP($request->ip()),
            'city' => self::getCityFromIP($request->ip()),
            'is_successful' => $isSuccessful,
            'failure_reason' => $failureReason,
            'login_at' => now(),
        ]);

        // Log to audit trail
        self::logAuditTrail(
            $user,
            $isSuccessful ? 'login_success' : 'login_failed',
            'User',
            $user->id,
            $request,
            null,
            [
                'login_method' => self::determineLoginMethod($request),
                'device_info' => $deviceInfo,
                'failure_reason' => $failureReason,
            ],
            $isSuccessful ? 'success' : 'failed'
        );

        return $loginHistory;
    }

    /**
     * Track a logout
     */
    public static function trackLogout(User $user, Request $request): void
    {
        // Find the most recent login for this user
        $loginHistory = UserLoginHistory::where('user_id', $user->id)
            ->where('is_successful', true)
            ->whereNull('logout_at')
            ->latest('login_at')
            ->first();

        if ($loginHistory) {
            $loginHistory->update([
                'logout_at' => now(),
            ]);
            $loginHistory->calculateSessionDuration();
        }

        // Log to audit trail
        self::logAuditTrail(
            $user,
            'logout',
            'User',
            $user->id,
            $request,
            null,
            [
                'session_duration_minutes' => $loginHistory?->session_duration_minutes,
            ],
            'success'
        );
    }

    /**
     * Track a failed login attempt
     */
    public static function trackFailedLogin(string $login, Request $request, string $reason = 'invalid_credentials'): void
    {
        // Try to find user by login
        $user = User::where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if ($user) {
            self::trackLogin($user, $request, false, $reason);
        } else {
            // Log failed attempt for non-existent user
            self::logAuditTrail(
                null,
                'login_failed',
                'User',
                null,
                $request,
                null,
                [
                    'login_attempt' => $login,
                    'failure_reason' => 'user_not_found',
                ],
                'failed'
            );
        }
    }

    /**
     * Get user login history
     */
    public static function getUserLoginHistory(User $user, int $days = 30, int $limit = 50)
    {
        return UserLoginHistory::where('user_id', $user->id)
            ->recent($days)
            ->with('user')
            ->orderBy('login_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get login statistics
     */
    public static function getLoginStatistics(User $user, int $days = 30): array
    {
        $recentLogins = UserLoginHistory::where('user_id', $user->id)
            ->recent($days);

        return [
            'total_logins' => $recentLogins->count(),
            'successful_logins' => $recentLogins->successful()->count(),
            'failed_logins' => $recentLogins->failed()->count(),
            'unique_ips' => $recentLogins->distinct('ip_address')->count('ip_address'),
            'unique_devices' => $recentLogins->distinct('device_type')->count('device_type'),
            'average_session_duration' => $recentLogins->successful()
                ->whereNotNull('session_duration_minutes')
                ->avg('session_duration_minutes'),
            'last_login' => $recentLogins->successful()->latest('login_at')->first()?->login_at,
        ];
    }

    /**
     * Determine login method from request
     */
    private static function determineLoginMethod(Request $request): string
    {
        $login = $request->input('login');
        return filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    }

    /**
     * Parse user agent to extract device information
     */
    private static function parseUserAgent(string $userAgent): array
    {
        $deviceType = 'desktop';
        $browser = 'Unknown';
        $os = 'Unknown';

        // Simple device detection
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/Tablet|iPad/', $userAgent)) {
            $deviceType = 'tablet';
        }

        // Browser detection
        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Chrome ' . $matches[1];
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Firefox ' . $matches[1];
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Safari ' . $matches[1];
        } elseif (preg_match('/Edge\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Edge ' . $matches[1];
        }

        // OS detection
        if (preg_match('/Windows NT ([0-9.]+)/', $userAgent, $matches)) {
            $os = 'Windows ' . $matches[1];
        } elseif (preg_match('/Mac OS X ([0-9_]+)/', $userAgent, $matches)) {
            $os = 'macOS ' . str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Linux/', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android ([0-9.]+)/', $userAgent, $matches)) {
            $os = 'Android ' . $matches[1];
        } elseif (preg_match('/iPhone OS ([0-9_]+)/', $userAgent, $matches)) {
            $os = 'iOS ' . str_replace('_', '.', $matches[1]);
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
        ];
    }

    /**
     * Get country from IP (simplified - in production, use a proper IP geolocation service)
     */
    private static function getCountryFromIP(string $ip): ?string
    {
        // This is a simplified implementation
        // In production, use a service like MaxMind GeoIP2 or ipapi.co
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Local';
        }
        return 'Unknown';
    }

    /**
     * Get city from IP (simplified - in production, use a proper IP geolocation service)
     */
    private static function getCityFromIP(string $ip): ?string
    {
        // This is a simplified implementation
        // In production, use a service like MaxMind GeoIP2 or ipapi.co
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Local';
        }
        return 'Unknown';
    }

    /**
     * Log to audit trail
     */
    private static function logAuditTrail(
        ?User $user,
        string $action,
        ?string $resourceType,
        ?int $resourceId,
        Request $request,
        ?array $oldValues,
        ?array $newValues,
        string $status = 'success'
    ): void {
        AuditTrail::create([
            'user_id' => $user?->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => self::generateDescription($action, $resourceType, $user),
            'status' => $status,
            'metadata' => [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ],
            'performed_at' => now(),
        ]);
    }

    /**
     * Generate description for audit trail
     */
    private static function generateDescription(string $action, ?string $resourceType, ?User $user): string
    {
        $userName = $user ? $user->name : 'Unknown User';
        
        switch ($action) {
            case 'login_success':
                return "{$userName} successfully logged in";
            case 'login_failed':
                return "{$userName} failed to log in";
            case 'logout':
                return "{$userName} logged out";
            default:
                return "{$userName} performed {$action} on {$resourceType}";
        }
    }
}
