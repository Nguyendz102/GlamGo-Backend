<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('message_customers')) {
            return;
        }

        if (Schema::hasColumn('message_customers', 'message')) {
            DB::statement('ALTER TABLE message_customers MODIFY message TEXT NULL');
        }

        Schema::table('message_customers', function (Blueprint $table) {
            if (! Schema::hasColumn('message_customers', 'message_type')) {
                $table->string('message_type', 20)->default('text')->after('message');
            }

            if (! Schema::hasColumn('message_customers', 'file_path')) {
                $table->string('file_path')->nullable()->after('message_type');
            }

            if (! Schema::hasColumn('message_customers', 'file_name')) {
                $table->string('file_name')->nullable()->after('file_path');
            }

            if (! Schema::hasColumn('message_customers', 'file_mime')) {
                $table->string('file_mime')->nullable()->after('file_name');
            }

            if (! Schema::hasColumn('message_customers', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('file_mime');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('message_customers')) {
            return;
        }

        Schema::table('message_customers', function (Blueprint $table) {
            foreach (['file_size', 'file_mime', 'file_name', 'file_path', 'message_type'] as $column) {
                if (Schema::hasColumn('message_customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('message_customers', 'message')) {
            DB::table('message_customers')
                ->whereNull('message')
                ->update(['message' => '']);
            DB::statement('ALTER TABLE message_customers MODIFY message TEXT NOT NULL');
        }
    }
};
