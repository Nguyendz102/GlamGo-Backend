<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('card')) {
            Schema::create('card', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('code')->nullable();
                $table->unsignedTinyInteger('status')->default(1)->index();
                $table->unsignedBigInteger('coupon_id')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('discount', 15, 2)->default(0);
                $table->decimal('total_price', 15, 2)->default(0);
                $table->timestamps();
            });
        } else {
            Schema::table('card', function (Blueprint $table) {
                if (! Schema::hasColumn('card', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->default(0)->index();
                }

                if (! Schema::hasColumn('card', 'code')) {
                    $table->string('code')->nullable();
                }

                if (! Schema::hasColumn('card', 'status')) {
                    $table->unsignedTinyInteger('status')->default(1)->index();
                }

                if (! Schema::hasColumn('card', 'coupon_id')) {
                    $table->unsignedBigInteger('coupon_id')->nullable();
                }

                if (! Schema::hasColumn('card', 'subtotal')) {
                    $table->decimal('subtotal', 15, 2)->default(0);
                }

                if (! Schema::hasColumn('card', 'discount')) {
                    $table->decimal('discount', 15, 2)->default(0);
                }

                if (! Schema::hasColumn('card', 'total_price')) {
                    $table->decimal('total_price', 15, 2)->default(0);
                }

                if (! Schema::hasColumn('card', 'created_at')) {
                    $table->timestamps();
                }
            });
        }

        if (! Schema::hasTable('card_item')) {
            Schema::create('card_item', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('card_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('attribute_name_id')->nullable();
                $table->json('attribute_ids')->nullable();
                $table->string('personalise_name')->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('total_price', 15, 2)->default(0);
                $table->timestamps();
            });
        } else {
            Schema::table('card_item', function (Blueprint $table) {
                if (! Schema::hasColumn('card_item', 'card_id')) {
                    $table->unsignedBigInteger('card_id')->default(0)->index();
                }

                if (! Schema::hasColumn('card_item', 'product_id')) {
                    $table->unsignedBigInteger('product_id')->default(0)->index();
                }

                if (! Schema::hasColumn('card_item', 'attribute_name_id')) {
                    $table->unsignedBigInteger('attribute_name_id')->nullable();
                }

                if (! Schema::hasColumn('card_item', 'attribute_ids')) {
                    $table->json('attribute_ids')->nullable();
                }

                if (! Schema::hasColumn('card_item', 'personalise_name')) {
                    $table->string('personalise_name')->nullable();
                }

                if (! Schema::hasColumn('card_item', 'price')) {
                    $table->decimal('price', 15, 2)->default(0);
                }

                if (! Schema::hasColumn('card_item', 'quantity')) {
                    $table->unsignedInteger('quantity')->default(1);
                }

                if (! Schema::hasColumn('card_item', 'total_price')) {
                    $table->decimal('total_price', 15, 2)->default(0);
                }

                if (! Schema::hasColumn('card_item', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('card_item');
        Schema::dropIfExists('card');
    }
};
