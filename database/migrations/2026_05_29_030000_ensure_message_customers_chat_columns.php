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
            if (! Schema::hasColumn('message_customers', 'user_id')) {
                $table->unsignedBigInteger('user_id')->default(0)->index()->after('id');
            }

            if (! Schema::hasColumn('message_customers', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->index()->after('user_id');
            }

            if (! Schema::hasColumn('message_customers', 'session_id')) {
                $table->string('session_id')->nullable()->after('admin_id');
            }

            if (! Schema::hasColumn('message_customers', 'message')) {
                $table->text('message')->nullable()->after('session_id');
            }

            if (! Schema::hasColumn('message_customers', 'is_admin')) {
                $table->boolean('is_admin')->default(false)->after('message');
            }

            if (! Schema::hasColumn('message_customers', 'username')) {
                $table->string('username')->nullable()->after('sender_type');
            }

            if (! Schema::hasColumn('message_customers', 'email')) {
                $table->string('email')->nullable()->after('username');
            }

            if (! Schema::hasColumn('message_customers', 'created_at')) {
                $table->timestamps();
            }

            if (! Schema::hasColumn('message_customers', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        //
    }
};
