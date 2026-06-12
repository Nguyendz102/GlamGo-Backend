<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponModel extends Model
{
    use HasFactory;

    public const INACTIVE = 0;
    public const ACTIVE = 1;

    protected $table = 'coupon';
    protected $guarded = [];

    protected $casts = [
        'discount_type' => 'float',
        'min_order_value' => 'float',
        'max_value' => 'float',
        'status' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'usage_limit' => 'integer',
        'type_unit' => 'integer',
    ];

    public function orders()
    {
        return $this->hasMany(OrderModel::class, 'coupon_id');
    }
}
