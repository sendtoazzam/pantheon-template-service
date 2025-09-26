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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            
            // Session Information
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('device_name')->nullable();
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('os_version')->nullable();
            
            // Location Information
            $table->string('country')->nullable();
            $table->string('country_code')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('timezone')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Session Status
            $table->enum('status', ['active', 'expired', 'terminated', 'suspended'])->default('active');
            $table->boolean('is_secure')->default(false);
            $table->boolean('is_mobile')->default(false);
            $table->boolean('is_trusted_device')->default(false);
            $table->boolean('is_remember_me')->default(false);
            
            // Security Information
            $table->string('login_method')->nullable(); // password, 2fa, social, etc.
            $table->boolean('two_factor_verified')->default(false);
            $table->timestamp('two_factor_verified_at')->nullable();
            $table->string('verification_method')->nullable(); // sms, email, app
            $table->json('security_events')->nullable(); // Security-related events
            
            // Session Duration
            $table->timestamp('login_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('logout_at')->nullable();
            $table->integer('session_duration_minutes')->nullable();
            $table->integer('idle_time_minutes')->default(0);
            $table->integer('max_idle_time_minutes')->default(30);
            
            // Activity Tracking
            $table->integer('page_views')->default(0);
            $table->integer('api_calls')->default(0);
            $table->json('pages_visited')->nullable(); // Array of visited pages
            $table->json('actions_performed')->nullable(); // Array of actions
            $table->text('last_page_visited')->nullable();
            $table->text('last_action_performed')->nullable();
            
            // Session Data
            $table->json('session_data')->nullable(); // Additional session data
            $table->json('preferences')->nullable(); // Session-specific preferences
            $table->json('metadata')->nullable(); // Additional metadata
            
            // Termination Information
            $table->enum('termination_reason', [
                'user_logout', 'timeout', 'forced_logout', 'security_breach', 
                'account_suspended', 'password_changed', 'admin_action'
            ])->nullable();
            $table->text('termination_notes')->nullable();
            $table->foreignId('terminated_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Refresh Token Information
            $table->string('refresh_token')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            $table->boolean('refresh_token_used')->default(false);
            $table->timestamp('refresh_token_used_at')->nullable();
            
            // API Session Information
            $table->string('api_token')->nullable();
            $table->timestamp('api_token_expires_at')->nullable();
            $table->json('api_permissions')->nullable();
            $table->integer('api_rate_limit')->nullable();
            $table->integer('api_calls_made')->default(0);
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['session_id']);
            $table->index(['ip_address']);
            $table->index(['login_at', 'logout_at']);
            $table->index(['last_activity_at']);
            $table->index(['status', 'is_trusted_device']);
            $table->index(['device_type', 'browser']);
            $table->index(['country', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};