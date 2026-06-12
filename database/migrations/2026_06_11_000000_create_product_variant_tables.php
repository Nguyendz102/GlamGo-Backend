<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->index();
                $table->string('sku')->nullable()->index();
                $table->decimal('price', 15, 2)->nullable();
                $table->unsignedInteger('quantity')->default(0);
                $table->unsignedTinyInteger('status')->default(1)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_variant_values')) {
            Schema::create('product_variant_values', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_variant_id')->index();
                $table->unsignedBigInteger('product_attribute_value_id')->index();
                $table->timestamps();

                $table->unique(['product_variant_id', 'product_attribute_value_id'], 'variant_value_unique');
            });
        }

        Schema::table('card_item', function (Blueprint $table) {
            if (! Schema::hasColumn('card_item', 'product_variant_id')) {
                $table->unsignedBigInteger('product_variant_id')->nullable()->after('product_id')->index();
            }
        });

        Schema::table('order_item', function (Blueprint $table) {
            if (! Schema::hasColumn('order_item', 'product_variant_id')) {
                $table->unsignedBigInteger('product_variant_id')->nullable()->after('product_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_item', function (Blueprint $table) {
            if (Schema::hasColumn('order_item', 'product_variant_id')) {
                $table->dropColumn('product_variant_id');
            }
        });

        Schema::table('card_item', function (Blueprint $table) {
            if (Schema::hasColumn('card_item', 'product_variant_id')) {
                $table->dropColumn('product_variant_id');
            }
        });

        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_variants');
    }
};
