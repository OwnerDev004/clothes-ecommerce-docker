<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'brand_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'brand_id')) {
                $afterColumn = Schema::hasColumn('products', 'sub_category_id') ? 'sub_category_id' : 'category_id';
                $table->foreignId('brand_id')->nullable()->after($afterColumn)->constrained('brands')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('products', 'brand_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
        });
    }
};
