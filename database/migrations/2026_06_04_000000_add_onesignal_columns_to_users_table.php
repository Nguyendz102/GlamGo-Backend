<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'onesignal_external_id')) {
                $table->string('onesignal_external_id')->nullable()->after('google_id');
            }

            if (! Schema::hasColumn('users', 'onesignal_subscription_id')) {
                $table->string('onesignal_subscription_id')->nullable()->after('onesignal_external_id');
            }

            if (! Schema::hasColumn('users', 'onesignal_push_token')) {
                $table->string('onesignal_push_token')->nullable()->after('onesignal_subscription_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['onesignal_push_token', 'onesignal_subscription_id', 'onesignal_external_id'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
