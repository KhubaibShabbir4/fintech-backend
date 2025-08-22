<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('stripe_payment_intent')->nullable()->after('extra');
            $table->string('stripe_session_id')->nullable()->after('stripe_payment_intent');
            $table->enum('status', ['pending','success','failed','refunded'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['stripe_payment_intent','stripe_session_id']);
            $table->enum('status', ['initiated','succeeded','failed','refunded'])->default('initiated')->change();
        });
    }
};


