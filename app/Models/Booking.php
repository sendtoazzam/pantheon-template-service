<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_booking_id',
        'booking_number',
        'user_id',
        'merchant_id',
        'service_type',
        'service_name',
        'service_description',
        'service_price',
        'quantity',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'final_amount',
        'currency',
        'booking_date',
        'booking_time',
        'booking_datetime',
        'duration_minutes',
        'expected_start_time',
        'expected_end_time',
        'actual_start_time',
        'actual_end_time',
        'status',
        'payment_status',
        'fulfillment_status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'customer_city',
        'customer_state',
        'customer_country',
        'customer_postal_code',
        'customer_latitude',
        'customer_longitude',
        'special_instructions',
        'dietary_restrictions',
        'accessibility_requirements',
        'custom_fields',
        'external_data',
        'payment_data',
        'success_processed_at',
        'fulfillment_type',
        'delivery_address',
        'delivery_contact_name',
        'delivery_contact_phone',
        'delivery_instructions',
        'delivery_fee',
        'delivery_distance',
        'estimated_delivery_time',
        'payment_method',
        'payment_gateway',
        'payment_transaction_id',
        'payment_reference',
        'payment_date',
        'payment_details',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'refund_amount',
        'refund_date',
        'refund_reference',
        'refund_reason',
        'rescheduled_at',
        'rescheduled_by',
        'original_booking_date',
        'original_booking_time',
        'reschedule_reason',
        'reschedule_count',
        'assigned_staff_id',
        'assigned_staff_name',
        'staff_assigned_at',
        'customer_rating',
        'customer_review',
        'reviewed_at',
        'merchant_rating',
        'merchant_notes',
        'customer_notified',
        'merchant_notified',
        'staff_notified',
        'last_notification_sent',
        'notification_history',
        'source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'referral_code',
        'tracking_data',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'external_data' => 'array',
        'payment_data' => 'array',
        'payment_details' => 'array',
        'notification_history' => 'array',
        'tracking_data' => 'array',
        'booking_date' => 'date',
        'booking_datetime' => 'datetime',
        'expected_start_time' => 'datetime',
        'expected_end_time' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'payment_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'refund_date' => 'datetime',
        'rescheduled_at' => 'datetime',
        'original_booking_date' => 'date',
        'staff_assigned_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'last_notification_sent' => 'datetime',
        'success_processed_at' => 'datetime',
        'customer_notified' => 'boolean',
        'merchant_notified' => 'boolean',
        'staff_notified' => 'boolean',
        'service_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'delivery_distance' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'customer_latitude' => 'decimal:8',
        'customer_longitude' => 'decimal:11',
    ];

    /**
     * Get the user that owns the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the merchant for the booking.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the user who cancelled the booking.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the user who rescheduled the booking.
     */
    public function rescheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rescheduled_by');
    }

    /**
     * Get the assigned staff member.
     */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    /**
     * Scope for confirmed bookings
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope for completed bookings
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for cancelled bookings
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope for pending bookings
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for bookings by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for bookings by payment status
     */
    public function scopeByPaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Scope for bookings by fulfillment type
     */
    public function scopeByFulfillmentType($query, $type)
    {
        return $query->where('fulfillment_type', $type);
    }

    /**
     * Scope for bookings on a specific date
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('booking_date', $date);
    }

    /**
     * Scope for bookings between dates
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('booking_date', [$startDate, $endDate]);
    }

    /**
     * Check if booking is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if booking is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if booking is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if booking is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if booking is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if booking is for delivery
     */
    public function isDelivery(): bool
    {
        return $this->fulfillment_type === 'delivery';
    }

    /**
     * Check if booking is for pickup
     */
    public function isPickup(): bool
    {
        return $this->fulfillment_type === 'pickup';
    }

    /**
     * Check if booking is in store
     */
    public function isInStore(): bool
    {
        return $this->fulfillment_type === 'in_store';
    }

    /**
     * Check if booking is virtual
     */
    public function isVirtual(): bool
    {
        return $this->fulfillment_type === 'virtual';
    }

    /**
     * Get booking duration in minutes
     */
    public function getDurationInMinutes(): int
    {
        if ($this->actual_start_time && $this->actual_end_time) {
            return $this->actual_start_time->diffInMinutes($this->actual_end_time);
        }
        
        return $this->duration_minutes ?? 0;
    }

    /**
     * Get booking duration in hours
     */
    public function getDurationInHours(): float
    {
        return round($this->getDurationInMinutes() / 60, 2);
    }

    /**
     * Check if booking is overdue
     */
    public function isOverdue(): bool
    {
        return $this->expected_end_time && $this->expected_end_time->isPast() && !$this->isCompleted();
    }

    /**
     * Get formatted booking time
     */
    public function getFormattedBookingTimeAttribute()
    {
        return $this->booking_time ? date('g:i A', strtotime($this->booking_time)) : null;
    }

    /**
     * Get formatted booking date
     */
    public function getFormattedBookingDateAttribute()
    {
        return $this->booking_date ? $this->booking_date->format('M j, Y') : null;
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAmountAttribute()
    {
        return '$' . number_format($this->total_amount, 2);
    }

    /**
     * Get formatted final amount
     */
    public function getFormattedFinalAmountAttribute()
    {
        return '$' . number_format($this->final_amount, 2);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'confirmed' => 'bg-blue-100 text-blue-800',
            'in_progress' => 'bg-purple-100 text-purple-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'no_show' => 'bg-gray-100 text-gray-800',
            'rescheduled' => 'bg-orange-100 text-orange-800',
            'refunded' => 'bg-pink-100 text-pink-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get payment status badge class
     */
    public function getPaymentStatusBadgeClassAttribute()
    {
        return match($this->payment_status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'paid' => 'bg-green-100 text-green-800',
            'partially_paid' => 'bg-blue-100 text-blue-800',
            'refunded' => 'bg-pink-100 text-pink-800',
            'failed' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}