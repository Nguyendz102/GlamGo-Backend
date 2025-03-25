<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponModel extends Model
{
    use HasFactory;
    protected $table = 'coupon';
    protected $guarded = [];

    public function status()
    {
        return $this->belongsTo(StatusModel::class, 'status_id');
    }
    public function orders()
    {
        return $this->hasMany(OrderModel::class, 'coupon_id');
    }
}
