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

        DB::statement('ALTER TABLE coupon MODIFY start_date DATETIME NULL');
        DB::statement('ALTER TABLE coupon MODIFY end_date DATETIME NULL');
    }

    public function down(): void
    {
        // Nullable date windows are kept because existing vouchers may rely on them.
    }
};
