<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiCallLog extends Model
{
    protected $fillable = [
        'user_id',
        'method',
        'url',
        'endpoint',
        'ip_address',
        'user_agent',
        'request_headers',
        'request_body',
        'request_params',
        'response_status',
        'response_headers',
        'response_body',
        'response_size_bytes',
        'execution_time_ms',
        'memory_usage_bytes',
        'peak_memory_bytes',
        'status',
        'error_message',
        'metadata',
        'called_at',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'request_params' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
        'metadata' => 'array',
        'execution_time_ms' => 'decimal:2',
        'memory_usage_bytes' => 'integer',
        'peak_memory_bytes' => 'integer',
        'response_size_bytes' => 'integer',
        'called_at' => 'datetime',
    ];

    /**
     * Get the user that made the API call.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for successful API calls
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed API calls
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope for warning API calls
     */
    public function scopeWarning($query)
    {
        return $query->where('status', 'warning');
    }

    /**
     * Scope for specific HTTP method
     */
    public function scopeMethod($query, $method)
    {
        return $query->where('method', strtoupper($method));
    }

    /**
     * Scope for specific endpoint
     */
    public function scopeEndpoint($query, $endpoint)
    {
        return $query->where('endpoint', 'like', "%{$endpoint}%");
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for anonymous calls
     */
    public function scopeAnonymous($query)
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope for recent calls
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('called_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for calls within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('called_at', [$startDate, $endDate]);
    }

    /**
     * Scope for calls with specific status code
     */
    public function scopeStatusCode($query, $statusCode)
    {
        return $query->where('response_status', $statusCode);
    }

    /**
     * Scope for calls from specific IP
     */
    public function scopeFromIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Get response size in human readable format
     */
    public function getResponseSizeHumanAttribute()
    {
        $bytes = $this->response_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get memory usage in human readable format
     */
    public function getMemoryUsageHumanAttribute()
    {
        $bytes = $this->memory_usage_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
