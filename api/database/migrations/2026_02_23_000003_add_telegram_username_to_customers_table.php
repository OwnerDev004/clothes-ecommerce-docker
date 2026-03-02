<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('customers', 'telegram_username')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->string('telegram_username')->nullable()->after('telegram_chat_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('customers', 'telegram_username')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('telegram_username');
        });
    }
};
