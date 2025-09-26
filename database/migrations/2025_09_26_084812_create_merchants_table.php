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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Business Information
            $table->string('business_name');
            $table->string('business_slug')->unique();
            $table->text('business_description')->nullable();
            $table->string('business_type')->nullable(); // restaurant, retail, service, etc.
            $table->string('business_category')->nullable(); // food, fashion, technology, etc.
            $table->string('business_size')->nullable(); // small, medium, large, enterprise
            
            // Contact Information
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('website')->nullable();
            $table->string('social_media')->nullable(); // JSON field for social media links
            
            // Address Information
            $table->text('business_address')->nullable();
            $table->string('business_city')->nullable();
            $table->string('business_state')->nullable();
            $table->string('business_country')->nullable();
            $table->string('business_postal_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Business Registration
            $table->string('registration_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('license_number')->nullable();
            $table->date('registration_date')->nullable();
            $table->date('license_expiry_date')->nullable();
            
            // Business Status
            $table->enum('status', ['pending', 'active', 'inactive', 'suspended', 'rejected'])->default('pending');
            $table->enum('verification_status', ['unverified', 'pending', 'verified', 'rejected'])->default('unverified');
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Business Settings
            $table->json('business_hours')->nullable(); // Store operating hours
            $table->json('payment_methods')->nullable(); // Accepted payment methods
            $table->json('delivery_options')->nullable(); // Delivery/pickup options
            $table->boolean('is_delivery_available')->default(false);
            $table->boolean('is_pickup_available')->default(false);
            $table->decimal('delivery_radius', 8, 2)->nullable(); // in kilometers
            $table->decimal('delivery_fee', 8, 2)->nullable();
            $table->integer('min_order_amount')->nullable();
            $table->integer('max_order_amount')->nullable();
            
            // Business Metrics
            $table->integer('total_orders')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('total_reviews')->default(0);
            $table->integer('total_customers')->default(0);
            
            // Subscription and Billing
            $table->string('subscription_plan')->nullable();
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->boolean('is_subscription_active')->default(false);
            $table->decimal('monthly_fee', 10, 2)->nullable();
            
            // Features and Capabilities
            $table->json('enabled_features')->nullable(); // Features enabled for this merchant
            $table->json('api_permissions')->nullable(); // API permissions for this merchant
            $table->boolean('can_accept_online_orders')->default(false);
            $table->boolean('can_manage_inventory')->default(false);
            $table->boolean('can_use_analytics')->default(false);
            $table->boolean('can_use_marketing_tools')->default(false);
            
            // Compliance and Legal
            $table->boolean('gdpr_compliant')->default(false);
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->boolean('privacy_policy_accepted')->default(false);
            $table->timestamp('privacy_policy_accepted_at')->nullable();
            $table->boolean('data_processing_consent')->default(false);
            $table->timestamp('data_processing_consent_at')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['business_slug']);
            $table->index(['status', 'verification_status']);
            $table->index(['business_type', 'business_category']);
            $table->index(['business_city', 'business_country']);
            $table->index(['is_subscription_active', 'subscription_expires_at']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};