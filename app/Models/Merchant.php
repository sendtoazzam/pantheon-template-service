<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_slug',
        'business_description',
        'business_type',
        'business_category',
        'business_size',
        'contact_email',
        'contact_phone',
        'website',
        'social_media',
        'business_address',
        'business_city',
        'business_state',
        'business_country',
        'business_postal_code',
        'latitude',
        'longitude',
        'registration_number',
        'tax_id',
        'license_number',
        'registration_date',
        'license_expiry_date',
        'status',
        'verification_status',
        'verification_notes',
        'verified_at',
        'verified_by',
        'business_hours',
        'payment_methods',
        'delivery_options',
        'is_delivery_available',
        'is_pickup_available',
        'delivery_radius',
        'delivery_fee',
        'min_order_amount',
        'max_order_amount',
        'total_orders',
        'total_revenue',
        'average_rating',
        'total_reviews',
        'total_customers',
        'subscription_plan',
        'subscription_started_at',
        'subscription_expires_at',
        'is_subscription_active',
        'monthly_fee',
        'enabled_features',
        'api_permissions',
        'can_accept_online_orders',
        'can_manage_inventory',
        'can_use_analytics',
        'can_use_marketing_tools',
        'gdpr_compliant',
        'terms_accepted',
        'terms_accepted_at',
        'privacy_policy_accepted',
        'privacy_policy_accepted_at',
        'data_processing_consent',
        'data_processing_consent_at',
    ];

    protected $casts = [
        'social_media' => 'array',
        'business_hours' => 'array',
        'payment_methods' => 'array',
        'delivery_options' => 'array',
        'enabled_features' => 'array',
        'api_permissions' => 'array',
        'is_delivery_available' => 'boolean',
        'is_pickup_available' => 'boolean',
        'is_subscription_active' => 'boolean',
        'can_accept_online_orders' => 'boolean',
        'can_manage_inventory' => 'boolean',
        'can_use_analytics' => 'boolean',
        'can_use_marketing_tools' => 'boolean',
        'gdpr_compliant' => 'boolean',
        'terms_accepted' => 'boolean',
        'privacy_policy_accepted' => 'boolean',
        'data_processing_consent' => 'boolean',
        'verified_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'privacy_policy_accepted_at' => 'datetime',
        'data_processing_consent_at' => 'datetime',
        'subscription_started_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'registration_date' => 'date',
        'license_expiry_date' => 'date',
    ];

    /**
     * Get the user that owns the merchant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the merchant settings.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(MerchantSetting::class);
    }

    /**
     * Get the bookings for the merchant.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the user who verified this merchant.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope for active merchants
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for verified merchants
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope for merchants by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('business_type', $type);
    }

    /**
     * Scope for merchants by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('business_category', $category);
    }

    /**
     * Scope for merchants with delivery
     */
    public function scopeWithDelivery($query)
    {
        return $query->where('is_delivery_available', true);
    }

    /**
     * Scope for merchants with pickup
     */
    public function scopeWithPickup($query)
    {
        return $query->where('is_pickup_available', true);
    }

    /**
     * Check if merchant is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if merchant is verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Check if merchant has delivery
     */
    public function hasDelivery(): bool
    {
        return $this->is_delivery_available;
    }

    /**
     * Check if merchant has pickup
     */
    public function hasPickup(): bool
    {
        return $this->is_pickup_available;
    }

    /**
     * Get business hours for a specific day
     */
    public function getBusinessHoursForDay($day)
    {
        $businessHours = $this->business_hours;
        return $businessHours[$day] ?? null;
    }

    /**
     * Check if merchant is open at a specific time
     */
    public function isOpenAt($datetime)
    {
        $day = strtolower($datetime->format('l'));
        $time = $datetime->format('H:i');
        
        $dayHours = $this->getBusinessHoursForDay($day);
        
        if (!$dayHours || !$dayHours['is_open']) {
            return false;
        }
        
        return $time >= $dayHours['open'] && $time <= $dayHours['close'];
    }

    /**
     * Get average rating formatted
     */
    public function getFormattedRatingAttribute()
    {
        return number_format($this->average_rating, 1);
    }

    /**
     * Get total revenue formatted
     */
    public function getFormattedRevenueAttribute()
    {
        return number_format($this->total_revenue, 2);
    }
}