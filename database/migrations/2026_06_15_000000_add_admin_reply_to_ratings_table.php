<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            if (! Schema::hasColumn('ratings', 'admin_reply')) {
                $table->text('admin_reply')->nullable()->after('comment');
            }

            if (! Schema::hasColumn('ratings', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('user_id')->index();
            }

            if (! Schema::hasColumn('ratings', 'admin_replied_at')) {
                $table->timestamp('admin_replied_at')->nullable()->after('admin_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            foreach (['admin_reply', 'admin_id', 'admin_replied_at'] as $column) {
                if (Schema::hasColumn('ratings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
