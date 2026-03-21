<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->boolean('is_signup_coupon')->default(false)->after('is_active');
            $table->boolean('first_order_only')->default(false)->after('is_signup_coupon');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['is_signup_coupon', 'first_order_only']);
        });
    }
};

