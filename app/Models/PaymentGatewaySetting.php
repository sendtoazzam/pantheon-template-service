<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGatewaySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway_name',
        'display_name',
        'gateway_type',
        'is_active',
        'is_default',
        'configuration',
        'supported_currencies',
        'supported_countries',
        'supported_payment_methods',
        'transaction_fee_percentage',
        'transaction_fee_fixed',
        'min_amount',
        'max_amount',
        'webhook_configuration',
        'limits',
        'description',
        'priority',
    ];

    protected $casts = [
        'configuration' => 'array',
        'supported_currencies' => 'array',
        'supported_countries' => 'array',
        'supported_payment_methods' => 'array',
        'webhook_configuration' => 'array',
        'limits' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'transaction_fee_percentage' => 'decimal:4',
        'transaction_fee_fixed' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];

    /**
     * Get active gateways
     */
    public static function getActiveGateways()
    {
        return static::where('is_active', true)
                    ->orderBy('priority', 'desc')
                    ->get();
    }

    /**
     * Get default gateway
     */
    public static function getDefaultGateway()
    {
        return static::where('is_active', true)
                    ->where('is_default', true)
                    ->first();
    }

    /**
     * Set as default gateway
     */
    public function setAsDefault()
    {
        // Remove default from other gateways
        static::where('id', '!=', $this->id)
              ->update(['is_default' => false]);
        
        // Set this as default
        $this->update(['is_default' => true]);
    }

    /**
     * Check if gateway supports currency
     */
    public function supportsCurrency($currency)
    {
        return in_array($currency, $this->supported_currencies ?? []);
    }

    /**
     * Check if gateway supports country
     */
    public function supportsCountry($country)
    {
        return in_array($country, $this->supported_countries ?? []);
    }

    /**
     * Check if gateway supports payment method
     */
    public function supportsPaymentMethod($method)
    {
        return in_array($method, $this->supported_payment_methods ?? []);
    }

    /**
     * Calculate transaction fee
     */
    public function calculateFee($amount)
    {
        $percentageFee = ($amount * $this->transaction_fee_percentage) / 100;
        return $percentageFee + $this->transaction_fee_fixed;
    }

    /**
     * Get configuration value
     */
    public function getConfig($key, $default = null)
    {
        return data_get($this->configuration, $key, $default);
    }
}