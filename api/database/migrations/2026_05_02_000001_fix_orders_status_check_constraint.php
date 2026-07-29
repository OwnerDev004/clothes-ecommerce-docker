<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // PostgreSQL supports ALTER TABLE ... DROP/ADD CONSTRAINT;
        // SQLite does not. This migration is only relevant for PostgreSQL.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');

            DB::statement("
                ALTER TABLE orders
                ADD CONSTRAINT orders_status_check
                CHECK (status IN (
                    'order_confirming',
                    'payment_confirmed',
                    'processing',
                    'shipped',
                    'delivered',
                    'cancel',
                    'cancelled',
                    'refunded'
                ))
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');

            DB::statement("
                ALTER TABLE orders
                ADD CONSTRAINT orders_status_check
                CHECK (status IN (
                    'order_confirming',
                    'payment_confirmed',
                    'processing',
                    'shipped',
                    'delivered',
                    'cancel',
                    'refunded'
                ))
            ");
        }
    }
};
