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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Notification Content
            $table->string('type'); // booking_confirmed, payment_received, system_alert, etc.
            $table->string('title');
            $table->text('message');
            $table->text('description')->nullable();
            $table->json('data')->nullable(); // Additional notification data
            
            // Notification Channels
            $table->boolean('in_app')->default(true);
            $table->boolean('email')->default(false);
            $table->boolean('sms')->default(false);
            $table->boolean('push')->default(false);
            $table->boolean('webhook')->default(false);
            
            // Delivery Status
            $table->enum('status', [
                'pending', 'sent', 'delivered', 'read', 'failed', 'cancelled'
            ])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // Delivery Attempts
            $table->integer('delivery_attempts')->default(0);
            $table->integer('max_delivery_attempts')->default(3);
            $table->timestamp('last_delivery_attempt')->nullable();
            $table->text('delivery_error')->nullable();
            $table->json('delivery_log')->nullable(); // Detailed delivery logs
            
            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_scheduled')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->json('recurrence_pattern')->nullable(); // For recurring notifications
            
            // Template and Localization
            $table->string('template')->nullable(); // Notification template used
            $table->string('language')->default('en');
            $table->json('template_variables')->nullable(); // Variables for template
            $table->string('locale')->default('en_US');
            
            // Action and Interaction
            $table->string('action_url')->nullable(); // URL to navigate to when clicked
            $table->string('action_text')->nullable(); // Text for action button
            $table->json('action_data')->nullable(); // Data for action
            $table->boolean('requires_action')->default(false);
            $table->timestamp('action_taken_at')->nullable();
            $table->text('action_taken')->nullable();
            
            // Grouping and Threading
            $table->string('group_key')->nullable(); // For grouping related notifications
            $table->string('thread_id')->nullable(); // For conversation threading
            $table->integer('thread_position')->nullable(); // Position in thread
            $table->boolean('is_thread_starter')->default(false);
            
            // Categories and Tags
            $table->string('category')->nullable(); // system, booking, payment, etc.
            $table->json('tags')->nullable(); // Additional categorization
            $table->string('subcategory')->nullable();
            
            // Business Context
            $table->string('business_type')->nullable(); // booking, payment, user, etc.
            $table->unsignedBigInteger('business_id')->nullable(); // Related business entity
            $table->string('business_reference')->nullable(); // Reference to business entity
            $table->json('business_data')->nullable(); // Business-specific data
            
            // User Preferences
            $table->boolean('respect_user_preferences')->default(true);
            $table->boolean('can_be_disabled')->default(true);
            $table->boolean('is_mandatory')->default(false);
            $table->json('user_preferences')->nullable(); // User-specific preferences
            
            // Analytics and Tracking
            $table->integer('view_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->integer('dismiss_count')->default(0);
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->json('interaction_data')->nullable(); // User interaction data
            
            // Security and Compliance
            $table->boolean('is_encrypted')->default(false);
            $table->string('encryption_key')->nullable();
            $table->boolean('contains_pii')->default(false);
            $table->boolean('gdpr_compliant')->default(true);
            $table->timestamp('retention_until')->nullable();
            
            // External Integration
            $table->string('external_id')->nullable(); // External system ID
            $table->string('external_system')->nullable(); // External system name
            $table->json('external_data')->nullable(); // External system data
            $table->string('webhook_url')->nullable();
            $table->json('webhook_headers')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['is_read', 'created_at']);
            $table->index(['scheduled_at', 'is_scheduled']);
            $table->index(['expires_at']);
            $table->index(['group_key', 'thread_id']);
            $table->index(['category', 'subcategory']);
            $table->index(['business_type', 'business_id']);
            $table->index(['priority', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};