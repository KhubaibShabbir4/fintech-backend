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
        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('session_ref')->unique(); // our session key
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('usd');
            $table->string('method'); // card/wallet/upi
            $table->string('return_url_success')->nullable();
            $table->string('return_url_failure')->nullable();
            $table->enum('status', ['open','processing','succeeded','failed'])->default('open');
            $table->json('customer')->nullable();
            $table->json('cart')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
