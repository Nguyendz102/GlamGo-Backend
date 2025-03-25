<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProductAttributeValueItemModel extends Model
{
    use HasFactory;
    protected $table = 'order_product_attribute_values_item';
    protected $guarded = []; 
    public function attributeValue(){
        return $this->belongsTo(ProductAttributeValuesModel::class, 'product_attribute_value_id','id');
    }
}
