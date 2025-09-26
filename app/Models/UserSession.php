<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'device_name',
        'browser',
        'browser_version',
        'operating_system',
        'os_version',
        'country',
        'country_code',
        'region',
        'city',
        'timezone',
        'latitude',
        'longitude',
        'status',
        'is_secure',
        'is_mobile',
        'is_trusted_device',
        'is_remember_me',
        'login_method',
        'two_factor_verified',
        'two_factor_verified_at',
        'verification_method',
        'security_events',
        'login_at',
        'last_activity_at',
        'logout_at',
        'session_duration_minutes',
        'idle_time_minutes',
        'max_idle_time_minutes',
        'page_views',
        'api_calls',
        'pages_visited',
        'actions_performed',
        'last_page_visited',
        'last_action_performed',
        'session_data',
        'preferences',
        'metadata',
        'termination_reason',
        'termination_notes',
        'terminated_by',
        'refresh_token',
        'refresh_token_expires_at',
        'refresh_token_used',
        'refresh_token_used_at',
        'api_token',
        'api_token_expires_at',
        'api_permissions',
        'api_rate_limit',
        'api_calls_made',
    ];

    protected $casts = [
        'security_events' => 'array',
        'pages_visited' => 'array',
        'actions_performed' => 'array',
        'session_data' => 'array',
        'preferences' => 'array',
        'metadata' => 'array',
        'api_permissions' => 'array',
        'is_secure' => 'boolean',
        'is_mobile' => 'boolean',
        'is_trusted_device' => 'boolean',
        'is_remember_me' => 'boolean',
        'two_factor_verified' => 'boolean',
        'refresh_token_used' => 'boolean',
        'login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'logout_at' => 'datetime',
        'two_factor_verified_at' => 'datetime',
        'refresh_token_expires_at' => 'datetime',
        'refresh_token_used_at' => 'datetime',
        'api_token_expires_at' => 'datetime',
        'session_duration_minutes' => 'integer',
        'idle_time_minutes' => 'integer',
        'max_idle_time_minutes' => 'integer',
        'page_views' => 'integer',
        'api_calls' => 'integer',
        'api_rate_limit' => 'integer',
        'api_calls_made' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:11',
    ];

    /**
     * Get the user that owns the session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who terminated the session.
     */
    public function terminatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'terminated_by');
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if session is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Check if session is terminated
     */
    public function isTerminated(): bool
    {
        return $this->status === 'terminated';
    }

    /**
     * Check if session is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if session is secure
     */
    public function isSecure(): bool
    {
        return $this->is_secure;
    }

    /**
     * Check if session is from mobile device
     */
    public function isMobile(): bool
    {
        return $this->is_mobile;
    }

    /**
     * Check if device is trusted
     */
    public function isTrustedDevice(): bool
    {
        return $this->is_trusted_device;
    }

    /**
     * Check if session uses remember me
     */
    public function isRememberMe(): bool
    {
        return $this->is_remember_me;
    }

    /**
     * Check if 2FA is verified
     */
    public function isTwoFactorVerified(): bool
    {
        return $this->two_factor_verified;
    }

    /**
     * Check if refresh token is expired
     */
    public function isRefreshTokenExpired(): bool
    {
        return $this->refresh_token_expires_at && $this->refresh_token_expires_at->isPast();
    }

    /**
     * Check if API token is expired
     */
    public function isApiTokenExpired(): bool
    {
        return $this->api_token_expires_at && $this->api_token_expires_at->isPast();
    }

    /**
     * Check if session is idle
     */
    public function isIdle(): bool
    {
        return $this->idle_time_minutes > $this->max_idle_time_minutes;
    }

    /**
     * Get session duration in minutes
     */
    public function getDurationInMinutes(): int
    {
        if ($this->logout_at && $this->login_at) {
            return $this->login_at->diffInMinutes($this->logout_at);
        }
        
        return $this->session_duration_minutes ?? 0;
    }

    /**
     * Get session duration in hours
     */
    public function getDurationInHours(): float
    {
        return round($this->getDurationInMinutes() / 60, 2);
    }

    /**
     * Get idle time in minutes
     */
    public function getIdleTimeInMinutes(): int
    {
        if ($this->last_activity_at) {
            return $this->last_activity_at->diffInMinutes(now());
        }
        
        return $this->idle_time_minutes ?? 0;
    }

    /**
     * Get device information
     */
    public function getDeviceInfo(): array
    {
        return [
            'type' => $this->device_type,
            'name' => $this->device_name,
            'browser' => $this->browser,
            'browser_version' => $this->browser_version,
            'os' => $this->operating_system,
            'os_version' => $this->os_version,
            'is_mobile' => $this->is_mobile,
        ];
    }

    /**
     * Get location information
     */
    public function getLocationInfo(): array
    {
        return [
            'country' => $this->country,
            'country_code' => $this->country_code,
            'region' => $this->region,
            'city' => $this->city,
            'timezone' => $this->timezone,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /**
     * Get session status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'active' => 'bg-green-100 text-green-800',
            'expired' => 'bg-yellow-100 text-yellow-800',
            'terminated' => 'bg-red-100 text-red-800',
            'suspended' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get device type icon
     */
    public function getDeviceTypeIconAttribute()
    {
        return match($this->device_type) {
            'desktop' => '🖥️',
            'mobile' => '📱',
            'tablet' => '📱',
            default => '💻',
        };
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for expired sessions
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope for terminated sessions
     */
    public function scopeTerminated($query)
    {
        return $query->where('status', 'terminated');
    }

    /**
     * Scope for mobile sessions
     */
    public function scopeMobile($query)
    {
        return $query->where('is_mobile', true);
    }

    /**
     * Scope for desktop sessions
     */
    public function scopeDesktop($query)
    {
        return $query->where('device_type', 'desktop');
    }

    /**
     * Scope for trusted devices
     */
    public function scopeTrusted($query)
    {
        return $query->where('is_trusted_device', true);
    }

    /**
     * Scope for sessions by country
     */
    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope for sessions by device type
     */
    public function scopeByDeviceType($query, $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope for sessions by browser
     */
    public function scopeByBrowser($query, $browser)
    {
        return $query->where('browser', $browser);
    }

    /**
     * Scope for sessions by operating system
     */
    public function scopeByOperatingSystem($query, $os)
    {
        return $query->where('operating_system', $os);
    }

    /**
     * Scope for recent sessions
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('login_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for idle sessions
     */
    public function scopeIdle($query)
    {
        return $query->whereRaw('idle_time_minutes > max_idle_time_minutes');
    }
}