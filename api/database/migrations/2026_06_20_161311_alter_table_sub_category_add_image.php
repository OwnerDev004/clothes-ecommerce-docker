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
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->tinyInteger('order_num')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('sub_categories')->cascadeOnDelete()->cascadeOnUpdate();
            $table->tinyInteger('level')->check('level IN (1, 2)')->default(1);
            $table->string('image_url')->nullable()->after('slug');
            $table->string('image_public_id')->nullable()->after('image_url');
            $table->boolean('status')->default(true);

            // Indexing
            $table->index(['parent_id', 'level', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropColumn(['order_num', 'image_url', 'image_public_id', 'status', 'parent_id', 'level']);
        });
    }
};
