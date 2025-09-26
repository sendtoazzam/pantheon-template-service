<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTrail extends Model
{
    protected $table = 'audit_trail';
    
    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'description',
        'status',
        'metadata',
        'performed_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'performed_at' => 'datetime',
    ];

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for successful actions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed actions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for specific action
     */
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for specific resource
     */
    public function scopeResource($query, $type, $id = null)
    {
        $query = $query->where('resource_type', $type);
        if ($id) {
            $query->where('resource_id', $id);
        }
        return $query;
    }

    /**
     * Scope for recent actions
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('performed_at', '>=', now()->subDays($days));
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
}
