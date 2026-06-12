<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coupon')) {
            return;
        }

        DB::statement('ALTER TABLE coupon MODIFY discount_type DECIMAL(15,2) NOT NULL');
        DB::statement('ALTER TABLE coupon MODIFY min_order_value DECIMAL(15,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE coupon MODIFY max_value DECIMAL(15,2) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        // Expanding monetary columns is intentionally not reversed to avoid data loss.
    }
};
