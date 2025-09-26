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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            
            // Booking Details
            $table->string('service_type')->nullable(); // consultation, appointment, delivery, etc.
            $table->string('service_name')->nullable();
            $table->text('service_description')->nullable();
            $table->decimal('service_price', 10, 2)->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2);
            
            // Scheduling
            $table->date('booking_date');
            $table->time('booking_time');
            $table->timestamp('booking_datetime');
            $table->integer('duration_minutes')->nullable(); // Service duration
            $table->timestamp('expected_start_time')->nullable();
            $table->timestamp('expected_end_time')->nullable();
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();
            
            // Status and State
            $table->enum('status', [
                'pending', 'confirmed', 'in_progress', 'completed', 
                'cancelled', 'no_show', 'rescheduled', 'refunded'
            ])->default('pending');
            $table->enum('payment_status', [
                'pending', 'paid', 'partially_paid', 'refunded', 
                'failed', 'cancelled'
            ])->default('pending');
            $table->enum('fulfillment_status', [
                'pending', 'preparing', 'ready', 'in_transit', 
                'delivered', 'completed', 'cancelled'
            ])->default('pending');
            
            // Customer Information
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->text('customer_address')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_state')->nullable();
            $table->string('customer_country')->nullable();
            $table->string('customer_postal_code')->nullable();
            $table->decimal('customer_latitude', 10, 8)->nullable();
            $table->decimal('customer_longitude', 11, 8)->nullable();
            
            // Special Instructions
            $table->text('special_instructions')->nullable();
            $table->text('dietary_restrictions')->nullable();
            $table->text('accessibility_requirements')->nullable();
            $table->json('custom_fields')->nullable(); // Additional custom data
            
            // Delivery/Pickup Information
            $table->enum('fulfillment_type', ['delivery', 'pickup', 'in_store', 'virtual'])->default('pickup');
            $table->text('delivery_address')->nullable();
            $table->string('delivery_contact_name')->nullable();
            $table->string('delivery_contact_phone')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('delivery_distance', 8, 2)->nullable(); // in kilometers
            $table->integer('estimated_delivery_time')->nullable(); // in minutes
            
            // Payment Information
            $table->string('payment_method')->nullable(); // cash, card, online, etc.
            $table->string('payment_gateway')->nullable();
            $table->string('payment_transaction_id')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->json('payment_details')->nullable(); // Additional payment info
            
            // Cancellation and Refunds
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('cancellation_reason')->nullable();
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->timestamp('refund_date')->nullable();
            $table->string('refund_reference')->nullable();
            $table->text('refund_reason')->nullable();
            
            // Rescheduling
            $table->timestamp('rescheduled_at')->nullable();
            $table->foreignId('rescheduled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('original_booking_date')->nullable();
            $table->time('original_booking_time')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->integer('reschedule_count')->default(0);
            
            // Staff Assignment
            $table->foreignId('assigned_staff_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('assigned_staff_name')->nullable();
            $table->timestamp('staff_assigned_at')->nullable();
            
            // Rating and Review
            $table->integer('customer_rating')->nullable(); // 1-5 stars
            $table->text('customer_review')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->integer('merchant_rating')->nullable(); // Merchant's rating of customer
            $table->text('merchant_notes')->nullable();
            
            // Notifications
            $table->boolean('customer_notified')->default(false);
            $table->boolean('merchant_notified')->default(false);
            $table->boolean('staff_notified')->default(false);
            $table->timestamp('last_notification_sent')->nullable();
            $table->json('notification_history')->nullable();
            
            // Analytics and Tracking
            $table->string('source')->nullable(); // website, app, phone, etc.
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('referral_code')->nullable();
            $table->json('tracking_data')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['merchant_id', 'status']);
            $table->index(['booking_date', 'booking_time']);
            $table->index(['status', 'payment_status']);
            $table->index(['fulfillment_type', 'fulfillment_status']);
            $table->index(['customer_email', 'customer_phone']);
            $table->index(['assigned_staff_id', 'booking_date']);
            $table->index(['created_at', 'status']);
            $table->index(['booking_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};