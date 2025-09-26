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
            // Guard-specific fields
            $table->boolean('is_admin')->default(false)->after('status');
            $table->boolean('is_vendor')->default(false)->after('is_admin');
            $table->boolean('is_active')->default(true)->after('is_vendor');
            
            // Security fields
            $table->text('two_factor_secret')->nullable()->after('is_active');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            
            // Login security
            $table->integer('login_attempts')->default(0)->after('two_factor_confirmed_at');
            $table->timestamp('locked_until')->nullable()->after('login_attempts');
            $table->string('last_login_ip', 45)->nullable()->after('locked_until');
            $table->text('last_login_user_agent')->nullable()->after('last_login_ip');
            
            // Indexes for performance
            $table->index(['is_admin', 'is_active']);
            $table->index(['is_vendor', 'is_active']);
            $table->index(['status', 'is_active']);
            $table->index('locked_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_admin', 'is_active']);
            $table->dropIndex(['is_vendor', 'is_active']);
            $table->dropIndex(['status', 'is_active']);
            $table->dropIndex(['locked_until']);
            
            $table->dropColumn([
                'is_admin',
                'is_vendor',
                'is_active',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'login_attempts',
                'locked_until',
                'last_login_ip',
                'last_login_user_agent',
            ]);
        });
    }
};
