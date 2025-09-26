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
        Schema::create('audit_trail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // login, logout, create, update, delete, view, etc.
            $table->string('resource_type')->nullable(); // User, Product, Order, etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('old_values')->nullable(); // Previous values for updates
            $table->json('new_values')->nullable(); // New values for updates
            $table->text('description')->nullable();
            $table->string('status')->default('success'); // success, failed, warning
            $table->json('metadata')->nullable(); // Additional context data
            $table->timestamp('performed_at');
            $table->timestamps();
            
            $table->index(['user_id', 'performed_at']);
            $table->index(['action', 'performed_at']);
            $table->index(['resource_type', 'resource_id']);
            $table->index('ip_address');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trail');
    }
};
