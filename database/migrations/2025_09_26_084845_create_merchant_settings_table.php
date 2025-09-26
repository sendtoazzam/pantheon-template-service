<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('merchant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            
            // API Configuration
            $table->string('api_key')->nullable()->unique();
            $table->string('api_secret')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->json('api_permissions')->nullable(); // Specific API permissions
            $table->integer('api_rate_limit')->default(1000); // requests per hour
            $table->boolean('api_enabled')->default(true);
            $table->timestamp('api_key_created_at')->nullable();
            $table->timestamp('api_key_expires_at')->nullable();
            $table->timestamp('last_api_call_at')->nullable();
            $table->integer('api_calls_count')->default(0);
            
            // Payment Gateway Settings
            $table->string('payment_gateway')->nullable(); // stripe, paypal, square, etc.
            $table->string('payment_gateway_key')->nullable();
            $table->string('payment_gateway_secret')->nullable();
            $table->string('payment_gateway_webhook_secret')->nullable();
            $table->json('payment_methods')->nullable(); // Enabled payment methods
            $table->boolean('payment_enabled')->default(false);
            $table->decimal('payment_processing_fee', 5, 2)->nullable(); // percentage
            $table->decimal('minimum_payment_amount', 8, 2)->nullable();
            $table->decimal('maximum_payment_amount', 10, 2)->nullable();
            
            // Notification Settings
            $table->string('notification_email')->nullable();
            $table->string('notification_phone')->nullable();
            $table->boolean('email_notifications_enabled')->default(true);
            $table->boolean('sms_notifications_enabled')->default(false);
            $table->boolean('push_notifications_enabled')->default(true);
            $table->json('notification_preferences')->nullable(); // Detailed notification settings
            
            // Business Hours and Availability
            $table->json('business_hours')->nullable(); // Store operating hours for each day
            $table->json('holiday_schedule')->nullable(); // Holiday and special hours
            $table->boolean('is_24_hours')->default(false);
            $table->string('timezone')->default('UTC');
            $table->boolean('auto_accept_orders')->default(false);
            $table->integer('order_preparation_time')->nullable(); // in minutes
            $table->integer('max_orders_per_hour')->nullable();
            
            // Delivery and Pickup Settings
            $table->boolean('delivery_enabled')->default(false);
            $table->boolean('pickup_enabled')->default(true);
            $table->decimal('delivery_fee', 8, 2)->nullable();
            $table->decimal('free_delivery_threshold', 8, 2)->nullable();
            $table->decimal('delivery_radius', 8, 2)->nullable(); // in kilometers
            $table->integer('delivery_time_min')->nullable(); // minimum delivery time in minutes
            $table->integer('delivery_time_max')->nullable(); // maximum delivery time in minutes
            $table->json('delivery_zones')->nullable(); // Specific delivery zones
            $table->json('pickup_locations')->nullable(); // Available pickup locations
            
            // Inventory and Product Settings
            $table->boolean('inventory_tracking_enabled')->default(false);
            $table->boolean('low_stock_alerts')->default(false);
            $table->integer('low_stock_threshold')->nullable();
            $table->boolean('auto_out_of_stock')->default(false);
            $table->boolean('allow_backorders')->default(false);
            $table->json('product_categories')->nullable(); // Available product categories
            
            // Order Management Settings
            $table->integer('max_order_items')->nullable();
            $table->decimal('minimum_order_amount', 8, 2)->nullable();
            $table->decimal('maximum_order_amount', 10, 2)->nullable();
            $table->boolean('require_customer_info')->default(true);
            $table->boolean('allow_guest_checkout')->default(true);
            $table->integer('order_hold_time')->nullable(); // How long to hold orders in minutes
            $table->boolean('auto_cancel_unpaid_orders')->default(false);
            $table->integer('auto_cancel_time')->nullable(); // Auto cancel after X minutes
            
            // Marketing and Promotions
            $table->boolean('promotions_enabled')->default(false);
            $table->json('promotion_settings')->nullable(); // Promotion configuration
            $table->boolean('loyalty_program_enabled')->default(false);
            $table->json('loyalty_settings')->nullable(); // Loyalty program configuration
            $table->boolean('email_marketing_enabled')->default(false);
            $table->string('email_marketing_provider')->nullable();
            $table->json('email_marketing_settings')->nullable();
            
            // Analytics and Reporting
            $table->boolean('analytics_enabled')->default(false);
            $table->string('google_analytics_id')->nullable();
            $table->string('facebook_pixel_id')->nullable();
            $table->json('custom_tracking_codes')->nullable();
            $table->boolean('sales_reporting_enabled')->default(true);
            $table->boolean('customer_analytics_enabled')->default(false);
            
            // Security Settings
            $table->boolean('two_factor_auth_enabled')->default(false);
            $table->boolean('ip_whitelist_enabled')->default(false);
            $table->json('allowed_ip_addresses')->nullable();
            $table->boolean('session_timeout_enabled')->default(true);
            $table->integer('session_timeout_minutes')->default(30);
            $table->boolean('password_policy_enabled')->default(true);
            $table->json('password_policy_settings')->nullable();
            
            // Integration Settings
            $table->json('third_party_integrations')->nullable(); // External service integrations
            $table->string('pos_system')->nullable(); // Point of sale system
            $table->json('pos_settings')->nullable();
            $table->string('accounting_system')->nullable();
            $table->json('accounting_settings')->nullable();
            $table->string('crm_system')->nullable();
            $table->json('crm_settings')->nullable();
            
            // Custom Settings
            $table->json('custom_fields')->nullable(); // Custom merchant-specific fields
            $table->json('theme_settings')->nullable(); // UI/UX customization
            $table->json('feature_flags')->nullable(); // Feature toggles
            $table->json('experimental_features')->nullable(); // Beta features
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['merchant_id']);
            $table->index(['api_key']);
            $table->index(['payment_enabled', 'delivery_enabled'], 'payment_delivery_idx');
            $table->index(['analytics_enabled', 'sales_reporting_enabled'], 'analytics_sales_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_settings');
    }
};