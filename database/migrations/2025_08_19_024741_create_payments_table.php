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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('stripe');
            $table->string('provider_payment_id')->nullable()->index(); // e.g., pi_...
            $table->string('currency', 10)->default('usd');
            $table->decimal('amount', 12, 2);
            $table->string('method'); // card, wallet, upi
            $table->enum('status', ['pending','succeeded','failed','refunded','requires_action'])->default('pending');
            $table->string('reference')->unique(); // our reference (for status)
            $table->json('metadata')->nullable(); // customer info, order id, etc
            $table->timestamps();
            $table->index(['merchant_id','status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
