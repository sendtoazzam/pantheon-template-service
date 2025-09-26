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
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable();
            
            // Activity Information
            $table->string('action'); // login, logout, view, create, update, delete, etc.
            $table->string('resource_type')->nullable(); // User, Booking, Merchant, etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('endpoint')->nullable(); // API endpoint or page URL
            $table->string('method')->nullable(); // GET, POST, PUT, DELETE, etc.
            
            // Request Information
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            
            // Activity Details
            $table->text('description')->nullable();
            $table->json('old_values')->nullable(); // Previous values for updates
            $table->json('new_values')->nullable(); // New values for updates
            $table->json('metadata')->nullable(); // Additional context data
            $table->text('error_message')->nullable(); // If activity failed
            
            // Status and Result
            $table->enum('status', ['success', 'failed', 'pending', 'cancelled'])->default('success');
            $table->integer('response_code')->nullable(); // HTTP response code
            $table->integer('execution_time_ms')->nullable(); // Execution time in milliseconds
            $table->integer('memory_usage_bytes')->nullable(); // Memory usage in bytes
            
            // Security and Risk
            $table->integer('risk_score')->default(0); // 0-100 risk assessment
            $table->boolean('is_suspicious')->default(false);
            $table->json('security_flags')->nullable(); // Security-related flags
            $table->boolean('requires_review')->default(false);
            
            // Context Information
            $table->string('referrer')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('referral_code')->nullable();
            $table->json('tracking_data')->nullable();
            
            // API Specific
            $table->string('api_version')->nullable();
            $table->string('api_key')->nullable();
            $table->integer('rate_limit_remaining')->nullable();
            $table->integer('rate_limit_reset')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('response_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->longText('response_body')->nullable();
            
            // Business Logic
            $table->decimal('amount', 15, 2)->nullable(); // For financial activities
            $table->string('currency')->nullable();
            $table->string('transaction_id')->nullable();
            $table->json('business_data')->nullable(); // Business-specific data
            
            // Timestamps
            $table->timestamp('performed_at');
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'performed_at']);
            $table->index(['action', 'performed_at']);
            $table->index(['resource_type', 'resource_id']);
            $table->index(['status', 'performed_at']);
            $table->index(['ip_address', 'performed_at']);
            $table->index(['is_suspicious', 'requires_review']);
            $table->index(['risk_score', 'performed_at']);
            $table->index(['session_id', 'performed_at']);
            $table->index(['endpoint', 'method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};