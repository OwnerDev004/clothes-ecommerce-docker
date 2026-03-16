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
        if (Schema::hasColumn('customers', 'enable_telegram_alerts')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('enable_telegram_alerts')->default(false)->after('telegram_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('customers', 'enable_telegram_alerts')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('enable_telegram_alerts');
        });
    }
};
