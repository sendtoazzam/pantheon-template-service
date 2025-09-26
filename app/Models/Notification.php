<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'category',
        'title',
        'message',
        'data',
        'read_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'retry_count',
        'max_retries',
        'priority',
        'channels',
        'status',
        'template_id',
        'template_data',
        'sender_id',
        'sender_type',
        'recipient_id',
        'recipient_type',
        'parent_id',
        'thread_id',
        'expires_at',
        'scheduled_at',
        'metadata',
        'tags',
        'locale',
        'timezone',
        'is_urgent',
        'is_silent',
        'is_persistent',
        'is_actionable',
        'action_url',
        'action_text',
        'action_data',
        'dismissible',
        'auto_dismiss_after',
        'group_key',
        'batch_id',
        'delivery_attempts',
        'delivery_status',
        'delivery_error',
        'delivery_metadata',
        'interaction_tracking',
        'click_count',
        'view_count',
        'dismiss_count',
        'action_count',
        'last_interacted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'data' => 'array',
        'template_data' => 'array',
        'channels' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'action_data' => 'array',
        'delivery_metadata' => 'array',
        'interaction_tracking' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'expires_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'last_interacted_at' => 'datetime',
        'is_urgent' => 'boolean',
        'is_silent' => 'boolean',
        'is_persistent' => 'boolean',
        'is_actionable' => 'boolean',
        'dismissible' => 'boolean',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'delivery_attempts' => 'integer',
        'click_count' => 'integer',
        'view_count' => 'integer',
        'dismiss_count' => 'integer',
        'action_count' => 'integer',
        'auto_dismiss_after' => 'integer',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sender of the notification.
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the recipient of the notification.
     */
    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the parent notification.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Notification::class, 'parent_id');
    }

    /**
     * Get the child notifications.
     */
    public function children()
    {
        return $this->hasMany(Notification::class, 'parent_id');
    }

    /**
     * Get notifications in the same thread.
     */
    public function thread()
    {
        return $this->hasMany(Notification::class, 'thread_id');
    }

    /**
     * Check if notification is read
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if notification is unread
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Check if notification is sent
     */
    public function isSent(): bool
    {
        return !is_null($this->sent_at);
    }

    /**
     * Check if notification is delivered
     */
    public function isDelivered(): bool
    {
        return !is_null($this->delivered_at);
    }

    /**
     * Check if notification failed
     */
    public function isFailed(): bool
    {
        return !is_null($this->failed_at);
    }

    /**
     * Check if notification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if notification is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    /**
     * Check if notification is urgent
     */
    public function isUrgent(): bool
    {
        return $this->is_urgent;
    }

    /**
     * Check if notification is silent
     */
    public function isSilent(): bool
    {
        return $this->is_silent;
    }

    /**
     * Check if notification is persistent
     */
    public function isPersistent(): bool
    {
        return $this->is_persistent;
    }

    /**
     * Check if notification is actionable
     */
    public function isActionable(): bool
    {
        return $this->is_actionable;
    }

    /**
     * Check if notification is dismissible
     */
    public function isDismissible(): bool
    {
        return $this->dismissible;
    }

    /**
     * Check if notification can be retried
     */
    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries;
    }

    /**
     * Get priority badge class
     */
    public function getPriorityBadgeClassAttribute()
    {
        return match($this->priority) {
            'low' => 'bg-gray-100 text-gray-800',
            'normal' => 'bg-blue-100 text-blue-800',
            'high' => 'bg-orange-100 text-orange-800',
            'urgent' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'sent' => 'bg-blue-100 text-blue-800',
            'delivered' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get delivery status badge class
     */
    public function getDeliveryStatusBadgeClassAttribute()
    {
        return match($this->delivery_status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'sent' => 'bg-blue-100 text-blue-800',
            'delivered' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'bounced' => 'bg-orange-100 text-orange-800',
            'spam' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get type icon
     */
    public function getTypeIconAttribute()
    {
        return match($this->type) {
            'info' => 'ℹ️',
            'success' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
            'security' => '🔒',
            'system' => '⚙️',
            'user' => '👤',
            'booking' => '📅',
            'payment' => '💳',
            'email' => '📧',
            'sms' => '📱',
            'push' => '🔔',
            default => '📢',
        };
    }

    /**
     * Mark as read
     */
    public function markAsRead(): bool
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
            return true;
        }
        return false;
    }

    /**
     * Mark as unread
     */
    public function markAsUnread(): bool
    {
        if ($this->isRead()) {
            $this->update(['read_at' => null]);
            return true;
        }
        return false;
    }

    /**
     * Increment interaction count
     */
    public function incrementInteraction(string $type): void
    {
        $field = $type . '_count';
        if (in_array($field, ['click_count', 'view_count', 'dismiss_count', 'action_count'])) {
            $this->increment($field);
            $this->update(['last_interacted_at' => now()]);
        }
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope for urgent notifications
     */
    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }

    /**
     * Scope for notifications by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for notifications by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for notifications by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for notifications by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for recent notifications
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for expired notifications
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope for scheduled notifications
     */
    public function scopeScheduled($query)
    {
        return $query->where('scheduled_at', '>', now());
    }

    /**
     * Scope for actionable notifications
     */
    public function scopeActionable($query)
    {
        return $query->where('is_actionable', true);
    }

    /**
     * Scope for notifications by channel
     */
    public function scopeByChannel($query, $channel)
    {
        return $query->whereJsonContains('channels', $channel);
    }

    /**
     * Scope for notifications by tag
     */
    public function scopeByTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Scope for notifications by group
     */
    public function scopeByGroup($query, $groupKey)
    {
        return $query->where('group_key', $groupKey);
    }

    /**
     * Scope for notifications by batch
     */
    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }
}