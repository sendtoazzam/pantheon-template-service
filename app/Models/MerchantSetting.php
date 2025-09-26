<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'api_key',
        'api_secret',
        'webhook_url',
        'webhook_secret',
        'api_permissions',
        'api_rate_limit',
        'api_enabled',
        'api_key_created_at',
        'api_key_expires_at',
        'last_api_call_at',
        'api_calls_count',
        'payment_gateway',
        'payment_gateway_key',
        'payment_gateway_secret',
        'payment_gateway_webhook_secret',
        'payment_methods',
        'payment_enabled',
        'payment_processing_fee',
        'minimum_payment_amount',
        'maximum_payment_amount',
        'notification_email',
        'notification_phone',
        'email_notifications_enabled',
        'sms_notifications_enabled',
        'push_notifications_enabled',
        'notification_preferences',
        'business_hours',
        'holiday_schedule',
        'is_24_hours',
        'timezone',
        'auto_accept_orders',
        'order_preparation_time',
        'max_orders_per_hour',
        'delivery_enabled',
        'pickup_enabled',
        'delivery_fee',
        'free_delivery_threshold',
        'delivery_radius',
        'delivery_time_min',
        'delivery_time_max',
        'delivery_zones',
        'pickup_locations',
        'inventory_tracking_enabled',
        'low_stock_alerts',
        'low_stock_threshold',
        'auto_out_of_stock',
        'allow_backorders',
        'product_categories',
        'max_order_items',
        'minimum_order_amount',
        'maximum_order_amount',
        'require_customer_info',
        'allow_guest_checkout',
        'order_hold_time',
        'auto_cancel_unpaid_orders',
        'auto_cancel_time',
        'promotions_enabled',
        'promotion_settings',
        'loyalty_program_enabled',
        'loyalty_settings',
        'email_marketing_enabled',
        'email_marketing_provider',
        'email_marketing_settings',
        'analytics_enabled',
        'google_analytics_id',
        'facebook_pixel_id',
        'custom_tracking_codes',
        'sales_reporting_enabled',
        'customer_analytics_enabled',
        'two_factor_auth_enabled',
        'ip_whitelist_enabled',
        'allowed_ip_addresses',
        'session_timeout_enabled',
        'session_timeout_minutes',
        'password_policy_enabled',
        'password_policy_settings',
        'third_party_integrations',
        'pos_system',
        'pos_settings',
        'accounting_system',
        'accounting_settings',
        'crm_system',
        'crm_settings',
        'custom_fields',
        'theme_settings',
        'feature_flags',
        'experimental_features',
    ];

    protected $casts = [
        'api_permissions' => 'array',
        'payment_methods' => 'array',
        'notification_preferences' => 'array',
        'business_hours' => 'array',
        'holiday_schedule' => 'array',
        'delivery_zones' => 'array',
        'pickup_locations' => 'array',
        'product_categories' => 'array',
        'promotion_settings' => 'array',
        'loyalty_settings' => 'array',
        'email_marketing_settings' => 'array',
        'custom_tracking_codes' => 'array',
        'allowed_ip_addresses' => 'array',
        'password_policy_settings' => 'array',
        'third_party_integrations' => 'array',
        'pos_settings' => 'array',
        'accounting_settings' => 'array',
        'crm_settings' => 'array',
        'custom_fields' => 'array',
        'theme_settings' => 'array',
        'feature_flags' => 'array',
        'experimental_features' => 'array',
        'api_enabled' => 'boolean',
        'payment_enabled' => 'boolean',
        'email_notifications_enabled' => 'boolean',
        'sms_notifications_enabled' => 'boolean',
        'push_notifications_enabled' => 'boolean',
        'is_24_hours' => 'boolean',
        'auto_accept_orders' => 'boolean',
        'delivery_enabled' => 'boolean',
        'pickup_enabled' => 'boolean',
        'inventory_tracking_enabled' => 'boolean',
        'low_stock_alerts' => 'boolean',
        'auto_out_of_stock' => 'boolean',
        'allow_backorders' => 'boolean',
        'require_customer_info' => 'boolean',
        'allow_guest_checkout' => 'boolean',
        'auto_cancel_unpaid_orders' => 'boolean',
        'promotions_enabled' => 'boolean',
        'loyalty_program_enabled' => 'boolean',
        'email_marketing_enabled' => 'boolean',
        'analytics_enabled' => 'boolean',
        'sales_reporting_enabled' => 'boolean',
        'customer_analytics_enabled' => 'boolean',
        'two_factor_auth_enabled' => 'boolean',
        'ip_whitelist_enabled' => 'boolean',
        'session_timeout_enabled' => 'boolean',
        'password_policy_enabled' => 'boolean',
        'api_key_created_at' => 'datetime',
        'api_key_expires_at' => 'datetime',
        'last_api_call_at' => 'datetime',
    ];

    /**
     * Get the merchant that owns the settings.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Check if API is enabled
     */
    public function isApiEnabled(): bool
    {
        return $this->api_enabled;
    }

    /**
     * Check if payment is enabled
     */
    public function isPaymentEnabled(): bool
    {
        return $this->payment_enabled;
    }

    /**
     * Check if delivery is enabled
     */
    public function isDeliveryEnabled(): bool
    {
        return $this->delivery_enabled;
    }

    /**
     * Check if pickup is enabled
     */
    public function isPickupEnabled(): bool
    {
        return $this->pickup_enabled;
    }

    /**
     * Check if analytics is enabled
     */
    public function isAnalyticsEnabled(): bool
    {
        return $this->analytics_enabled;
    }

    /**
     * Check if API key is expired
     */
    public function isApiKeyExpired(): bool
    {
        return $this->api_key_expires_at && $this->api_key_expires_at->isPast();
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
        if ($this->is_24_hours) {
            return true;
        }

        $day = strtolower($datetime->format('l'));
        $time = $datetime->format('H:i');
        
        $dayHours = $this->getBusinessHoursForDay($day);
        
        if (!$dayHours || !$dayHours['is_open']) {
            return false;
        }
        
        return $time >= $dayHours['open'] && $time <= $dayHours['close'];
    }

    /**
     * Get delivery zones as array
     */
    public function getDeliveryZonesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Get pickup locations as array
     */
    public function getPickupLocationsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Check if a location is within delivery radius
     */
    public function isWithinDeliveryRadius($latitude, $longitude)
    {
        if (!$this->delivery_radius || !$this->merchant->latitude || !$this->merchant->longitude) {
            return false;
        }

        $distance = $this->calculateDistance(
            $this->merchant->latitude,
            $this->merchant->longitude,
            $latitude,
            $longitude
        );

        return $distance <= $this->delivery_radius;
    }

    /**
     * Calculate distance between two coordinates
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }
}