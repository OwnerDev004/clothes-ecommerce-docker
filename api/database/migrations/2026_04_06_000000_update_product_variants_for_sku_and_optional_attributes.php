<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'sku')) {
                $table->string('sku')->nullable()->unique()->after('product_id');
            }

            if (!Schema::hasColumn('product_variants', 'color')) {
                $table->string('color')->nullable()->after('sku');
            }
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'color_id')) {
                $table->dropUnique(['product_id', 'color_id', 'size_id']);
                $table->dropConstrainedForeignId('color_id');
            }
            $table->foreignId('size_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'color', 'size_id']);
            $table->dropColumn('color');
            $table->dropUnique(['sku']);
            $table->dropColumn('sku');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'color_id')) {
                $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            }
            $table->foreignId('size_id')->nullable(false)->change();
            $table->unique(['product_id', 'color_id', 'size_id']);
        });
    }
};
