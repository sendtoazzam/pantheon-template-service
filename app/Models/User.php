<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'avatar',
        'status',
        'password',
        'last_login_at',
        'is_admin',
        'is_vendor',
        'is_active',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'login_attempts',
        'locked_until',
        'last_login_ip',
        'last_login_user_agent',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_vendor' => 'boolean',
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'locked_until' => 'datetime',
            'two_factor_recovery_codes' => 'array',
        ];
    }

    /**
     * Get the guard name for the model.
     */
    public function getGuardName(): string
    {
        return 'web';
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->is_admin || $this->hasRole(['admin', 'superadmin']);
    }

    /**
     * Check if user is a vendor
     */
    public function isVendor(): bool
    {
        return $this->is_vendor || $this->hasRole('vendor');
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->status === 'active';
    }

    /**
     * Check if user is locked
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Check if user has 2FA enabled
     */
    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_secret) && !is_null($this->two_factor_confirmed_at);
    }

    /**
     * Get the appropriate guard for this user
     */
    public function getGuardForUser(): string
    {
        if ($this->hasRole('superadmin')) {
            return 'superadmin';
        } elseif ($this->hasRole('admin')) {
            return 'admin';
        } elseif ($this->isVendor()) {
            return 'vendor';
        }
        return 'web';
    }

    /**
     * Get the appropriate API guard for this user
     */
    public function getApiGuardForUser(): string
    {
        if ($this->hasRole('superadmin')) {
            return 'api_superadmin';
        } elseif ($this->hasRole('admin')) {
            return 'api_admin';
        } elseif ($this->isVendor()) {
            return 'api_vendor';
        }
        return 'api';
    }

    /**
     * Get the primary guard for this user (most restrictive)
     */
    public function getPrimaryGuard(): string
    {
        if ($this->hasRole('superadmin')) {
            return 'api_superadmin';
        } elseif ($this->hasRole('admin')) {
            return 'api_admin';
        } elseif ($this->isVendor()) {
            return 'api_vendor';
        }
        return 'api';
    }

    /**
     * Get all available guards for this user
     */
    public function getAvailableGuards(): array
    {
        $guards = ['web', 'api'];
        
        if ($this->hasRole('superadmin')) {
            $guards = array_merge($guards, ['superadmin', 'api_superadmin', 'admin', 'api_admin', 'vendor', 'api_vendor']);
        } elseif ($this->hasRole('admin')) {
            $guards = array_merge($guards, ['admin', 'api_admin']);
        } elseif ($this->isVendor()) {
            $guards = array_merge($guards, ['vendor', 'api_vendor']);
        }
        
        return array_unique($guards);
    }

    /**
     * Scope for admin users
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * Scope for vendor users
     */
    public function scopeVendors($query)
    {
        return $query->where('is_vendor', true);
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * Scope for locked users
     */
    public function scopeLocked($query)
    {
        return $query->whereNotNull('locked_until')->where('locked_until', '>', now());
    }
}
