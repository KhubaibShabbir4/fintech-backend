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
        Schema::create('errorlogs', function (Blueprint $table) {
            $table->id();
            $table->string('error_type')->nullable(); // Type of error (e.g., 'API_ERROR', 'VALIDATION_ERROR', etc.)
            $table->string('error_code')->nullable(); // HTTP status code or custom error code
            $table->text('error_message'); // Error message description
            $table->text('error_details')->nullable(); // Detailed error information (JSON format)
            $table->string('api_endpoint')->nullable(); // The API endpoint that caused the error
            $table->string('http_method', 10)->nullable(); // HTTP method (GET, POST, PUT, DELETE, etc.)
            $table->text('request_payload')->nullable(); // Request data that caused the error (JSON format)
            $table->text('response_payload')->nullable(); // Response received from the API (JSON format)
            $table->string('user_agent')->nullable(); // User agent from the request
            $table->ipAddress('ip_address')->nullable(); // IP address of the client
            $table->unsignedBigInteger('user_id')->nullable(); // User who triggered the error (if authenticated)
            $table->string('session_id')->nullable(); // Session ID
            $table->text('stack_trace')->nullable(); // Stack trace for debugging
            $table->string('environment', 20)->default('production'); // Environment where error occurred
            $table->boolean('is_resolved')->default(false); // Whether the error has been resolved
            $table->text('resolution_notes')->nullable(); // Notes about how the error was resolved
            $table->timestamp('resolved_at')->nullable(); // When the error was resolved
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index(['error_type', 'created_at']);
            $table->index(['api_endpoint', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['is_resolved', 'created_at']);
            
            // Foreign key constraint (optional)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('errorlogs');
    }
};
