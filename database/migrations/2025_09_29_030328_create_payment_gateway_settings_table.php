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
        Schema::create('payment_gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_name'); // stripe, paypal, razorpay, square, etc.
            $table->string('display_name'); // Human readable name
            $table->string('gateway_type'); // card, wallet, bank_transfer, crypto
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->json('configuration'); // Gateway-specific settings (API keys, etc.)
            $table->json('supported_currencies'); // Array of supported currencies
            $table->json('supported_countries'); // Array of supported countries
            $table->json('supported_payment_methods'); // Array of payment methods
            $table->decimal('transaction_fee_percentage', 5, 4)->default(0); // Fee percentage
            $table->decimal('transaction_fee_fixed', 10, 2)->default(0); // Fixed fee amount
            $table->decimal('min_amount', 10, 2)->default(0); // Minimum transaction amount
            $table->decimal('max_amount', 10, 2)->nullable(); // Maximum transaction amount
            $table->json('webhook_configuration')->nullable(); // Webhook settings
            $table->json('limits')->nullable(); // Rate limits, quotas, etc.
            $table->text('description')->nullable();
            $table->integer('priority')->default(0); // For display order
            $table->timestamps();
            
            $table->index(['gateway_name', 'is_active']);
            $table->unique('gateway_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
    }
};