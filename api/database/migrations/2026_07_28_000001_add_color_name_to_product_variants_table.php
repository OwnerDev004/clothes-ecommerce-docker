<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('product_variants', 'color_name')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->string('color_name', 64)->default('Unknown')->after('color_label');
            });
        }

        DB::table('product_variants')
            ->whereNull('color_name')
            ->orWhere('color_name', '')
            ->update([
                'color_name' => DB::raw("COALESCE(NULLIF(color_label, ''), NULLIF(color, ''), 'Unknown')"),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_variants', 'color_name')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('color_name');
            });
        }
    }
};
