<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            if (!Schema::hasColumn('collections', 'image_url')) {
                $table->string('image_url')->nullable()->after('img');
            }
            if (!Schema::hasColumn('collections', 'image_public_id')) {
                $table->string('image_public_id')->nullable()->after('image_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            if (Schema::hasColumn('collections', 'image_public_id')) {
                $table->dropColumn('image_public_id');
            }
            if (Schema::hasColumn('collections', 'image_url')) {
                $table->dropColumn('image_url');
            }
        });
    }
};
