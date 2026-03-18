<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'dress_type_id')) {
                if (Schema::hasTable('collection_product')) {
                    DB::table('collection_product')->insertUsing(
                        ['collection_id', 'product_id', 'created_at', 'updated_at'],
                        DB::table('products')
                            ->selectRaw('dress_type_id, id, NOW(), NOW()')
                            ->whereNotNull('dress_type_id')
                    );
                }
                $table->dropForeign(['dress_type_id']);
                $table->dropColumn('dress_type_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'dress_type_id')) {
                $table->foreignId('dress_type_id')->nullable()->constrained('collections')->nullOnDelete();
            }
        });
    }
};
