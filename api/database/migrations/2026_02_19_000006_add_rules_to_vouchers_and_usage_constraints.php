<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
            $table->unsignedInteger('max_uses_per_customer')->default(1)->after('max_order');
        });

        Schema::table('voucher_uses', function (Blueprint $table) {
            $table->unique(['voucher_id', 'order_id']);
            $table->index(['voucher_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_uses', function (Blueprint $table) {
            $table->dropUnique(['voucher_id', 'order_id']);
            $table->dropIndex(['voucher_id', 'customer_id']);
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'max_uses_per_customer']);
        });
    }
};
