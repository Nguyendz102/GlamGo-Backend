<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order')) {
            Schema::create('order', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->default(0)->index();
                $table->unsignedBigInteger('coupon_id')->default(0)->index();
                $table->string('code')->index();
                $table->string('transaction_id')->nullable();
                $table->unsignedTinyInteger('payment_method')->default(1);
                $table->unsignedTinyInteger('payment_status')->default(2);
                $table->unsignedTinyInteger('status')->default(1)->index();
                $table->unsignedBigInteger('country_id')->default(1);
                $table->decimal('total_price', 15, 2)->default(0);
                $table->string('email');
                $table->string('first_name');
                $table->string('last_name');
                $table->string('postal_code')->nullable();
                $table->string('address');
                $table->string('phone_number');
                $table->text('note')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('order', function (Blueprint $table) {
                $this->addColumnIfMissing($table, 'user_id', fn () => $table->unsignedBigInteger('user_id')->default(0)->index());
                $this->addColumnIfMissing($table, 'coupon_id', fn () => $table->unsignedBigInteger('coupon_id')->default(0)->index());
                $this->addColumnIfMissing($table, 'code', fn () => $table->string('code')->nullable()->index());
                $this->addColumnIfMissing($table, 'transaction_id', fn () => $table->string('transaction_id')->nullable());
                $this->addColumnIfMissing($table, 'payment_method', fn () => $table->unsignedTinyInteger('payment_method')->default(1));
                $this->addColumnIfMissing($table, 'payment_status', fn () => $table->unsignedTinyInteger('payment_status')->default(2));
                $this->addColumnIfMissing($table, 'status', fn () => $table->unsignedTinyInteger('status')->default(1)->index());
                $this->addColumnIfMissing($table, 'country_id', fn () => $table->unsignedBigInteger('country_id')->default(1));
                $this->addColumnIfMissing($table, 'total_price', fn () => $table->decimal('total_price', 15, 2)->default(0));
                $this->addColumnIfMissing($table, 'email', fn () => $table->string('email')->nullable());
                $this->addColumnIfMissing($table, 'first_name', fn () => $table->string('first_name')->nullable());
                $this->addColumnIfMissing($table, 'last_name', fn () => $table->string('last_name')->nullable());
                $this->addColumnIfMissing($table, 'postal_code', fn () => $table->string('postal_code')->nullable());
                $this->addColumnIfMissing($table, 'address', fn () => $table->string('address')->nullable());
                $this->addColumnIfMissing($table, 'phone_number', fn () => $table->string('phone_number')->nullable());
                $this->addColumnIfMissing($table, 'note', fn () => $table->text('note')->nullable());

                if (! Schema::hasColumn('order', 'created_at')) {
                    $table->timestamps();
                }
            });
        }

        if (! Schema::hasTable('order_item')) {
            Schema::create('order_item', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->decimal('price', 15, 2)->default(0);
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('total_price', 15, 2)->default(0);
                $table->timestamps();
            });
        } else {
            Schema::table('order_item', function (Blueprint $table) {
                $this->addColumnIfMissing($table, 'order_id', fn () => $table->unsignedBigInteger('order_id')->default(0)->index());
                $this->addColumnIfMissing($table, 'product_id', fn () => $table->unsignedBigInteger('product_id')->default(0)->index());
                $this->addColumnIfMissing($table, 'price', fn () => $table->decimal('price', 15, 2)->default(0));
                $this->addColumnIfMissing($table, 'quantity', fn () => $table->unsignedInteger('quantity')->default(1));
                $this->addColumnIfMissing($table, 'total_price', fn () => $table->decimal('total_price', 15, 2)->default(0));

                if (! Schema::hasColumn('order_item', 'created_at')) {
                    $table->timestamps();
                }
            });
        }

        if (! Schema::hasTable('order_product_attribute_values_item')) {
            Schema::create('order_product_attribute_values_item', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_item_id')->index();
                $table->json('product_attribute_value_id')->nullable();
                $table->unsignedBigInteger('product_atribute_id_name')->nullable();
                $table->string('personalise_name')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('order_product_attribute_values_item', function (Blueprint $table) {
                $this->addColumnIfMissing($table, 'order_item_id', fn () => $table->unsignedBigInteger('order_item_id')->default(0)->index());
                $this->addColumnIfMissing($table, 'product_attribute_value_id', fn () => $table->json('product_attribute_value_id')->nullable());
                $this->addColumnIfMissing($table, 'product_atribute_id_name', fn () => $table->unsignedBigInteger('product_atribute_id_name')->nullable());
                $this->addColumnIfMissing($table, 'personalise_name', fn () => $table->string('personalise_name')->nullable());

                if (! Schema::hasColumn('order_product_attribute_values_item', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_product_attribute_values_item');
        Schema::dropIfExists('order_item');
        Schema::dropIfExists('order');
    }

    private function addColumnIfMissing(Blueprint $table, string $column, callable $callback): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $callback();
        }
    }
};
