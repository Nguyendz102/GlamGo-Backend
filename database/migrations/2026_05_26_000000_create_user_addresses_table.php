<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('label', 100)->nullable();
            $table->string('recipient_name');
            $table->string('phone', 20);
            $table->text('address_line');
            $table->string('ward', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('country', 100)->default('Vietnam');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
