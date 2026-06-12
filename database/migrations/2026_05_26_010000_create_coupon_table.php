<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('coupon')) {
            return;
        }

        Schema::create('coupon', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->text('description')->nullable();
            $table->decimal('discount_type', 15, 2)->comment('Gia tri giam gia');
            $table->decimal('min_order_value', 15, 2)->default(0);
            $table->decimal('max_value', 15, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(0)->comment('Trang thai');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->unsignedInteger('usage_limit')->default(0)->comment('Gioi han su dung');
            $table->unsignedTinyInteger('type_unit')->default(1)->comment('1: %, 2: tien mat');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon');
    }
};
