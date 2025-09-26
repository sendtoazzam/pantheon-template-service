<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'activity_type',
        'activity_category',
        'activity_name',
        'description',
        'resource_type',
        'resource_id',
        'resource_name',
        'action',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'operating_system',
        'country',
        'city',
        'latitude',
        'longitude',
        'status',
        'priority',
        'severity',
        'old_values',
        'new_values',
        'metadata',
        'tags',
        'duration_seconds',
        'memory_usage',
        'cpu_usage',
        'network_usage',
        'file_size',
        'file_type',
        'file_path',
        'url',
        'referrer',
        'search_query',
        'search_results_count',
        'error_code',
        'error_message',
        'stack_trace',
        'performance_metrics',
        'security_events',
        'compliance_flags',
        'audit_trail',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'performance_metrics' => 'array',
        'security_events' => 'array',
        'compliance_flags' => 'array',
        'audit_trail' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:11',
        'duration_seconds' => 'decimal:3',
        'memory_usage' => 'integer',
        'cpu_usage' => 'decimal:2',
        'network_usage' => 'integer',
        'file_size' => 'integer',
        'search_results_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the session associated with the activity.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(UserSession::class, 'session_id');
    }

    /**
     * Get the resource model
     */
    public function getResourceModel()
    {
        if ($this->resource_type && $this->resource_id) {
            $modelClass = "App\\Models\\{$this->resource_type}";
            if (class_exists($modelClass)) {
                return $modelClass::find($this->resource_id);
            }
        }
        return null;
    }

    /**
     * Check if activity is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if activity failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if activity has warning
     */
    public function hasWarning(): bool
    {
        return $this->status === 'warning';
    }

    /**
     * Get priority badge class
     */
    public function getPriorityBadgeClassAttribute()
    {
        return match($this->priority) {
            'low' => 'bg-gray-100 text-gray-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'high' => 'bg-orange-100 text-orange-800',
            'critical' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get severity badge class
     */
    public function getSeverityBadgeClassAttribute()
    {
        return match($this->severity) {
            'info' => 'bg-blue-100 text-blue-800',
            'warning' => 'bg-yellow-100 text-yellow-800',
            'error' => 'bg-red-100 text-red-800',
            'critical' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'success' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'warning' => 'bg-yellow-100 text-yellow-800',
            'pending' => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Scope for successful activities
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed activities
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for activities by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope for activities by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('activity_category', $category);
    }

    /**
     * Scope for activities by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for activities by severity
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for recent activities
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for activities by resource
     */
    public function scopeByResource($query, $type, $id = null)
    {
        $query = $query->where('resource_type', $type);
        if ($id) {
            $query->where('resource_id', $id);
        }
        return $query;
    }

    /**
     * Scope for activities with errors
     */
    public function scopeWithErrors($query)
    {
        return $query->whereNotNull('error_code')->orWhereNotNull('error_message');
    }

    /**
     * Scope for security-related activities
     */
    public function scopeSecurity($query)
    {
        return $query->where('activity_category', 'security');
    }

    /**
     * Scope for performance-related activities
     */
    public function scopePerformance($query)
    {
        return $query->where('activity_category', 'performance');
    }

    /**
     * Scope for user management activities
     */
    public function scopeUserManagement($query)
    {
        return $query->where('activity_category', 'user_management');
    }

    /**
     * Scope for system activities
     */
    public function scopeSystem($query)
    {
        return $query->where('activity_category', 'system');
    }
}