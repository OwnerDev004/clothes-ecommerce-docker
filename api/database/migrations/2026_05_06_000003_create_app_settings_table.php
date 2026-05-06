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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default(config('app.name'));
            $table->string('app_tagline')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->text('business_address')->nullable();
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('free_shipping_threshold', 10, 2)->default(0);
            $table->integer('low_stock_threshold')->default(20);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->json('shipping_rates')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
