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
        Schema::table('users', function (Blueprint $table) {
            // 2FA fields (only add if they don't exist)
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false);
            }
            if (!Schema::hasColumn('users', 'two_factor_backup_codes')) {
                $table->text('two_factor_backup_codes')->nullable();
            }
            
            // Security fields
            if (!Schema::hasColumn('users', 'is_locked')) {
                $table->boolean('is_locked')->default(false);
            }
            if (!Schema::hasColumn('users', 'locked_until')) {
                $table->timestamp('locked_until')->nullable();
            }
            if (!Schema::hasColumn('users', 'failed_login_attempts')) {
                $table->integer('failed_login_attempts')->default(0);
            }
            if (!Schema::hasColumn('users', 'last_failed_login_at')) {
                $table->timestamp('last_failed_login_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'password_history')) {
                $table->text('password_history')->nullable();
            }
            
            // Email verification
            if (!Schema::hasColumn('users', 'email_verification_sent_at')) {
                $table->timestamp('email_verification_sent_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'email_verification_token')) {
                $table->string('email_verification_token')->nullable();
            }
            
            // Account security
            if (!Schema::hasColumn('users', 'force_password_change')) {
                $table->boolean('force_password_change')->default(false);
            }
            if (!Schema::hasColumn('users', 'last_security_check_at')) {
                $table->timestamp('last_security_check_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_backup_codes',
                'is_locked',
                'locked_until',
                'failed_login_attempts',
                'last_failed_login_at',
                'password_history',
                'email_verification_sent_at',
                'email_verification_token',
                'password_changed_at',
                'force_password_change',
                'last_security_check_at'
            ]);
        });
    }
};