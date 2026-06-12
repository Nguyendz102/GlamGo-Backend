<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_CUSTOMER = 'customer';
    public const IS_ADMIN = 1;
    public const IS_CUSTOMER = 0;

    protected $table = 'users';

    protected $guarded = [];

    protected $appends = [
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_admin' => 'integer',
        'wallet_balance' => 'decimal:2',
        'password' => 'hashed',
    ];

    public function getRoleAttribute(): string
    {
        return (int) $this->is_admin === self::IS_ADMIN ? self::ROLE_ADMIN : self::ROLE_CUSTOMER;
    }

    public function isAdmin(): bool
    {
        return (int) $this->is_admin === self::IS_ADMIN;
    }

    public function isCustomer(): bool
    {
        return (int) $this->is_admin === self::IS_CUSTOMER;
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }

    public function favoriteProducts()
    {
        return $this->hasMany(FavoriteProduct::class, 'user_id');
    }
}
