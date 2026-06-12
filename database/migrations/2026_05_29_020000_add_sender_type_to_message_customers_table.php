<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('message_customers')) {
            return;
        }

        Schema::table('message_customers', function (Blueprint $table) {
            if (! Schema::hasColumn('message_customers', 'sender_type')) {
                $table->string('sender_type', 20)->default('customer')->after('is_admin');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('message_customers')) {
            return;
        }

        Schema::table('message_customers', function (Blueprint $table) {
            if (Schema::hasColumn('message_customers', 'sender_type')) {
                $table->dropColumn('sender_type');
            }
        });
    }
};
