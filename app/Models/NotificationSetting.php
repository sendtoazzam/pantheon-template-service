<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_type',
        'provider_name',
        'display_name',
        'is_active',
        'is_default',
        'configuration',
        'capabilities',
        'description',
        'priority',
        'limits',
    ];

    protected $casts = [
        'configuration' => 'array',
        'capabilities' => 'array',
        'limits' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get active providers by type
     */
    public static function getActiveProviders($type = null)
    {
        $query = static::where('is_active', true);
        
        if ($type) {
            $query->where('provider_type', $type);
        }
        
        return $query->orderBy('priority', 'desc')->get();
    }

    /**
     * Get default provider by type
     */
    public static function getDefaultProvider($type)
    {
        return static::where('provider_type', $type)
                    ->where('is_active', true)
                    ->where('is_default', true)
                    ->first();
    }

    /**
     * Set as default provider
     */
    public function setAsDefault()
    {
        // Remove default from other providers of same type
        static::where('provider_type', $this->provider_type)
              ->where('id', '!=', $this->id)
              ->update(['is_default' => false]);
        
        // Set this as default
        $this->update(['is_default' => true]);
    }

    /**
     * Check if provider supports a capability
     */
    public function supports($capability)
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    /**
     * Get configuration value
     */
    public function getConfig($key, $default = null)
    {
        return data_get($this->configuration, $key, $default);
    }
}