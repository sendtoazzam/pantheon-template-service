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
        Schema::create('api_call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('method', 10); // GET, POST, PUT, DELETE, etc.
            $table->string('url', 500); // Full URL
            $table->string('endpoint', 200); // API endpoint path
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->json('request_params')->nullable(); // Query parameters
            $table->integer('response_status');
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->integer('response_size_bytes')->default(0);
            $table->decimal('execution_time_ms', 8, 2); // Response time in milliseconds
            $table->bigInteger('memory_usage_bytes')->default(0);
            $table->bigInteger('peak_memory_bytes')->default(0);
            $table->string('status', 20)->default('success'); // success, error, warning
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamp('called_at');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'called_at']);
            $table->index(['method', 'endpoint']);
            $table->index(['response_status', 'called_at']);
            $table->index(['ip_address', 'called_at']);
            $table->index('called_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_call_logs');
    }
};
