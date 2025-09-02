<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Add merchant_id referencing merchants table
            $table->foreignId('merchant_id')->nullable()->after('payment_id')->constrained()->cascadeOnDelete();
            $table->index(['merchant_id', 'status']);
        });

        // Backfill existing rows from payments
        DB::statement('UPDATE transactions t JOIN payments p ON t.payment_id = p.id SET t.merchant_id = p.merchant_id WHERE t.merchant_id = 0 OR t.merchant_id IS NULL');

        // Make column non-nullable after backfill
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('merchant_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop FK and column
            $table->dropForeign(['merchant_id']);
            $table->dropColumn('merchant_id');
        });
    }
};


