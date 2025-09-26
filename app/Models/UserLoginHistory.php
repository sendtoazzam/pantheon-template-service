<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginHistory extends Model
{
    protected $table = 'user_login_history';
    
    protected $fillable = [
        'user_id',
        'login_method',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'country',
        'city',
        'is_successful',
        'failure_reason',
        'login_at',
        'logout_at',
        'session_duration_minutes',
    ];

    protected $casts = [
        'is_successful' => 'boolean',
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'session_duration_minutes' => 'integer',
    ];

    /**
     * Get the user that owns the login history.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for successful logins
     */
    public function scopeSuccessful($query)
    {
        return $query->where('is_successful', true);
    }

    /**
     * Scope for failed logins
     */
    public function scopeFailed($query)
    {
        return $query->where('is_successful', false);
    }

    /**
     * Scope for recent logins
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('login_at', '>=', now()->subDays($days));
    }

    /**
     * Calculate session duration
     */
    public function calculateSessionDuration()
    {
        if ($this->logout_at && $this->login_at) {
            $this->session_duration_minutes = $this->login_at->diffInMinutes($this->logout_at);
            $this->save();
        }
    }
}
