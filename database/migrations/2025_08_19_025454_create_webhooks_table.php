<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('stripe');
            $table->string('event_id')->unique();
            $table->string('type');
            $table->boolean('processed')->default(false);
            $table->json('payload');
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
