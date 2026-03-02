<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('telegram_user_id')->nullable()->unique()->after('avatar_public_id');
            $table->string('telegram_chat_id')->nullable()->after('telegram_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['telegram_user_id', 'telegram_chat_id']);
        });
    }
};
