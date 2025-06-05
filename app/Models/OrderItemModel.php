<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemModel extends Model
{
    use HasFactory;
    protected $table = 'order_item';
    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }
    public function product()
    {
        return $this->belongsTo(ProductsModel::class, 'product_id');
    }
    public function orderProductAttributeValueItemModel()
    {
        return $this->hasMany(OrderProductAttributeValueItemModel::class, 'order_item_id');
    }
}
