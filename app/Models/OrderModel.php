<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    use HasFactory;
    protected $table = 'order';
    protected $guarded = [];

    public function status()
    {
        return $this->belongsTo(StatusModel::class, 'status_id');
    }
    public function users()
    {
        return $this->belongsTo(UsersModel::class, 'user_id');
    }
    public function coupon()
    {
        return $this->belongsTo(CouponModel::class, 'coupon_id');
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItemModel::class, 'order_id');
    }
    public function country()
    {
        return $this->belongsTo(CountryModel::class, 'country_id');
    }
}
