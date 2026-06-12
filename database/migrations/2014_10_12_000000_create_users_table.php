<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('Ho ten khach hang');
                $table->string('code')->comment('Ma khach hang');
                $table->string('user_name')->comment('Ten dang nhap');
                $table->string('email')->unique('email_unique')->comment('Email khach hang');
                $table->string('phone')->comment('SDT khach hang');
                $table->string('avatar')->nullable()->comment('Link anh dai dien');
                $table->string('address')->nullable()->comment('Dia chi khach hang');
                $table->integer('contry_id')->default(0)->comment('ID quoc gia');
                $table->string('password')->comment('Mat khau');
                $table->integer('status_id')->default(1)->comment('Trang thai');
                $table->string('google_id')->default('');
                $table->timestamps();
                $table->integer('is_admin')->default(0);
            });

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'code')) {
                $table->string('code')->default('')->after('name')->comment('Ma khach hang');
            }

            if (! Schema::hasColumn('users', 'user_name')) {
                $table->string('user_name')->default('')->after('code')->comment('Ten dang nhap');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->default('')->after('email')->comment('SDT khach hang');
            }

            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('phone')->comment('Link anh dai dien');
            }

            if (! Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable()->after('avatar')->comment('Dia chi khach hang');
            }

            if (! Schema::hasColumn('users', 'contry_id')) {
                $table->integer('contry_id')->default(0)->after('address')->comment('ID quoc gia');
            }

            if (! Schema::hasColumn('users', 'status_id')) {
                $table->integer('status_id')->default(1)->after('password')->comment('Trang thai');
            }

            if (! Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->default('')->after('status_id');
            }

            if (! Schema::hasColumn('users', 'is_admin')) {
                $table->integer('is_admin')->default(0)->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
