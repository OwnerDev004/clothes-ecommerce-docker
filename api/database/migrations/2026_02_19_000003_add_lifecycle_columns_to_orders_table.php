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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal_price', 10, 2)->default(0)->after('order_date');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('subtotal_price');
            $table->enum('status', ['order_confirming', 'payment_confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])->after('subtotal_price')->default('pending');
            $table->foreignId('voucher_id')->nullable()->after('customer_id')->constrained('vouchers')->nullOnDelete();
            $table->string('payment_provider')->nullable()->after('payment_method');
            $table->string('payment_reference')->nullable()->after('payment_provider');
            $table->timestamp('payment_expires_at')->nullable()->after('payment_reference');
            $table->timestamp('paid_at')->nullable()->after('payment_expires_at');
            $table->timestamp('cancelled_at')->nullable()->after('paid_at');
            $table->timestamp('refunded_at')->nullable()->after('cancelled_at');
            $table->timestamp('stock_restored_at')->nullable()->after('refunded_at');

            $table->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['payment_status']);
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn([
                'subtotal_price',
                'discount_amount',
                'status',
                'payment_provider',
                'payment_reference',
                'payment_expires_at',
                'paid_at',
                'cancelled_at',
                'refunded_at',
                'stock_restored_at',
            ]);
        });
    }
};
