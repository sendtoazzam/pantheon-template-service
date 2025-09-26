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
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('two_factor_confirmed_at');
            }
            if (!Schema::hasColumn('users', 'failed_login_attempts')) {
                $table->integer('failed_login_attempts')->default(0)->after('password_changed_at');
            }
            if (!Schema::hasColumn('users', 'last_password_reset_at')) {
                $table->timestamp('last_password_reset_at')->nullable()->after('locked_until');
            }
            
            // Session management
            if (!Schema::hasColumn('users', 'current_session_id')) {
                $table->string('current_session_id')->nullable()->after('last_password_reset_at');
            }
            if (!Schema::hasColumn('users', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('current_session_id');
            }
            if (!Schema::hasColumn('users', 'active_sessions')) {
                $table->json('active_sessions')->nullable()->after('last_activity_at');
            }
            
            // Preferences
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone')->default('UTC')->after('active_sessions');
            }
            if (!Schema::hasColumn('users', 'language')) {
                $table->string('language')->default('en')->after('timezone');
            }
            if (!Schema::hasColumn('users', 'currency')) {
                $table->string('currency')->default('USD')->after('language');
            }
            if (!Schema::hasColumn('users', 'dark_mode')) {
                $table->boolean('dark_mode')->default(false)->after('currency');
            }
            if (!Schema::hasColumn('users', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable()->after('dark_mode');
            }
            
            // Account status and flags
            if (!Schema::hasColumn('users', 'account_status')) {
                $table->enum('account_status', ['active', 'inactive', 'suspended', 'pending_verification', 'locked'])->default('pending_verification')->after('notification_preferences');
            }
            if (!Schema::hasColumn('users', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('account_status');
            }
            if (!Schema::hasColumn('users', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable()->after('suspended_at');
            }
            if (!Schema::hasColumn('users', 'suspended_until')) {
                $table->timestamp('suspended_until')->nullable()->after('suspension_reason');
            }
            if (!Schema::hasColumn('users', 'is_deleted')) {
                $table->boolean('is_deleted')->default(false)->after('suspended_until');
            }
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('is_deleted');
            }
            
            // Onboarding
            if (!Schema::hasColumn('users', 'onboarding_completed')) {
                $table->boolean('onboarding_completed')->default(false)->after('deleted_at');
            }
            if (!Schema::hasColumn('users', 'onboarding_steps')) {
                $table->json('onboarding_steps')->nullable()->after('onboarding_completed');
            }
            if (!Schema::hasColumn('users', 'onboarding_completed_at')) {
                $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_steps');
            }
            
            // Analytics
            if (!Schema::hasColumn('users', 'login_count')) {
                $table->integer('login_count')->default(0)->after('onboarding_completed_at');
            }
            if (!Schema::hasColumn('users', 'first_login_at')) {
                $table->timestamp('first_login_at')->nullable()->after('login_count');
            }
            if (!Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('first_login_at');
            }
            if (!Schema::hasColumn('users', 'device_info')) {
                $table->json('device_info')->nullable()->after('last_seen_at');
            }
            
            // API access
            if (!Schema::hasColumn('users', 'api_key')) {
                $table->string('api_key')->nullable()->unique()->after('device_info');
            }
            if (!Schema::hasColumn('users', 'api_key_created_at')) {
                $table->timestamp('api_key_created_at')->nullable()->after('api_key');
            }
            if (!Schema::hasColumn('users', 'api_key_expires_at')) {
                $table->timestamp('api_key_expires_at')->nullable()->after('api_key_created_at');
            }
            if (!Schema::hasColumn('users', 'api_calls_count')) {
                $table->integer('api_calls_count')->default(0)->after('api_key_expires_at');
            }
            if (!Schema::hasColumn('users', 'last_api_call_at')) {
                $table->timestamp('last_api_call_at')->nullable()->after('api_calls_count');
            }
            
            // Vendor specific fields
            if (!Schema::hasColumn('users', 'is_merchant')) {
                $table->boolean('is_merchant')->default(false)->after('last_api_call_at');
            }
            if (!Schema::hasColumn('users', 'merchant_type')) {
                $table->string('merchant_type')->nullable()->after('is_merchant');
            }
            if (!Schema::hasColumn('users', 'business_name')) {
                $table->string('business_name')->nullable()->after('merchant_type');
            }
            if (!Schema::hasColumn('users', 'business_registration_number')) {
                $table->string('business_registration_number')->nullable()->after('business_name');
            }
            if (!Schema::hasColumn('users', 'tax_id')) {
                $table->string('tax_id')->nullable()->after('business_registration_number');
            }
            
            // Add indexes if they don't exist
            $indexes = [
                ['account_status', 'is_active'],
                ['is_merchant', 'merchant_type'],
                ['email_verified_at', 'is_email_verified'],
                ['last_login_at', 'last_seen_at'],
                ['created_at', 'account_status']
            ];
            
            foreach ($indexes as $index) {
                $indexName = implode('_', $index) . '_index';
                if (!$this->indexExists('users', $indexName)) {
                    $table->index($index, $indexName);
                }
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
                'password_changed_at', 'failed_login_attempts', 'last_password_reset_at',
                'current_session_id', 'last_activity_at', 'active_sessions',
                'timezone', 'language', 'currency', 'dark_mode', 'notification_preferences',
                'account_status', 'suspended_at', 'suspension_reason', 'suspended_until', 'is_deleted', 'deleted_at',
                'onboarding_completed', 'onboarding_steps', 'onboarding_completed_at',
                'login_count', 'first_login_at', 'last_seen_at', 'device_info',
                'api_key', 'api_key_created_at', 'api_key_expires_at', 'api_calls_count', 'last_api_call_at',
                'is_merchant', 'merchant_type', 'business_name', 'business_registration_number', 'tax_id'
            ]);
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists($table, $indexName)
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        foreach ($indexes as $index) {
            if ($index->Key_name === $indexName) {
                return true;
            }
        }
        return false;
    }
};