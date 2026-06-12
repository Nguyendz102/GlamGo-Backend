<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_sessions')) {
            return;
        }

        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('handled_by', 20)->default('bot');
            $table->timestamp('admin_active_until')->nullable();
            $table->timestamp('last_customer_message_at')->nullable();
            $table->timestamp('last_admin_message_at')->nullable();
            $table->timestamp('last_bot_message_at')->nullable();
            $table->timestamp('last_handoff_at')->nullable();
            $table->timestamps();

            $table->index(['handled_by', 'admin_active_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
