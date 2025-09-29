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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type'); // email, sms, push
            $table->string('provider_name'); // smtp, postmark, twilio, nexmo, etc.
            $table->string('display_name'); // Human readable name
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->json('configuration'); // Provider-specific settings
            $table->json('capabilities'); // What this provider can do
            $table->text('description')->nullable();
            $table->integer('priority')->default(0); // For fallback order
            $table->json('limits')->nullable(); // Rate limits, quotas, etc.
            $table->timestamps();
            
            $table->index(['provider_type', 'is_active']);
            $table->unique(['provider_type', 'provider_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};