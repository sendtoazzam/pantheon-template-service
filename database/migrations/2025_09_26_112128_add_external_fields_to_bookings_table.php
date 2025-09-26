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
        Schema::table('bookings', function (Blueprint $table) {
            // Add currency column if it doesn't exist
            if (!Schema::hasColumn('bookings', 'currency')) {
                $table->string('currency', 3)->default('USD')->after('final_amount');
            }
            
            // Add external_data column if it doesn't exist
            if (!Schema::hasColumn('bookings', 'external_data')) {
                $table->json('external_data')->nullable()->after('special_instructions');
            }
            
            // Add payment_data column if it doesn't exist
            if (!Schema::hasColumn('bookings', 'payment_data')) {
                $table->json('payment_data')->nullable()->after('external_data');
            }
            
            // Add success_processed_at column if it doesn't exist
            if (!Schema::hasColumn('bookings', 'success_processed_at')) {
                $table->timestamp('success_processed_at')->nullable()->after('updated_at');
            }
            
            // Add indexes for better performance
            if (!Schema::hasIndex('bookings', 'bookings_external_booking_id_index')) {
                $table->index('external_booking_id');
            }
            if (!Schema::hasIndex('bookings', 'bookings_success_processed_at_index')) {
                $table->index('success_processed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['external_booking_id']);
            $table->dropIndex(['success_processed_at']);
            
            $table->dropColumn([
                'external_booking_id',
                'currency',
                'external_data',
                'payment_data',
                'success_processed_at',
            ]);
        });
    }
};