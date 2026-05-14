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
        DB::statement("ALTER TABLE app_settings RENAME COLUMN currency_code TO default_currency_code");

        Schema::table('app_settings', function (Blueprint $table) {
            $table->decimal('exchange_rate', 10, 2)->nullable()->after('shipping_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropColumns('app_settings', ['exchange_rate', 'default_currency_code']);
    }
};
