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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // General Preferences
            $table->string('language')->default('en');
            $table->string('locale')->default('en_US');
            $table->string('timezone')->default('UTC');
            $table->string('currency')->default('USD');
            $table->string('date_format')->default('Y-m-d');
            $table->string('time_format')->default('24'); // 12 or 24 hour
            $table->string('number_format')->default('US'); // US, EU, etc.
            
            // UI/UX Preferences
            $table->boolean('dark_mode')->default(false);
            $table->string('theme')->default('default');
            $table->string('color_scheme')->default('blue');
            $table->string('font_size')->default('medium'); // small, medium, large
            $table->string('font_family')->default('system');
            $table->boolean('animations_enabled')->default(true);
            $table->boolean('sounds_enabled')->default(true);
            $table->string('sidebar_position')->default('left'); // left, right, hidden
            $table->boolean('compact_mode')->default(false);
            
            // Notification Preferences
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->boolean('push_notifications')->default(true);
            $table->boolean('in_app_notifications')->default(true);
            $table->boolean('marketing_emails')->default(false);
            $table->boolean('newsletter_subscription')->default(false);
            $table->boolean('promotional_offers')->default(false);
            $table->boolean('security_alerts')->default(true);
            $table->boolean('system_updates')->default(true);
            $table->boolean('booking_reminders_notifications')->default(true);
            $table->boolean('payment_notifications_general')->default(true);
            $table->boolean('order_updates_general')->default(true);
            
            // Communication Preferences
            $table->string('preferred_contact_method')->default('email'); // email, phone, sms
            $table->string('preferred_contact_time')->default('anytime'); // morning, afternoon, evening, anytime
            $table->json('contact_time_restrictions')->nullable(); // Specific time restrictions
            $table->boolean('allow_phone_calls')->default(true);
            $table->boolean('allow_sms')->default(false);
            $table->boolean('allow_whatsapp')->default(false);
            $table->boolean('allow_telegram')->default(false);
            
            // Privacy Preferences
            $table->boolean('profile_public')->default(false);
            $table->boolean('show_online_status')->default(true);
            $table->boolean('show_last_seen')->default(true);
            $table->boolean('show_activity_status')->default(true);
            $table->boolean('allow_profile_search')->default(true);
            $table->boolean('allow_direct_messages')->default(true);
            $table->boolean('show_email_publicly')->default(false);
            $table->boolean('show_phone_publicly')->default(false);
            $table->json('privacy_settings')->nullable(); // Additional privacy settings
            
            // Security Preferences
            $table->boolean('two_factor_auth')->default(false);
            $table->boolean('login_notifications')->default(true);
            $table->boolean('device_notifications')->default(true);
            $table->boolean('location_tracking')->default(false);
            $table->boolean('data_collection')->default(true);
            $table->boolean('analytics_tracking')->default(true);
            $table->boolean('cookie_consent')->default(false);
            $table->json('security_settings')->nullable(); // Additional security settings
            
            // Booking Preferences
            $table->string('preferred_booking_time')->default('morning'); // morning, afternoon, evening, anytime
            $table->integer('advance_booking_days')->default(7); // How many days in advance
            $table->boolean('auto_confirm_bookings')->default(false);
            $table->boolean('booking_reminders_preferences')->default(true);
            $table->integer('reminder_hours_before')->default(24);
            $table->boolean('cancellation_notifications')->default(true);
            $table->boolean('rescheduling_notifications')->default(true);
            $table->json('booking_preferences')->nullable(); // Additional booking settings
            
            // Payment Preferences
            $table->string('preferred_payment_method')->default('card'); // card, bank, paypal, etc.
            $table->boolean('save_payment_methods')->default(false);
            $table->boolean('auto_pay')->default(false);
            $table->boolean('receipt_emails')->default(true);
            $table->boolean('invoice_emails')->default(true);
            $table->json('payment_preferences')->nullable(); // Additional payment settings
            
            // Location Preferences
            $table->string('default_country')->nullable();
            $table->string('default_city')->nullable();
            $table->string('default_state')->nullable();
            $table->string('default_postal_code')->nullable();
            $table->decimal('default_latitude', 10, 8)->nullable();
            $table->decimal('default_longitude', 11, 8)->nullable();
            $table->integer('search_radius_km')->default(25); // Default search radius
            $table->json('saved_locations')->nullable(); // User's saved locations
            
            // Dietary and Health Preferences
            $table->json('dietary_restrictions')->nullable(); // vegetarian, vegan, gluten-free, etc.
            $table->json('allergies')->nullable();
            $table->json('health_conditions')->nullable();
            $table->json('medication_reminders')->nullable();
            $table->json('health_preferences')->nullable(); // Additional health settings
            
            // Accessibility Preferences
            $table->boolean('screen_reader_support')->default(false);
            $table->boolean('high_contrast_mode')->default(false);
            $table->boolean('reduced_motion')->default(false);
            $table->boolean('keyboard_navigation')->default(false);
            $table->string('text_size')->default('normal'); // small, normal, large, extra-large
            $table->json('accessibility_settings')->nullable(); // Additional accessibility settings
            
            // Social Preferences
            $table->boolean('social_login_enabled')->default(true);
            $table->json('connected_social_accounts')->nullable();
            $table->boolean('social_sharing')->default(false);
            $table->boolean('public_reviews')->default(true);
            $table->boolean('recommendation_emails')->default(false);
            $table->json('social_preferences')->nullable(); // Additional social settings
            
            // Business Preferences (for merchants)
            $table->boolean('business_notifications')->default(true);
            $table->boolean('order_notifications')->default(true);
            $table->boolean('payment_notifications_business')->default(true);
            $table->boolean('customer_messages')->default(true);
            $table->boolean('review_notifications')->default(true);
            $table->boolean('analytics_reports')->default(true);
            $table->json('business_preferences')->nullable(); // Additional business settings
            
            // Custom Preferences
            $table->json('custom_preferences')->nullable(); // User-defined custom preferences
            $table->json('feature_flags')->nullable(); // Feature toggles
            $table->json('experimental_features')->nullable(); // Beta features
            $table->json('third_party_integrations')->nullable(); // External service preferences
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id']);
            $table->index(['language', 'locale']);
            $table->index(['timezone']);
            $table->index(['dark_mode', 'theme']);
            $table->index(['email_notifications', 'push_notifications']);
            $table->index(['profile_public']);
            $table->index(['two_factor_auth']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};