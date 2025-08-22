<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'provider_payment_id')) {
                $table->string('provider_payment_id')->nullable()->after('provider');
            }
            $table->enum('status', ['pending','success','failed','refunded'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending','succeeded','failed','refunded'])->default('pending')->change();
        });
    }
};


